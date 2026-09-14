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

        // Aluno comum só pode ver o próprio
        $souMaster = $u['tipo'] === 'master';
        $souRep    = $this->tu->ehRepresentante((int) $projeto['turma_id'], (int) $u['id']);

        if (!$souMaster && !$souRep && $alunoId !== (int) $u['id']) {
            http_response_code(403);
            exit('Aluno só vê o próprio boletim.');
        }

        $aluno = $this->usuario->findById($alunoId);
        if (!$aluno) { $this->flash('Aluno não encontrado.'); $this->redirect('/projetos/' . $projetoId); }

        $boletim = $this->snap->boletimEfetivo($projetoId, $alunoId);
        $cfg     = $this->cfg->garantir($projetoId);

        $this->render('relatorios/boletim_aluno', [
            'projeto'  => $projeto,
            'aluno'    => $aluno,
            'boletim'  => $boletim,
            'cfg'      => $cfg,
            'isMaster' => $souMaster,
            'isRep'    => $souRep,
            'souEu'    => $alunoId === (int) $u['id'],
        ]);
    }

    public function boletimAlunoPdf(int $projetoId)
    {
        $projeto = $this->projeto->porId($projetoId);
        if (!$projeto) { $this->flash('Projeto não encontrado.'); $this->redirect('/turmas'); }

        $u = $_SESSION['usuario'];
        $this->exigirAcesso($projeto);

        $alunoId = (int) ($_GET['aluno_id'] ?? $u['id']);

        $souMaster = $u['tipo'] === 'master';
        $souRep    = $this->tu->ehRepresentante((int) $projeto['turma_id'], (int) $u['id']);

        if (!$souMaster && !$souRep && $alunoId !== (int) $u['id']) {
            http_response_code(403); exit('Sem permissão.');
        }

        $aluno   = $this->usuario->findById($alunoId);
        $boletim = $this->snap->boletimEfetivo($projetoId, $alunoId);

        $html = $this->montarBoletimHtml($projeto, $aluno, $boletim, true);

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