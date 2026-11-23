<?php
// app/Controllers/RelatorioController.php

require_once __DIR__ . '/../Core/App.php';
require_once __DIR__ . '/../Models/Projeto.php';
require_once __DIR__ . '/../Models/Usuario.php';
require_once __DIR__ . '/../Models/TurmaUsuario.php';
require_once __DIR__ . '/../Models/ConfiguracaoConceito.php';
require_once __DIR__ . '/../Services/NotaService.php';
require_once __DIR__ . '/../Services/SnapshotService.php';
require_once __DIR__ . '/../Helpers/ViewHelper.php';
require_once __DIR__ . '/../Helpers/ExcelHelper.php';
require_once __DIR__ . '/../Helpers/pdfHelper.php';

class RelatorioController
{
    private Projeto              $projeto;
    private Usuario              $usuario;
    private TurmaUsuario         $tu;
    private ConfiguracaoConceito $cfg;
    private NotaService          $nota;
    private SnapshotService      $snap;

    public function __construct()
    {
        $this->projeto = new Projeto();
        $this->usuario = new Usuario();
        $this->tu      = new TurmaUsuario();
        $this->cfg     = new ConfiguracaoConceito();
        $this->nota    = new NotaService();
        $this->snap    = new SnapshotService();
    }

    // ============ BOLETIM INDIVIDUAL ============

    public function boletimAluno(int $projetoId)
{
    $projeto = $this->projeto->porId($projetoId);
    if (!$projeto) { $this->flash('Projeto não encontrado.'); $this->redirect('/turmas'); }

    $u = $_SESSION['usuario'];
    $this->exigirAcesso($projeto);

    $alunoId = (int) ($_GET['aluno_id'] ?? $u['id']);

    if (!$this->podeVerBoletimDe($projetoId, $alunoId)) {
        http_response_code(403);
        exit('Você não tem permissão para ver este boletim.');
    }

    $aluno = $this->usuario->findById($alunoId);
    if (!$aluno) { $this->flash('Aluno não encontrado.'); $this->redirect('/projetos/' . $projetoId); }

    $boletim = $this->snap->boletimEfetivo($projetoId, $alunoId);
    $cfg     = $this->cfg->garantir($projetoId);

    $souMaster = $u['tipo'] === 'master';
    $souRep    = $this->tu->ehRepresentante((int) $projeto['turma_id'], (int) $u['id']);

    // ---------- Atas em que o aluno participou ----------
    $pdo  = Database::getConnection();
    $stmt = $pdo->prepare("
        SELECT a.id, a.titulo, a.data_ata, a.status,
               a.horario_inicio, a.horario_fim,
               g.nome AS grupo_nome,
               g.id   AS grupo_id,
               ap.presente,
               ap.justificativa,
               u_dir.nome AS diretor_nome
        FROM ata_participantes ap
        INNER JOIN atas a ON a.id = ap.ata_id
        INNER JOIN grupos g ON g.id = a.grupo_id
        LEFT JOIN usuarios u_dir ON u_dir.id = a.diretor_id
        WHERE g.projeto_id = ? AND ap.aluno_id = ?
        ORDER BY a.data_ata DESC, a.id DESC
    ");
    $stmt->execute([$projetoId, $alunoId]);
    $atas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Para cada ata, carrega atividades e relatórios internos
    $atasDetalhes = [];
    foreach ($atas as $ata) {
        $aid = (int) $ata['id'];

        // Atividades
        $stmt = $pdo->prepare("
            SELECT aa.id, aa.nome, aa.descricao,
                   (SELECT COUNT(*) FROM participantes_atividade pa WHERE pa.atividade_id = aa.id) AS total_participantes
            FROM atividades_ata aa
            WHERE aa.ata_id = ?
            ORDER BY aa.id ASC
        ");
        $stmt->execute([$aid]);
        $atividades = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Relatórios internos em que o aluno foi mencionado OU em que participou
        $stmt = $pdo->prepare("
            SELECT r.id, r.titulo, r.conteudo, r.tipo_relatorio, r.tema,
                   r.created_at,
                   u.nome AS autor_nome,
                   (SELECT COUNT(*) FROM participantes_relatorio pr WHERE pr.relatorio_id = r.id AND pr.usuario_id = ?) AS mencionado
            FROM relatorios_ata r
            INNER JOIN usuarios u ON u.id = r.usuario_id
            WHERE r.ata_id = ?
              AND (
                r.usuario_id = ?
                OR EXISTS (
                    SELECT 1 FROM participantes_relatorio pr
                    WHERE pr.relatorio_id = r.id AND pr.usuario_id = ?
                )
              )
            ORDER BY r.created_at DESC
        ");
        $stmt->execute([$alunoId, $aid, $alunoId, $alunoId]);
        $relatorios = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $atasDetalhes[] = [
            'ata'        => $ata,
            'atividades' => $atividades,
            'relatorios' => $relatorios,
        ];
    }

    // ---------- Estatísticas de presença ----------
    $totalAtas     = count($atas);
    $totalPresente = count(array_filter($atas, fn($a) => $a['presente'] === 'sim'));
    $totalAusente  = count(array_filter($atas, fn($a) => $a['presente'] === 'nao'));
    $totalJust     = count(array_filter($atas, fn($a) => $a['presente'] === 'justificado'));

    $this->render('relatorios/boletim_aluno', [
        'projeto'        => $projeto,
        'aluno'          => $aluno,
        'boletim'        => $boletim,
        'cfg'            => $cfg,
        'isMaster'       => $souMaster,
        'isRep'          => $souRep,
        'souEu'          => $alunoId === (int) $u['id'],
        'atasDetalhes'   => $atasDetalhes,
        'totalAtas'      => $totalAtas,
        'totalPresente'  => $totalPresente,
        'totalAusente'   => $totalAusente,
        'totalJust'      => $totalJust,
    ]);
}

    public function boletimAlunoPdf(int $projetoId)
{
    $projeto = $this->projeto->porId($projetoId);
    if (!$projeto) { $this->flash('Projeto não encontrado.'); $this->redirect('/turmas'); }

    $u = $_SESSION['usuario'];
    $this->exigirAcesso($projeto);

    $alunoId = (int) ($_GET['aluno_id'] ?? $u['id']);

    if (!$this->podeVerBoletimDe($projetoId, $alunoId)) {
        http_response_code(403); exit('Sem permissão.');
    }

    $aluno   = $this->usuario->findById($alunoId);
    $boletim = $this->snap->boletimEfetivo($projetoId, $alunoId);

    // Renderiza a view como string HTML puro
    ob_start();
    extract([
        'projeto' => $projeto,
        'aluno'   => $aluno,
        'boletim' => $boletim,
        'basePath' => App::getBasePath(),
        'appName'  => App::getName(),
    ]);
    require __DIR__ . '/../Views/relatorios/boletim_aluno_pdf.php';
    $html = ob_get_clean();

    PdfHelper::gerar($html, 'boletim_' . $aluno['id'] . '.pdf', 'P', 'A4');
}


    // ============ RELATÓRIO GERAL ============

    public function geralProjeto(int $projetoId)
    {
        $projeto = $this->projeto->porId($projetoId);
        if (!$projeto) { $this->flash('Projeto não encontrado.'); $this->redirect('/turmas'); }

        $u = $_SESSION['usuario'];
        $this->exigirAcesso($projeto);

        $souMaster = $u['tipo'] === 'master';
        $souRep    = $this->tu->ehRepresentante((int) $projeto['turma_id'], (int) $u['id']);

        if (!$souMaster && !$souRep) {
            http_response_code(403);
            exit('Apenas representante ou master vê o relatório geral.');
        }

        $alunos = $this->tu->listarAlunos((int) $projeto['turma_id']);

        // Boletim de cada aluno (usa snapshot se encerrado)
        $boletins = [];
        foreach ($alunos as $a) {
            $boletins[(int) $a['id']] = $this->snap->boletimEfetivo($projetoId, (int) $a['id']);
        }

        $this->render('relatorios/geral_projeto', [
            'projeto'  => $projeto,
            'alunos'   => $alunos,
            'boletins' => $boletins,
        ]);
    }

    /**
     * Painel analítico do projeto (representante/master): indicadores, distribuição das notas,
     * média por critério, comparação entre grupos, alunos em risco, presença nas atas e ranking filtrável.
     */
    public function analitico(int $projetoId)
    {
        $projeto = $this->projeto->porId($projetoId);
        if (!$projeto) { $this->flash('Projeto não encontrado.'); $this->redirect('/turmas'); }
        $u = $_SESSION['usuario'];
        $this->exigirAcesso($projeto);
        if ($u['tipo'] !== 'master' && !$this->tu->ehRepresentante((int) $projeto['turma_id'], (int) $u['id'])) {
            http_response_code(403); exit('Apenas representante ou master vê o painel analítico.');
        }

        require_once __DIR__ . '/../Helpers/EscalaNota.php';
        require_once __DIR__ . '/../Models/Grupo.php';
        $pdo    = Database::getConnection();
        $cfg    = (new NotaService())->carregarCfg($projetoId);
        $alunos = $this->tu->listarAlunos((int) $projeto['turma_id']);

        // Grupos de cada aluno neste projeto
        $st = $pdo->prepare("SELECT ga.usuario_id, g.id, g.nome FROM grupo_alunos ga INNER JOIN grupos g ON g.id = ga.grupo_id WHERE g.projeto_id = ? AND ga.saiu_em IS NULL");
        $st->execute([$projetoId]);
        $gruposDe = [];
        foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $r) $gruposDe[(int) $r['usuario_id']][(int) $r['id']] = $r['nome'];

        // Presença nas atas dos grupos do projeto
        $st = $pdo->prepare("
            SELECT ap.aluno_id, COUNT(*) AS total, SUM(ap.presente = 1) AS presentes
            FROM ata_participantes ap INNER JOIN atas a ON a.id = ap.ata_id INNER JOIN grupos g ON g.id = a.grupo_id
            WHERE g.projeto_id = ? GROUP BY ap.aluno_id
        ");
        $st->execute([$projetoId]);
        $presenca = [];
        foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $r) $presenca[(int) $r['aluno_id']] = $r['total'] ? round($r['presentes'] / $r['total'] * 100) : null;

        $linhasAlunos = []; $porCriterio = []; $celulas = 0; $avaliadas = 0;
        foreach ($alunos as $a) {
            $aid = (int) $a['id'];
            $b = $this->snap->boletimEfetivo($projetoId, $aid);
            $feitos = 0;
            foreach ($b['linhas'] as $l) {
                $cid = (int) $l['criterio']['id'];
                $porCriterio[$cid]['criterio'] = $l['criterio'];
                $porCriterio[$cid]['notas'] = $porCriterio[$cid]['notas'] ?? [];
                $celulas++;
                if ($l['nota'] !== null) { $porCriterio[$cid]['notas'][] = (float) $l['nota']; $avaliadas++; $feitos++; }
            }
            $media = $b['media_ponderada'];
            $linhasAlunos[] = [
                'id' => $aid, 'nome' => $a['nome'], 'grupos' => $gruposDe[$aid] ?? [],
                'media' => $media, 'conceito' => $b['conceito_final'],
                'feitos' => $feitos, 'total' => count($b['linhas']),
                'presenca' => $presenca[$aid] ?? null,
                'risco' => $media !== null && $media <= (float) $cfg['limite_i_max'],
            ];
        }
        usort($linhasAlunos, fn($x, $y) => ($y['media'] ?? -1) <=> ($x['media'] ?? -1));

        // Média por critério
        $criterios = [];
        foreach ($porCriterio as $c) {
            $n = $c['notas'];
            $criterios[] = ['nome' => $c['criterio']['nome'], 'peso' => (float) $c['criterio']['peso'],
                'media' => $n ? array_sum($n) / count($n) : null, 'avaliados' => count($n)];
        }

        // Grupos: média dos membros
        $grupos = [];
        foreach ((new Grupo())->listarPorProjeto($projetoId) as $g) {
            $medias = array_values(array_filter(array_map(fn($l) => isset($l['grupos'][(int) $g['id']]) ? $l['media'] : null, $linhasAlunos), fn($m) => $m !== null));
            $grupos[] = ['nome' => $g['nome'], 'membros' => (int) $g['total_membros'],
                'media' => $medias ? array_sum($medias) / count($medias) : null];
        }
        usort($grupos, fn($x, $y) => ($y['media'] ?? -1) <=> ($x['media'] ?? -1));

        // Distribuição: menções ou faixas de 10 em 10 (no esquema)
        $comNota = array_values(array_filter($linhasAlunos, fn($l) => $l['media'] !== null));
        $dist = [];
        if (!EscalaNota::ehNumerico($cfg)) {
            foreach (['I', 'R', 'B', 'MB'] as $k) $dist[$k] = count(array_filter($comNota, fn($l) => $l['conceito'] === $k));
        } else {
            for ($f = 0; $f < 10; $f++) {
                $rot = EscalaNota::exibir($f * 10, $cfg) . '–' . EscalaNota::exibir($f === 9 ? 100 : $f * 10 + 9.99, $cfg);
                $dist[$rot] = count(array_filter($comNota, fn($l) => min(9, (int) floor($l['media'] / 10)) === $f));
            }
        }

        $this->render('relatorios/analitico', [
            'projeto'   => $projeto,
            'cfg'       => $cfg,
            'linhas'    => $linhasAlunos,
            'criterios' => $criterios,
            'grupos'    => $grupos,
            'dist'      => $dist,
            'kpi'       => [
                'media'      => $comNota ? array_sum(array_column($comNota, 'media')) / count($comNota) : null,
                'progresso'  => $celulas ? round($avaliadas / $celulas * 100) : 0,
                'risco'      => count(array_filter($linhasAlunos, fn($l) => $l['risco'])),
                'sem_nota'   => count($linhasAlunos) - count($comNota),
                'alunos'     => count($linhasAlunos),
            ],
        ]);
    }

    public function geralProjetoPdf(int $projetoId)
    {
        $projeto = $this->projeto->porId($projetoId);
        if (!$projeto) { $this->flash('Projeto não encontrado.'); $this->redirect('/turmas'); }

        $u = $_SESSION['usuario'];
        $this->exigirAcesso($projeto);

        $souMaster = $u['tipo'] === 'master';
        $souRep    = $this->tu->ehRepresentante((int) $projeto['turma_id'], (int) $u['id']);
        if (!$souMaster && !$souRep) { http_response_code(403); exit('Sem permissão.'); }

        $alunos = $this->tu->listarAlunos((int) $projeto['turma_id']);
        $boletins = [];
        foreach ($alunos as $a) {
            $boletins[(int) $a['id']] = $this->snap->boletimEfetivo($projetoId, (int) $a['id']);
        }

        $html = $this->montarGeralHtml($projeto, $alunos, $boletins);
        PdfHelper::gerar($html, 'relatorio_geral_projeto_' . $projetoId . '.pdf', 'L', 'A4');
    }

    public function geralProjetoExcel(int $projetoId)
    {
        $projeto = $this->projeto->porId($projetoId);
        if (!$projeto) { $this->flash('Projeto não encontrado.'); $this->redirect('/turmas'); }

        $u = $_SESSION['usuario'];
        $this->exigirAcesso($projeto);

        $souMaster = $u['tipo'] === 'master';
        $souRep    = $this->tu->ehRepresentante((int) $projeto['turma_id'], (int) $u['id']);
        if (!$souMaster && !$souRep) { http_response_code(403); exit('Sem permissão.'); }

        $alunos = $this->tu->listarAlunos((int) $projeto['turma_id']);

        // Monta linhas do Excel
        $linhas = [];
        foreach ($alunos as $a) {
            $boletim = $this->snap->boletimEfetivo($projetoId, (int) $a['id']);
            $linha = ['Aluno' => $a['nome']];
            foreach ($boletim['linhas'] as $l) {
                $nomeCrit = $l['criterio']['nome'];
                $linha[$nomeCrit] = $l['nota'] === null ? '' : number_format($l['nota'], 2, ',', '.');
            }
            $linha['Média'] = $boletim['media_ponderada'] === null ? '' : number_format($boletim['media_ponderada'], 2, ',', '.');
            $linha['Conceito Final'] = $boletim['conceito_final'] ?? '';
            $linhas[] = $linha;
        }

        ExcelHelper::gerar($linhas, 'relatorio_projeto_' . $projetoId . '.xls',
            'Relatório — ' . $projeto['nome']);
    }

    // ============ HELPERS ============

        /**
     * Verifica se o usuário atual pode ver o boletim do aluno $alvoId.
     * Aplica as regras de visibilidade do projeto.
     */
    private function podeVerBoletimDe(int $projetoId, int $alvoId): bool
    {
        $u = $_SESSION['usuario'] ?? null;
        if (!$u) return false;

        $meuId   = (int) $u['id'];
        $projeto = $this->projeto->porId($projetoId);
        if (!$projeto) return false;

        // Sempre pode ver o próprio
        if ($meuId === $alvoId) return true;

        // Master vê qualquer um
        if ($u['tipo'] === 'master') return true;

        // Representante vê qualquer um da turma
        if ($this->tu->ehRepresentante((int) $projeto['turma_id'], $meuId)) return true;

        // Caso contrário, respeita a configuração do projeto
        $cfg = $this->cfg->garantir($projetoId);
        $pdo = Database::getConnection();

        // É diretor ativo de algum grupo do projeto?
        $stmt = $pdo->prepare("
            SELECT 1 FROM grupo_diretores gd
            INNER JOIN grupos g ON g.id = gd.grupo_id
            WHERE g.projeto_id = ? AND gd.usuario_id = ? AND gd.ativo = 1
            LIMIT 1
        ");
        $stmt->execute([$projetoId, $meuId]);
        $souDiretor = (bool) $stmt->fetchColumn();

        $regra = $souDiretor ? $cfg['visibilidade_diretor'] : $cfg['visibilidade_aluno'];

        if ($regra === 'turma') {
            // Alvo precisa estar ativo na turma do projeto
            $stmt = $pdo->prepare("
                SELECT 1 FROM turma_usuarios
                WHERE turma_id = ? AND usuario_id = ? AND ativo = 1
                LIMIT 1
            ");
            $stmt->execute([(int) $projeto['turma_id'], $alvoId]);
            return (bool) $stmt->fetchColumn();
        }

        if ($regra === 'grupo') {
            // Alvo precisa compartilhar algum grupo comigo (como diretor ou membro)
            $stmt = $pdo->prepare("
                SELECT 1
                FROM grupo_alunos ga1
                INNER JOIN grupo_alunos ga2 ON ga2.grupo_id = ga1.grupo_id
                INNER JOIN grupos g ON g.id = ga1.grupo_id
                WHERE g.projeto_id = ?
                  AND ga1.usuario_id = ?
                  AND ga2.usuario_id = ?
                  AND ga1.saiu_em IS NULL
                  AND ga2.saiu_em IS NULL
                LIMIT 1
            ");
            $stmt->execute([$projetoId, $meuId, $alvoId]);
            if ($stmt->fetchColumn()) return true;

            // Ou o alvo está em algum grupo que eu dirijo
            $stmt = $pdo->prepare("
                SELECT 1
                FROM grupo_diretores gd
                INNER JOIN grupos g ON g.id = gd.grupo_id
                INNER JOIN grupo_alunos ga ON ga.grupo_id = g.id
                WHERE g.projeto_id = ?
                  AND gd.usuario_id = ?
                  AND gd.ativo = 1
                  AND ga.usuario_id = ?
                  AND ga.saiu_em IS NULL
                LIMIT 1
            ");
            $stmt->execute([$projetoId, $meuId, $alvoId]);
            return (bool) $stmt->fetchColumn();
        }

        // 'proprio' — já checado no início
        return false;
    }


    private function exigirAcesso(array $projeto): void
    {
        $u = $_SESSION['usuario'] ?? null;
        if (!$u) { http_response_code(403); exit('Não autenticado.'); }
        if ($u['tipo'] === 'master') return;
        if ($this->tu->estaAtivo((int) $projeto['turma_id'], (int) $u['id'])) return;
        http_response_code(403); exit('Sem acesso.');
    }

    private function montarBoletimHtml(array $projeto, array $aluno, array $boletim, bool $pdf = false): string
    {
        $titulo = htmlspecialchars($projeto['nome']) . ' — ' . htmlspecialchars($aluno['nome']);
        $congelado = !empty($boletim['congelado']);

        $html  = '<h1>' . $titulo . '</h1>';
        if ($congelado) {
            $html .= '<p><em>Boletim congelado em ' . htmlspecialchars($boletim['congelado_em'] ?? '') . '</em></p>';
        }

        $html .= '<table border="1" cellpadding="6" width="100%">';
        $html .= '<thead><tr><th>Critério</th><th>Tipo</th><th>Peso</th><th>Nota</th><th>Conceito</th></tr></thead>';
        $html .= '<tbody>';
        foreach ($boletim['linhas'] as $l) {
            $html .= '<tr>';
            $html .= '<td>' . htmlspecialchars($l['criterio']['nome']) . '</td>';
            $html .= '<td>' . htmlspecialchars($l['criterio']['tipo_avaliacao']) . '</td>';
            $html .= '<td>' . number_format((float) $l['criterio']['peso'], 2, ',', '.') . '</td>';
            $html .= '<td>' . ($l['nota'] === null ? '—' : number_format($l['nota'], 2, ',', '.')) . '</td>';
            $html .= '<td>' . ($l['conceito'] ?? '—') . '</td>';
            $html .= '</tr>';
        }
        $html .= '</tbody><tfoot>';
        $html .= '<tr><th colspan="3">Média ponderada</th><th>' .
                 ($boletim['media_ponderada'] === null ? '—' : number_format($boletim['media_ponderada'], 2, ',', '.')) .
                 '</th><th>' . ($boletim['conceito_final'] ?? '—') . '</th></tr>';
        $html .= '</tfoot></table>';

        return $html;
    }

    private function montarGeralHtml(array $projeto, array $alunos, array $boletins): string
    {
        // Pega os critérios do primeiro boletim com linhas
        $criterios = [];
        foreach ($boletins as $b) {
            foreach ($b['linhas'] as $l) {
                $criterios[(int) $l['criterio']['id']] = $l['criterio'];
            }
        }

        $html  = '<h1>Relatório Geral — ' . htmlspecialchars($projeto['nome']) . '</h1>';
        $html .= '<table border="1" cellpadding="4" width="100%">';
        $html .= '<thead><tr><th>Aluno</th>';
        foreach ($criterios as $c) {
            $html .= '<th>' . htmlspecialchars($c['nome']) . '<br><small>peso ' . number_format((float) $c['peso'], 2, ',', '.') . '</small></th>';
        }
        $html .= '<th>Média</th><th>Conceito</th></tr></thead><tbody>';

        foreach ($alunos as $a) {
            $aid = (int) $a['id'];
            $b   = $boletins[$aid] ?? null;

            $html .= '<tr><td>' . htmlspecialchars($a['nome']) . '</td>';

            $mapa = [];
            if ($b) {
                foreach ($b['linhas'] as $l) {
                    $mapa[(int) $l['criterio']['id']] = $l;
                }
            }
            foreach ($criterios as $cid => $c) {
                $nota = $mapa[$cid]['nota'] ?? null;
                $html .= '<td>' . ($nota === null ? '—' : number_format($nota, 2, ',', '.')) . '</td>';
            }
            $html .= '<td>' . ($b && $b['media_ponderada'] !== null ? number_format($b['media_ponderada'], 2, ',', '.') : '—') . '</td>';
            $html .= '<td>' . ($b['conceito_final'] ?? '—') . '</td>';
            $html .= '</tr>';
        }

        $html .= '</tbody></table>';
        return $html;
    }

    private function render(string $view, array $dados = []): void
    {
        extract($dados);
        require __DIR__ . '/../Views/' . $view . '.php';
    }

    private function redirect(string $path): void
    {
        header('Location: ' . App::getBasePath() . $path);
        exit;
    }

    private function flash(string $msg, string $tipo = 'erro'): void
    {
        $_SESSION['flash'] = ['tipo' => $tipo, 'mensagem' => $msg];
    }
}