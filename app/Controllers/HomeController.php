<?php
// app/Controllers/HomeController.php
require_once __DIR__ . '/../Core/App.php';
require_once __DIR__ . '/../Config/database.php';
require_once __DIR__ . '/../Models/Turma.php';
require_once __DIR__ . '/../Models/TurmaUsuario.php';
require_once __DIR__ . '/../Models/Alerta.php';
require_once __DIR__ . '/../Models/Notificacao.php';

class HomeController
{
    public function index()
    {
        if (empty($_SESSION['usuario'])) {
            $tituloPagina = App::getName() . ' — Bem-vindo';
            require __DIR__ . '/../Views/home/landing.php';
            return;
        }

        $u        = $_SESSION['usuario'];
        $userId   = (int) $u['id'];
        $isMaster = $u['tipo'] === 'master';

        $pdo          = Database::getConnection();
        $turmaModel   = new Turma();
        $tuModel      = new TurmaUsuario();
        $alertaModel  = new Alerta();
        $notifModel   = new Notificacao();

        // ============ TURMAS ============
        $minhasTurmas = $isMaster
            ? $turmaModel->listarTodas()
            : $turmaModel->listarDoUsuario($userId);

        $idsTurmas = array_map(fn($t) => (int) $t['id'], $minhasTurmas);

        // ============ ALERTAS (top 5) ============
        $alertas = [];
        foreach ($minhasTurmas as $t) {
            foreach ($alertaModel->listarVisiveis((int) $t['id'], $userId) as $a) {
                $a['turma_nome_ctx'] = $t['nome'];
                $alertas[] = $a;
            }
        }
        usort($alertas, function ($x, $y) {
            $ux = (int) $x['urgente'];
            $uy = (int) $y['urgente'];
            if ($ux !== $uy) return $uy - $ux;
            return strcmp($y['created_at'], $x['created_at']);
        });
        $alertas = array_slice($alertas, 0, 5);

        // ============ NOTIFICAÇÕES ============
        $naoLidas      = $notifModel->contarNaoLidas($userId);
        $ultimasNotifs = $notifModel->ultimas($userId, 6);

        // ============ PAPÉIS ============
        $souRepEmAlgumaTurma    = false;
        $souDiretorEmAlgumGrupo = false;
        $meusGrupos             = [];

        if (!$isMaster) {
            foreach ($minhasTurmas as $t) {
                if ($tuModel->ehRepresentante((int) $t['id'], $userId)) {
                    $souRepEmAlgumaTurma = true;
                    break;
                }
            }
            $stmt = $pdo->prepare("
                SELECT g.id, g.nome AS grupo_nome, p.nome AS projeto_nome,
                       p.id AS projeto_id, t.nome AS turma_nome
                FROM grupo_diretores gd
                INNER JOIN grupos g   ON g.id = gd.grupo_id
                INNER JOIN projetos p ON p.id = g.projeto_id
                INNER JOIN turmas t   ON t.id = p.turma_id
                WHERE gd.usuario_id = ? AND gd.ativo = 1
                ORDER BY p.nome, g.nome
            ");
            $stmt->execute([$userId]);
            $meusGrupos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $souDiretorEmAlgumGrupo = !empty($meusGrupos);
        }

        // ============ STATS (varia por papel) ============
        $stats = $this->montarStats($pdo, $isMaster, $userId, $idsTurmas, $souRepEmAlgumaTurma, $souDiretorEmAlgumGrupo);

        // ============ PROJETOS EM ANDAMENTO ============
        $projetos = $this->projetosEmAndamento($pdo, $isMaster, $idsTurmas, $meusGrupos, $userId);

        // ============ ATIVIDADE RECENTE ============
        $atividade = $this->atividadeRecente($pdo, $isMaster, $userId, $idsTurmas);

        // ============ PRÓXIMOS PRAZOS ============
        $prazos = $this->proximosPrazos($pdo, $idsTurmas);

        // ============ DISTRIBUIÇÃO DE PAPÉIS (por turma) ============
        $papeis = $this->distribuicaoPapeis($pdo, $idsTurmas);

        $tituloPagina = 'Painel — ' . App::getName();
        $dados = [
            'usuario'               => $u,
            'isMaster'              => $isMaster,
            'minhasTurmas'          => $minhasTurmas,
            'alertas'               => $alertas,
            'naoLidas'              => $naoLidas,
            'ultimasNotifs'         => $ultimasNotifs,
            'souRepEmAlgumaTurma'   => $souRepEmAlgumaTurma,
            'souDiretorEmAlgumGrupo'=> $souDiretorEmAlgumGrupo,
            'meusGrupos'            => $meusGrupos,
            'stats'                 => $stats,
            'projetos'              => $projetos,
            'atividade'             => $atividade,
            'prazos'                => $prazos,
            'papeis'                => $papeis,
        ];
        extract($dados);
        require __DIR__ . '/../Views/home/index.php';
    }

    // ============ HELPERS ============

    private function montarStats(PDO $pdo, bool $isMaster, int $userId, array $idsTurmas, bool $souRep, bool $souDiretor): array
    {
        if ($isMaster) {
            return [
                ['label' => 'Turmas',            'valor' => (int) $pdo->query("SELECT COUNT(*) FROM turmas")->fetchColumn(),                                          'icon' => 'fas fa-users',            'cor' => 'purple', 'foot' => 'no sistema'],
                ['label' => 'Alunos ativos',     'valor' => (int) $pdo->query("SELECT COUNT(*) FROM usuarios WHERE tipo = 'aluno' AND ativo = 1")->fetchColumn(),          'icon' => 'fas fa-user',             'cor' => 'blue',   'foot' => 'contas ativas'],
                ['label' => 'Projetos abertos',  'valor' => (int) $pdo->query("SELECT COUNT(*) FROM projetos WHERE encerrado = 0")->fetchColumn(),                         'icon' => 'fas fa-diagram-project',  'cor' => 'orange', 'foot' => 'em andamento'],
                ['label' => 'Alertas ativos',    'valor' => (int) $pdo->query("SELECT COUNT(*) FROM alertas WHERE expira_em IS NULL OR expira_em >= NOW()")->fetchColumn(),'icon' => 'fas fa-bullhorn',         'cor' => 'green',  'foot' => 'visíveis'],
            ];
        }

        if (empty($idsTurmas)) {
            return [
                ['label' => 'Turmas',        'valor' => 0, 'icon' => 'fas fa-users',           'cor' => 'purple', 'foot' => 'entre em uma turma'],
                ['label' => 'Projetos',      'valor' => 0, 'icon' => 'fas fa-diagram-project', 'cor' => 'orange', 'foot' => 'sem projetos'],
                ['label' => 'Avaliações',    'valor' => 0, 'icon' => 'fas fa-star',            'cor' => 'blue',   'foot' => 'nenhuma aberta'],
                ['label' => 'Alertas',       'valor' => 0, 'icon' => 'fas fa-bullhorn',        'cor' => 'green',  'foot' => 'nenhum ativo'],
            ];
        }

        $ph = implode(',', array_fill(0, count($idsTurmas), '?'));

        // Projetos abertos nas minhas turmas
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM projetos WHERE turma_id IN ($ph) AND encerrado = 0");
        $stmt->execute($idsTurmas);
        $projetosAbertos = (int) $stmt->fetchColumn();

        // Critérios abertos nas minhas turmas
        $stmt = $pdo->prepare("
            SELECT COUNT(DISTINCT c.id)
            FROM criterios c
            INNER JOIN projetos p ON p.id = c.projeto_id
            WHERE p.turma_id IN ($ph) AND p.encerrado = 0 AND c.bloqueado = 0
        ");
        $stmt->execute($idsTurmas);
        $criteriosAbertos = (int) $stmt->fetchColumn();

        // Atas pendentes para mim (se sou diretor) ou total de atas nas turmas
        if ($souDiretor) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM atas WHERE diretor_id = ? AND status = 'pendente'");
            $stmt->execute([$userId]);
            $atasPendentes = (int) $stmt->fetchColumn();
        } else {
            $atasPendentes = 0;
        }

        // Alertas ativos nas minhas turmas
        $stmt = $pdo->prepare("
            SELECT COUNT(*)
            FROM alertas
            WHERE turma_id IN ($ph) AND (expira_em IS NULL OR expira_em >= NOW())
        ");
        $stmt->execute($idsTurmas);
        $alertasAtivos = (int) $stmt->fetchColumn();

        return [
            ['label' => 'Minhas turmas',    'valor' => count($idsTurmas),  'icon' => 'fas fa-users',           'cor' => 'purple', 'foot' => 'onde você participa'],
            ['label' => 'Projetos abertos', 'valor' => $projetosAbertos,   'icon' => 'fas fa-diagram-project', 'cor' => 'orange', 'foot' => 'em andamento'],
            ['label' => $souDiretor ? 'Atas pendentes' : 'Critérios abertos',
                                            'valor' => $souDiretor ? $atasPendentes : $criteriosAbertos,
                                                                           'icon' => $souDiretor ? 'fas fa-book' : 'fas fa-clipboard-check',
                                                                                                                  'cor' => 'blue',   'foot' => $souDiretor ? 'para preencher' : 'aguardando avaliação'],
            ['label' => 'Alertas ativos',   'valor' => $alertasAtivos,     'icon' => 'fas fa-bullhorn',        'cor' => 'green',  'foot' => 'nas suas turmas'],
        ];
    }

    private function projetosEmAndamento(PDO $pdo, bool $isMaster, array $idsTurmas, array $meusGrupos, int $userId): array
    {
        if (empty($idsTurmas)) return [];
        $ph = implode(',', array_fill(0, count($idsTurmas), '?'));

        $stmt = $pdo->prepare("
            SELECT p.id, p.nome, p.prazo, p.modo_avaliacao, t.nome AS turma_nome,
                   (SELECT COUNT(*) FROM grupos g WHERE g.projeto_id = p.id) AS total_grupos,
                   (SELECT COUNT(*) FROM criterios c WHERE c.projeto_id = p.id) AS total_criterios
            FROM projetos p
            INNER JOIN turmas t ON t.id = p.turma_id
            WHERE p.turma_id IN ($ph) AND p.encerrado = 0
            ORDER BY p.prazo IS NULL, p.prazo ASC
            LIMIT 6
        ");
        $stmt->execute($idsTurmas);
        $lista = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Calcula "progresso" simples baseado no prazo restante (prazo - hoje)
        $hoje = new DateTime();
        foreach ($lista as &$p) {
            $progresso = 0;
            if (!empty($p['prazo'])) {
                $prazo = DateTime::createFromFormat('Y-m-d', $p['prazo']);
                if ($prazo) {
                    $total    = 60; // janela arbitrária de 60 dias
                    $restante = max(0, (int) $hoje->diff($prazo)->format('%r%a'));
                    $progresso = max(0, min(100, 100 - ($restante / $total) * 100));
                }
            }
            $p['progresso'] = (int) $progresso;

            // Dias até o prazo
            $p['dias_restantes'] = null;
            if (!empty($p['prazo'])) {
                $prazo = DateTime::createFromFormat('Y-m-d', $p['prazo']);
                if ($prazo) $p['dias_restantes'] = (int) $hoje->diff($prazo)->format('%r%a');
            }

            // Iniciais para o "logo"
            $p['sigla'] = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $p['nome']), 0, 2));
        }
        return $lista;
    }

    private function atividadeRecente(PDO $pdo, bool $isMaster, int $userId, array $idsTurmas): array
    {
        // Master vê auditoria; outros veem suas notificações
        if ($isMaster) {
            $stmt = $pdo->query("
                SELECT a.acao, a.tabela_afetada, a.created_at, u.nome AS usuario_nome
                FROM auditoria_log a
                LEFT JOIN usuarios u ON u.id = a.usuario_id
                ORDER BY a.created_at DESC
                LIMIT 6
            ");
            $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $atividade = [];
            foreach ($logs as $l) {
                $atividade[] = [
                    'titulo' => $this->humanizar($l['acao']),
                    'texto'  => ($l['usuario_nome'] ?? 'Sistema') . ' • ' . $this->tempoRelativo($l['created_at']),
                    'icon'   => 'fas fa-circle',
                    'cor'    => 'blue',
                ];
            }
            return $atividade;
        }

        $stmt = $pdo->prepare("
            SELECT titulo, mensagem, tipo, created_at
            FROM notificacoes
            WHERE usuario_id = ?
            ORDER BY created_at DESC
            LIMIT 6
        ");
        $stmt->execute([$userId]);
        $notifs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $mapaCor = [
            'alerta'    => ['icon' => 'fas fa-bullhorn', 'cor' => 'orange'],
            'avaliacao' => ['icon' => 'fas fa-star',     'cor' => 'blue'],
            'papel'     => ['icon' => 'fas fa-user-tag', 'cor' => 'purple'],
            'ata'       => ['icon' => 'fas fa-book',     'cor' => 'orange'],
            'prazo'     => ['icon' => 'fas fa-clock',    'cor' => 'blue'],
            'material'  => ['icon' => 'fas fa-boxes',    'cor' => 'green'],
            'lgpd'      => ['icon' => 'fas fa-shield',   'cor' => 'purple'],
        ];

        $atividade = [];
        foreach ($notifs as $n) {
            $tipo = $n['tipo'] ?? 'alerta';
            $cfg  = $mapaCor[$tipo] ?? ['icon' => 'fas fa-bell', 'cor' => 'blue'];

            $atividade[] = [
                'titulo' => $n['titulo'],
                'texto'  => $this->tempoRelativo($n['created_at']),
                'icon'   => $cfg['icon'],
                'cor'    => $cfg['cor'],
            ];
        }
        return $atividade;
    }

    private function proximosPrazos(PDO $pdo, array $idsTurmas): array
    {
        if (empty($idsTurmas)) return [];
        $ph = implode(',', array_fill(0, count($idsTurmas), '?'));

        // Unifica: critérios com prazo (30 dias) + projetos com prazo (30 dias)
        $stmt = $pdo->prepare("
            (SELECT c.id, c.nome AS titulo, c.prazo_avaliacao AS prazo,
                    p.nome AS contexto, p.id AS projeto_id, 'criterio' AS tipo
             FROM criterios c
             INNER JOIN projetos p ON p.id = c.projeto_id
             WHERE p.turma_id IN ($ph)
               AND c.bloqueado = 0
               AND c.prazo_avaliacao >= NOW()
               AND c.prazo_avaliacao <= DATE_ADD(NOW(), INTERVAL 30 DAY))
            UNION
            (SELECT p.id, p.nome AS titulo, CAST(p.prazo AS DATETIME) AS prazo,
                    t.nome AS contexto, p.id AS projeto_id, 'projeto' AS tipo
             FROM projetos p
             INNER JOIN turmas t ON t.id = p.turma_id
             WHERE p.turma_id IN ($ph)
               AND p.encerrado = 0
               AND p.prazo IS NOT NULL
               AND p.prazo >= CURDATE()
               AND p.prazo <= DATE_ADD(CURDATE(), INTERVAL 30 DAY))
            ORDER BY prazo ASC
            LIMIT 5
        ");
        $stmt->execute(array_merge($idsTurmas, $idsTurmas));
        $lista = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $hoje = new DateTime();
        foreach ($lista as &$p) {
            $dt = new DateTime($p['prazo']);
            $dias = (int) $hoje->diff($dt)->format('%r%a');
            $p['dias'] = $dias;

            if ($dias <= 3)      $p['badge'] = 'badge-red';
            elseif ($dias <= 7)  $p['badge'] = 'badge-yellow';
            else                 $p['badge'] = 'badge-gray';

            $p['dia']  = $dt->format('d');
            $p['mes']  = strtoupper(substr(['JAN','FEV','MAR','ABR','MAI','JUN','JUL','AGO','SET','OUT','NOV','DEZ'][(int)$dt->format('n')-1], 0, 3));
        }
        return $lista;
    }

    private function distribuicaoPapeis(PDO $pdo, array $idsTurmas): array
    {
        if (empty($idsTurmas)) return ['total' => 0, 'alunos' => 0, 'diretores' => 0, 'reps' => 0];
        $ph = implode(',', array_fill(0, count($idsTurmas), '?'));

        $stmt = $pdo->prepare("
            SELECT
                COUNT(DISTINCT tu.usuario_id) AS total,
                SUM(CASE WHEN tu.papel = 'aluno'         THEN 1 ELSE 0 END) AS alunos,
                SUM(CASE WHEN tu.papel = 'representante' THEN 1 ELSE 0 END) AS reps
            FROM turma_usuarios tu
            WHERE tu.turma_id IN ($ph) AND tu.ativo = 1
        ");
        $stmt->execute($idsTurmas);
        $linha = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['total' => 0, 'alunos' => 0, 'reps' => 0];

        // Diretores: usuários ativos em grupo_diretores cujos grupos pertencem às turmas
        $stmt = $pdo->prepare("
            SELECT COUNT(DISTINCT gd.usuario_id)
            FROM grupo_diretores gd
            INNER JOIN grupos g ON g.id = gd.grupo_id
            INNER JOIN projetos p ON p.id = g.projeto_id
            WHERE p.turma_id IN ($ph) AND gd.ativo = 1
        ");
        $stmt->execute($idsTurmas);
        $diretores = (int) $stmt->fetchColumn();

        return [
            'total'     => (int) $linha['total'],
            'alunos'    => (int) $linha['alunos'],
            'reps'      => (int) $linha['reps'],
            'diretores' => $diretores,
        ];
    }

    private function humanizar(string $acao): string
    {
        $mapa = [
            'login'                       => 'Login realizado',
            'logout'                      => 'Logout',
            'login_falha'                 => 'Tentativa de login falha',
            'cadastro'                    => 'Novo cadastro',
            'turma_criada'                => 'Turma criada',
            'turma_atualizada'            => 'Turma atualizada',
            'turma_bloqueada'             => 'Turma bloqueada',
            'turma_desbloqueada'          => 'Turma desbloqueada',
            'turma_excluida'              => 'Turma excluída',
            'projeto_criado'              => 'Projeto criado',
            'projeto_atualizado'          => 'Projeto atualizado',
            'projeto_encerrado'           => 'Projeto encerrado',
            'grupo_criado'                => 'Grupo criado',
            'grupo_atualizado'            => 'Grupo atualizado',
            'grupo_excluido'              => 'Grupo excluído',
            'grupo_membro_adicionado'     => 'Membro adicionado ao grupo',
            'grupo_membro_removido'       => 'Membro removido do grupo',
            'diretor_nomeado'             => 'Diretor nomeado',
            'diretor_removido'            => 'Diretor removido',
            'representante_nomeado'       => 'Representante nomeado',
            'representante_removido'      => 'Representante removido',
            'criterio_criado'             => 'Critério criado',
            'criterio_atualizado'         => 'Critério atualizado',
            'criterio_excluido'           => 'Critério excluído',
            'criterio_reaberto'           => 'Critério reaberto',
            'criterio_restaurado'         => 'Critério restaurado',
            'ata_criada'                  => 'Ata criada',
            'ata_atualizada'              => 'Ata atualizada',
            'ata_preenchida'              => 'Ata preenchida',
            'ata_validada'                => 'Ata validada',
            'ata_excluida'                => 'Ata excluída',
            'material_cadastrado'         => 'Material cadastrado',
            'material_comprado'           => 'Material comprado',
            'material_usado'              => 'Material usado',
            'material_excluido'           => 'Material excluído',
            'avaliacao_rep_salva'         => 'Avaliações do representante',
            'avaliacao_diretor_salva'     => 'Avaliações do diretor',
            'avaliacao_pares_salva'       => 'Avaliações por pares',
            'avaliacao_auto_salva'        => 'Autoavaliação salva',
            'avaliacao_coletiva_salva'    => 'Avaliação coletiva salva',
            'alerta_criado'               => 'Alerta enviado',
            'backup_gerado'               => 'Backup gerado',
            'backup_excluido'             => 'Backup excluído',
            'lgpd_exportacao'             => 'Exportação LGPD',
            'lgpd_exclusao_executada'     => 'Exclusão LGPD executada',
        ];
        return $mapa[$acao] ?? ucfirst(str_replace('_', ' ', $acao));
    }

    private function tempoRelativo(string $dataHora): string
    {
        $ts   = strtotime($dataHora);
        $agora = time();
        $diff = $agora - $ts;

        if ($diff < 60)         return 'agora há pouco';
        if ($diff < 3600)       return 'há ' . (int)($diff / 60) . ' min';
        if ($diff < 86400)      return 'há ' . (int)($diff / 3600) . ' h';
        if ($diff < 604800)     return 'há ' . (int)($diff / 86400) . ' dia(s)';
        return date('d/m/Y', $ts);
    }
}