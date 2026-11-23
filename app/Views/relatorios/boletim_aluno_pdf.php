<?php
// app/Views/relatorios/boletim_aluno_pdf.php
// HTML para TCPDF — estilos inline, sem dependência de CSS externo.

$congelado = !empty($boletim['congelado']);
// o PDF não passa pelo cabeçalho do site, onde h() é definida
if (!function_exists('h')) { function h($s) { return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); } }
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Boletim — <?= h($aluno['nome']) ?></title>
<style>
    body { font-family: helvetica, sans-serif; color: #333; font-size: 11px; }
    h1 { font-size: 18px; color: #1d4ed8; margin: 0 0 4px; }
    .subtitulo { color: #666; font-size: 12px; margin: 0 0 18px; }
    .congelado {
        background: #fdecef;
        border-left: 3px solid #e46d78;
        color: #b13b47;
        padding: 8px 12px;
        margin-bottom: 14px;
        font-size: 11px;
    }
    table { width: 100%; border-collapse: collapse; margin-top: 10px; }
    th {
        background: #f0f0f5;
        color: #555;
        font-size: 10px;
        text-transform: uppercase;
        letter-spacing: .5px;
        text-align: left;
        padding: 8px;
        border-bottom: 1px solid #ddd;
    }
    td {
        padding: 8px;
        border-bottom: 1px solid #eee;
        font-size: 11px;
        color: #222;
    }
    tfoot th {
        background: #f7f7fb;
        border-top: 2px solid #ccc;
        font-size: 12px;
        padding: 12px 8px;
    }
    .num { text-align: right; font-weight: bold; }
    .centro { text-align: center; }
    .media { color: #1d4ed8; font-size: 16px; font-weight: bold; }
    .rodape {
        margin-top: 24px;
        padding-top: 12px;
        border-top: 1px solid #eee;
        color: #999;
        font-size: 9px;
        text-align: center;
    }
</style>
</head>
<body>

<h1>Boletim Individual</h1>
<p class="subtitulo">
    <strong><?= h($aluno['nome']) ?></strong> — <?= h($aluno['email']) ?><br>
    Projeto: <?= h($projeto['nome']) ?> &nbsp;|&nbsp; Turma: <?= h($projeto['turma_nome']) ?>
</p>

<?php if ($congelado): ?>
    <div class="congelado">
        <strong>Boletim congelado.</strong>
        Este projeto foi encerrado em <?= h($boletim['congelado_em'] ?? '') ?> e as notas não mudam mais.
    </div>
<?php endif; ?>

<?php if (empty($boletim['linhas'])): ?>
    <p>Nenhum critério definido neste projeto.</p>
<?php else: ?>
    <table>
        <thead>
            <tr>
                <th>Critério</th>
                <th class="centro">Tipo</th>
                <th class="num">Peso</th>
                <th class="num">Nota</th>
                <th class="centro">Conceito</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($boletim['linhas'] as $l): ?>
                <tr>
                    <td><strong><?= h($l['criterio']['nome']) ?></strong></td>
                    <td class="centro"><?= h($l['criterio']['tipo_avaliacao']) ?></td>
                    <td class="num"><?= number_format((float) $l['criterio']['peso'], 2, ',', '.') ?></td>
                    <td class="num">
                        <?= $l['nota'] === null ? '—' : number_format($l['nota'], 2, ',', '.') ?>
                    </td>
                    <td class="centro">
                        <?= $l['conceito'] !== null ? h($l['conceito']) : '—' ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <th colspan="3">Média ponderada</th>
                <th class="num media">
                    <?= $boletim['media_ponderada'] === null
                        ? '—'
                        : number_format($boletim['media_ponderada'], 2, ',', '.') ?>
                </th>
                <th class="centro media">
                    <?= h($boletim['conceito_final'] ?? '—') ?>
                </th>
            </tr>
            <?php foreach ($boletim['descontos'] ?? [] as $dsc): ?>
                <tr>
                    <td colspan="5">Desconto de <?= number_format((float) $dsc['pontos'], 1, ',', '.') ?> ponto(s) já incluído na média: <?= h($dsc['motivo']) ?>.</td>
                </tr>
            <?php endforeach; ?>
        </tfoot>
    </table>
<?php endif; ?>

<div class="rodape">
    Gerado em <?= date('d/m/Y H:i') ?> — <?= h($appName) ?>
</div>

</body>
</html>