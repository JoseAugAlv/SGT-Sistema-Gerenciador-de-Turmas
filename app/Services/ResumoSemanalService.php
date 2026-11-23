<?php
// app/Services/ResumoSemanalService.php
// Resumo semanal por email (segunda-feira): para cada pessoa, o que vem pela frente nas turmas dela
// (agenda dos próximos 7 dias, reuniões), tarefas abertas do Kanban, enquetes que ainda não votou,
// avisos da semana e notificações não lidas. Quem desligou em Preferências não recebe; sem nada para
// resumir, não manda. Uma vez por semana por pessoa (tabela resumo_envios).
//
// Como dispara (sem agendador obrigatório):
//   1. scripts/resumo_semanal.php (Agendador de Tarefas do Windows / cron, segunda 7h) — recomendado;
//   2. o master pode mandar na hora (ResumoController).
// (O disparo automático durante as requisições foi removido: enviar email no fim da requisição deixava
//  o clique de quem estava usando o sistema lento. talvezEnviar fica para quem quiser ligar por conta própria.)
require_once __DIR__ . '/../Config/database.php';
require_once __DIR__ . '/../Core/App.php';
require_once __DIR__ . '/../Core/Mail.php';

class ResumoSemanalService
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getConnection();
    }

    public static function semana(?int $ts = null): string
    {
        return date('o-\WW', $ts ?? time());
    }

    /** Janela do envio automático: de segunda 7h até quarta 23h59. */
    public static function ehHora(?int $ts = null): bool
    {
        $ts = $ts ?? time();
        $dia = (int) date('N', $ts);
        return ($dia === 1 && (int) date('G', $ts) >= 7) || $dia === 2 || $dia === 3;
    }

    /** Disparo automático no fim de uma requisição (lote pequeno, no máximo um por minuto). */
    public static function talvezEnviar(int $lote = 5): void
    {
        if (!self::ehHora() || (string) App::get('MAIL_USER', '') === '') return;
        try {
            $pdo = Database::getConnection();
            // trava simples: só uma requisição por minuto faz o lote
            $st = $pdo->prepare("INSERT INTO config_comercial (chave, valor, descricao) VALUES ('resumo_lote_em', ?, 'Resumo semanal: último lote automático')
                                 ON DUPLICATE KEY UPDATE valor = IF(valor < ?, VALUES(valor), valor)");
            $agora = date('Y-m-d H:i:s');
            $st->execute([$agora, date('Y-m-d H:i:s', time() - 60)]);
            if ($st->rowCount() === 0) return;
            (new self())->enviarLote($lote);
        } catch (\Throwable $e) {
            error_log('resumo semanal: ' . $e->getMessage());
        }
    }

    /** Envia para até $limite pessoas que ainda não receberam nesta semana. Retorna ['enviados', 'vazios']. */
    public function enviarLote(int $limite = 50, bool $soEste = false, ?int $uid = null): array
    {
        $sem = self::semana();
        $sql = "SELECT u.id, u.nome, u.email FROM usuarios u
                LEFT JOIN preferencias_usuario p ON p.usuario_id = u.id
                WHERE u.ativo = 1 AND u.email_confirmado = 1 AND COALESCE(p.resumo_semanal, 1) = 1
                  AND NOT EXISTS (SELECT 1 FROM resumo_envios r WHERE r.usuario_id = u.id AND r.semana = ?)"
             . ($soEste ? " AND u.id = ?" : '') . " ORDER BY u.id LIMIT " . max(1, $limite);
        $st = $this->pdo->prepare($sql);
        $st->execute($soEste ? [$sem, $uid] : [$sem]);
        $mail = new Mail();
        $marca = $this->pdo->prepare("INSERT IGNORE INTO resumo_envios (usuario_id, semana, enviado_em, ok) VALUES (?, ?, NOW(), ?)");
        $r = ['enviados' => 0, 'vazios' => 0];
        foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $u) {
            $dados = $this->montar((int) $u['id']);
            if (!$dados['tem']) { $marca->execute([$u['id'], $sem, 0]); $r['vazios']++; continue; }
            $ok = $mail->enviar($u['email'], 'Sua semana no ' . App::getName(), $this->html($u, $dados), $u['nome']);
            $marca->execute([$u['id'], $sem, $ok ? 1 : 0]);
            $ok ? $r['enviados']++ : $r['vazios']++;
        }
        return $r;
    }

    /** Dados do resumo de uma pessoa. */
    public function montar(int $uid): array
    {
        $st = $this->pdo->prepare("SELECT t.id, t.nome FROM turma_usuarios tu INNER JOIN turmas t ON t.id = tu.turma_id WHERE tu.usuario_id = ? AND tu.ativo = 1 AND t.bloqueada = 0");
        $st->execute([$uid]);
        $turmas = array_column($st->fetchAll(PDO::FETCH_ASSOC), 'nome', 'id');
        $ids = array_keys($turmas) ?: [0];
        $in = implode(',', array_map('intval', $ids));

        $agenda = $this->pdo->prepare("
            (SELECT e.titulo, e.data, e.hora_inicio AS hora, e.tipo, e.prioridade, t.nome AS turma
               FROM eventos_calendario e LEFT JOIN turmas t ON t.id = e.turma_id
              WHERE e.data BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
                AND ((e.visibilidade = 'turma' AND e.turma_id IN ($in)) OR (e.visibilidade = 'pessoal' AND e.criado_por = ?)))
            UNION ALL
            (SELECT r.titulo, DATE(r.data), TIME(r.data), 'reuniao', 'media', t.nome
               FROM reunioes r INNER JOIN turmas t ON t.id = r.turma_id
              WHERE r.turma_id IN ($in) AND r.status = 'agendada' AND r.data BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 7 DAY))
            ORDER BY data, hora LIMIT 12
        ");
        $agenda->execute([$uid]);

        $tarefas = $this->pdo->prepare("
            SELECT t.titulo, t.prazo, t.coluna, g.nome AS grupo FROM grupo_tarefas t INNER JOIN grupos g ON g.id = t.grupo_id
            WHERE t.responsavel_id = ? AND t.coluna <> 'feito' ORDER BY t.prazo IS NULL, t.prazo LIMIT 8
        ");
        $tarefas->execute([$uid]);

        $enquetes = $this->pdo->prepare("
            SELECT e.id, e.pergunta, e.encerra_em, t.nome AS turma FROM enquetes e INNER JOIN turmas t ON t.id = e.turma_id
            WHERE e.turma_id IN ($in) AND e.encerrada_em IS NULL AND (e.encerra_em IS NULL OR e.encerra_em > NOW())
              AND NOT EXISTS (SELECT 1 FROM enquete_votos v WHERE v.enquete_id = e.id AND v.usuario_id = ?) ORDER BY e.created_at DESC LIMIT 5
        ");
        $enquetes->execute([$uid]);

        $avisos = $this->pdo->query("
            SELECT a.titulo, a.urgente, t.nome AS turma FROM alertas a INNER JOIN turmas t ON t.id = a.turma_id
            WHERE a.turma_id IN ($in) AND a.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) AND (a.expira_em IS NULL OR a.expira_em > NOW())
            ORDER BY a.urgente DESC, a.created_at DESC LIMIT 6
        ");

        $nl = $this->pdo->prepare("SELECT COUNT(*) FROM notificacoes WHERE usuario_id = ? AND lida = 0");
        $nl->execute([$uid]);

        $d = [
            'agenda'   => $agenda->fetchAll(PDO::FETCH_ASSOC),
            'tarefas'  => $tarefas->fetchAll(PDO::FETCH_ASSOC),
            'enquetes' => $enquetes->fetchAll(PDO::FETCH_ASSOC),
            'avisos'   => $avisos->fetchAll(PDO::FETCH_ASSOC),
            'naoLidas' => (int) $nl->fetchColumn(),
            'turmas'   => $turmas,
        ];
        $d['tem'] = $d['agenda'] || $d['tarefas'] || $d['enquetes'] || $d['avisos'];
        return $d;
    }

    /** Email no modelo do Prumo. */
    public function html(array $u, array $d): string
    {
        $e = fn($t) => Mail::e((string) $t);
        $dias = ['', 'seg', 'ter', 'qua', 'qui', 'sex', 'sáb', 'dom'];
        $cores = ['baixa' => '#29946e', 'media' => '#2563eb', 'alta' => '#e08a00', 'urgente' => '#d63548'];
        $secao = fn($titulo, $linhas) => $linhas ? '<h3 style="margin:24px 0 8px;font-size:15px;color:#0f172a" class="ap-texto">' . $titulo . '</h3>'
            . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-top:1px solid #e2e8f0" class="ap-borda">' . implode('', $linhas) . '</table>' : '';
        $linha = fn($esq, $txt, $sub, $cor = '#2563eb') => '<tr><td style="padding:10px 12px 10px 0;width:64px;vertical-align:top;border-bottom:1px solid #e2e8f0" class="ap-borda">'
            . '<div style="font-size:12px;font-weight:700;color:' . $cor . ';text-transform:uppercase">' . $esq . '</div></td>'
            . '<td style="padding:10px 0;border-bottom:1px solid #e2e8f0" class="ap-borda"><div style="font-size:14px;font-weight:600;color:#0f172a" class="ap-texto">' . $txt . '</div>'
            . ($sub ? '<div style="font-size:12px;color:#64748b" class="ap-mudo">' . $sub . '</div>' : '') . '</td></tr>';

        $ag = array_map(fn($a) => $linha($dias[(int) date('N', strtotime($a['data']))] . ' ' . date('d/m', strtotime($a['data'])), $e($a['titulo']),
            $e(trim(($a['hora'] ? substr($a['hora'], 0, 5) . ' · ' : '') . ($a['turma'] ?? 'Pessoal'))), $cores[$a['prioridade']] ?? '#2563eb'), $d['agenda']);
        $ta = array_map(fn($t) => $linha($t['prazo'] ? date('d/m', strtotime($t['prazo'])) : 'sem prazo', $e($t['titulo']), 'Grupo ' . $e($t['grupo']),
            $t['prazo'] && $t['prazo'] < date('Y-m-d') ? '#d63548' : '#7c3aed'), $d['tarefas']);
        $en = array_map(fn($q) => $linha('votar', $e($q['pergunta']), $e($q['turma']) . ($q['encerra_em'] ? ' · até ' . date('d/m H:i', strtotime($q['encerra_em'])) : ''), '#0ea5a4'), $d['enquetes']);
        $av = array_map(fn($a) => $linha($a['urgente'] ? 'urgente' : 'aviso', $e($a['titulo']), $e($a['turma']), $a['urgente'] ? '#d63548' : '#64748b'), $d['avisos']);

        $primeiro = $e(explode(' ', trim($u['nome']))[0] ?? '');
        $resumoNum = count($d['agenda']) . ' compromisso(s) nos próximos 7 dias';
        $corpo = '<p style="margin:0 0 6px;font-size:15px;line-height:1.6;color:#334155" class="ap-texto">Oi, ' . $primeiro . '! Aqui está o que vem pela frente nas suas turmas.</p>'
            . $secao('Agenda dos próximos 7 dias', $ag) . $secao('Suas tarefas em aberto', $ta) . $secao('Enquetes esperando o seu voto', $en) . $secao('Avisos da semana', $av)
            . ($d['naoLidas'] ? '<p style="margin:22px 0 0;font-size:14px;color:#334155" class="ap-texto">Você tem <strong>' . $d['naoLidas'] . '</strong> notificação(ões) não lida(s).</p>' : '');
        return Mail::modelo([
            'selo'    => 'Resumo da semana',
            'titulo'  => 'Sua semana no ' . App::getName(),
            'previa'  => $resumoNum,
            'corpo'   => $corpo,
            'botao'   => ['Abrir o ' . App::getName(), '/'],
            'rodape'  => 'Você recebe este resumo às segundas. Para parar, desligue em Notificações > Preferências.',
        ]);
    }
}
