<?php
// app/Controllers/PortfolioController.php
// Relatório final do projeto em PDF, estilo portfólio (TCPDF, várias páginas): capa, sobre/etapas/critérios,
// uma página por grupo, desempenho da turma (só distribuição, sem notas individuais) e orçamento.
// Qualquer membro da turma baixa (não há nota de ninguém no documento).
require_once __DIR__ . '/../Core/App.php';
require_once __DIR__ . '/../Models/Projeto.php';
require_once __DIR__ . '/../Models/TurmaUsuario.php';
require_once __DIR__ . '/../Services/PortfolioService.php';
require_once __DIR__ . '/../../vendor/autoload.php';

class PortfolioController
{
    public function pdf(int $projetoId)
    {
        $projeto = (new Projeto())->porId($projetoId);
        if (!$projeto) { http_response_code(404); exit('Projeto não encontrado.'); }
        $u = $_SESSION['usuario'];
        if ($u['tipo'] !== 'master' && !(new TurmaUsuario())->estaAtivo((int) $projeto['turma_id'], (int) $u['id'])) { http_response_code(403); exit('Sem acesso.'); }

        $d = (new PortfolioService())->montar($projeto);
        $cor = preg_match('/^#[0-9a-fA-F]{6}$/', (string) ($d['turma']['cor_primaria'] ?? '')) ? $d['turma']['cor_primaria'] : '#3355e0';
        $rgb = sscanf($cor, '#%02x%02x%02x');
        $e = fn($t) => htmlspecialchars((string) $t, ENT_QUOTES, 'UTF-8');
        $data = fn($x) => $x ? date('d/m/Y', strtotime($x)) : '';
        $brl = fn($v) => 'R$ ' . number_format((float) $v, 2, ',', '.');

        $pdf = new \TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->SetCreator(App::getName()); $pdf->SetAuthor(App::getName());
        $pdf->SetTitle('Portfólio · ' . $projeto['nome']);
        $pdf->setPrintHeader(false); $pdf->setPrintFooter(false);
        $pdf->SetMargins(18, 18, 18); $pdf->SetAutoPageBreak(true, 18);
        $pdf->SetFont('helvetica', '', 10);

        // ---------- capa
        $pdf->AddPage();
        $pdf->SetFillColor($rgb[0], $rgb[1], $rgb[2]);
        $pdf->Rect(0, 0, 210, 120, 'F');
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetXY(18, 34); $pdf->SetFont('helvetica', 'B', 11); $pdf->Cell(0, 6, mb_strtoupper('Portfólio do projeto'), 0, 1);
        $pdf->SetX(18); $pdf->SetFont('helvetica', 'B', 30); $pdf->MultiCell(174, 13, $projeto['nome'], 0, 'L');
        $pdf->SetX(18); $pdf->SetFont('helvetica', '', 13);
        $periodo = $data($projeto['created_at']) . ' a ' . ($projeto['encerrado'] ? $data($projeto['encerrado_em']) : 'em andamento');
        $pdf->Cell(0, 8, ($d['turma']['nome'] ?? '') . '  ·  ' . $periodo, 0, 1);
        $pdf->SetTextColor(15, 18, 34);
        $nums = [['Alunos', $d['numeros']['alunos']], ['Grupos', $d['numeros']['grupos']], ['Atas', $d['numeros']['atas']], ['Tarefas feitas', $d['numeros']['tarefas']], ['Critérios', $d['numeros']['criterios']]];
        $x = 18; $y = 136; $w = 174 / count($nums);
        foreach ($nums as [$rot, $v]) {
            $pdf->SetXY($x, $y); $pdf->SetFont('helvetica', 'B', 26); $pdf->SetTextColor($rgb[0], $rgb[1], $rgb[2]); $pdf->Cell($w, 12, (string) $v, 0, 2, 'C');
            $pdf->SetFont('helvetica', '', 9); $pdf->SetTextColor(100, 103, 116); $pdf->Cell($w, 5, $rot, 0, 0, 'C');
            $x += $w;
        }
        if ($projeto['descricao']) {
            $pdf->SetXY(18, 168); $pdf->SetTextColor(15, 18, 34); $pdf->SetFont('helvetica', 'B', 13); $pdf->Cell(0, 8, 'Sobre o projeto', 0, 1);
            $pdf->SetX(18); $pdf->SetFont('helvetica', '', 11); $pdf->MultiCell(174, 6, $projeto['descricao'], 0, 'L');
        }
        $pdf->SetXY(18, 270); $pdf->SetFont('helvetica', '', 8); $pdf->SetTextColor(148, 151, 161);
        $pdf->Cell(0, 5, 'Gerado em ' . date('d/m/Y H:i') . ' pelo ' . App::getName(), 0, 0, 'L');

        $estilo = '<style>h2{color:' . $cor . ';font-size:16pt} h3{font-size:12pt;color:#0f1222} p,td,li{font-size:10pt;color:#334155}
                   .mudo{color:#646774;font-size:9pt} th{background-color:#eceef6;font-weight:bold;font-size:9pt;color:#0f1222} td{border-bottom:1px solid #e3e5ee}</style>';

        // ---------- etapas e critérios
        if ($d['etapas'] || $d['criterios']) {
            $pdf->AddPage();
            $h = $estilo;
            if ($d['etapas']) {
                $h .= '<h2>Linha do tempo</h2><table cellpadding="6">';
                foreach ($d['etapas'] as $i => $et) {
                    $h .= '<tr><td width="12%" style="color:' . $cor . ';font-weight:bold;font-size:14pt">' . ($i + 1) . '</td><td width="88%"><b>' . $e($et['nome']) . '</b>'
                        . (($et['data_inicio'] || $et['data_fim']) ? '<br><span class="mudo">' . $data($et['data_inicio']) . ($et['data_fim'] ? ' a ' . $data($et['data_fim']) : '') . '</span>' : '')
                        . ($et['descricao'] ? '<br>' . nl2br($e($et['descricao'])) : '') . '</td></tr>';
                }
                $h .= '</table>';
            }
            if ($d['criterios']) {
                $h .= '<h2>Como o projeto foi avaliado</h2><table cellpadding="5"><tr><th width="70%">Critério</th><th width="15%">Peso</th><th width="15%">Avaliação</th></tr>';
                foreach ($d['criterios'] as $c) $h .= '<tr><td>' . $e($c['nome']) . '</td><td>' . $e(rtrim(rtrim(number_format((float) $c['peso'], 2, ',', ''), '0'), ',')) . '</td><td class="mudo">' . $e($c['tipo_avaliacao']) . '</td></tr>';
                $h .= '</table>';
            }
            $pdf->writeHTML($h, true, false, true, false, '');
        }

        // ---------- grupos
        foreach ($d['grupos'] as $g) {
            $pdf->AddPage();
            $h = $estilo . '<h2>' . $e($g['nome']) . '</h2>';
            $h .= '<p><b>Integrantes:</b> ' . ($g['membros'] ? $e(implode(', ', $g['membros'])) : '—') . '</p>';
            if ($g['diretores']) $h .= '<p><b>Direção:</b> ' . $e(implode(', ', $g['diretores'])) . '</p>';
            if ($g['tarefas_total']) $h .= '<p><b>Tarefas:</b> ' . $g['tarefas_feitas'] . ' de ' . $g['tarefas_total'] . ' concluídas no quadro do grupo.</p>';
            if ($g['atas']) {
                $h .= '<h3>Registro das reuniões (atas)</h3><table cellpadding="5"><tr><th width="20%">Data</th><th width="60%">Ata</th><th width="20%">Atividades</th></tr>';
                foreach ($g['atas'] as $a) $h .= '<tr><td>' . $data($a['data_ata']) . '</td><td>' . $e($a['titulo']) . '</td><td>' . (int) $a['atividades'] . '</td></tr>';
                $h .= '</table>';
            } else {
                $h .= '<p class="mudo">Nenhuma ata registrada.</p>';
            }
            $pdf->writeHTML($h, true, false, true, false, '');
        }

        // ---------- desempenho e orçamento
        $pdf->AddPage();
        $h = $estilo . '<h2>Desempenho da turma</h2>';
        if ($d['distribuicao']) {
            $tot = array_sum($d['distribuicao']);
            $h .= '<p>' . $d['avaliados'] . ' de ' . $d['alunos'] . ' alunos com conceito final' . ($d['media'] !== null ? ' · média geral <b>' . number_format($d['media'], 2, ',', '') . '</b>' : '') . '.</p><table cellpadding="5">';
            foreach ($d['distribuicao'] as $conc => $qtd) {
                $pct = $tot ? round($qtd / $tot * 100) : 0;
                $h .= '<tr><td width="15%"><b>' . $e($conc) . '</b></td><td width="70%"><table cellpadding="0"><tr><td width="' . max(1, $pct) . '%" style="background-color:' . $cor . '">&nbsp;</td><td width="' . (100 - max(1, $pct)) . '%"></td></tr></table></td><td width="15%">' . $qtd . ' (' . $pct . '%)</td></tr>';
            }
            $h .= '</table><p class="mudo">Sem notas individuais: o boletim de cada aluno fica no sistema.</p>';
        } else {
            $h .= '<p class="mudo">As notas ainda não foram lançadas.</p>';
        }
        $o = $d['orcamento'];
        if ($o['previsto'] > 0 || $o['arrecadado'] > 0 || $o['gasto'] > 0) {
            $h .= '<h2>Orçamento</h2><table cellpadding="6"><tr><th>Previsto</th><th>Arrecadado</th><th>Gasto</th><th>Saldo</th></tr><tr><td>' . $brl($o['previsto']) . '</td><td>' . $brl($o['arrecadado'])
                . '</td><td>' . $brl($o['gasto']) . '</td><td>' . $brl($o['arrecadado'] - $o['gasto']) . '</td></tr></table>';
        }
        $pdf->writeHTML($h, true, false, true, false, '');

        if (ob_get_length()) ob_end_clean();
        $nome = 'portfolio-' . preg_replace('/[^a-z0-9]+/i', '-', iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $projeto['nome']) ?: 'projeto') . '.pdf';
        $pdf->Output(strtolower(trim($nome, '-')), isset($_GET['baixar']) ? 'D' : 'I');
        exit;
    }
}
