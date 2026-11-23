<?php
// app/Views/avaliacoes/_boletim.php
// Resultado (conceito, média, progresso) + notas por critério.
// Espera: $boletim (NotaService::boletimDoAluno), e opcional $legendaParcial.
$tipoLabel = [
    'diretor' => 'Diretores do grupo', 'representante' => 'Representante', 'pares' => 'Colegas',
    'autoavaliacao' => 'Autoavaliação', 'coletiva' => 'Nota do grupo', 'misto' => 'Diretores e representante',
];
$linhas    = $boletim['linhas'] ?? [];
$avaliados = count(array_filter($linhas, fn($l) => $l['nota'] !== null));
$media     = $boletim['media_ponderada'] ?? null;
$final     = $boletim['conceito_final'] ?? null;
// Esquema de notas do projeto: em 0–10 / 0–100 mostra números no lugar das menções
require_once __DIR__ . '/../../Helpers/EscalaNota.php';
$cfgB      = $cfg ?? ($boletim['cfg'] ?? null);
$numerico  = EscalaNota::ehNumerico($cfgB);
$br        = fn($n) => number_format(EscalaNota::paraEscala((float) $n, $cfgB), EscalaNota::esquema($cfgB) === '0a100' ? 0 : 1, ',', '.');
$legendaParcial = $legendaParcial ?? 'Parcial: muda conforme as avaliações chegam';
?>

    <!-- Resultado -->
    <section class="nota-resumo <?= $final ? 'c-' . strtolower($final) : '' ?>">
        <div class="nota-final">
            <span class="nota-final-conceito num"><?= $numerico ? ($media === null ? '—' : $br($media)) : ($final ? h($final) : '—') ?></span>
            <span class="nota-final-texto">
                <strong><?= $media === null ? 'Ainda sem nota' : ($numerico ? 'Média de 0 a ' . EscalaNota::maximo($cfgB) : 'Média ' . $br($media)) ?></strong>
                <small><?= !empty($boletim['congelado']) ? 'Resultado final do projeto' : h($legendaParcial) ?></small>
            </span>
        </div>
        <div class="nota-progresso">
            <span class="num"><?= $avaliados ?>/<?= count($linhas) ?></span>
            <small>critérios já avaliados</small>
            <span class="aval-barra"><i style="width: <?= count($linhas) ? round($avaliados / count($linhas) * 100) : 0 ?>%"></i></span>
        </div>
    </section>

    <?php require __DIR__ . '/_descontos.php'; ?>

    <!-- Por critério -->
    <section class="para-fazer">
        <div class="bloco-titulo"><h2>Por critério</h2></div>
        <ul class="lista-notas">
            <?php foreach ($linhas as $l):
                $c = $l['criterio'];
                $n = $l['nota'];
                $cc = $l['conceito'];
            ?>
                <li class="<?= $cc ? 'c-' . strtolower($cc) : 'sem-nota' ?>">
                    <div class="nota-crit">
                        <strong><?= h($c['nome']) ?></strong>
                        <small><?= h($tipoLabel[$c['tipo_avaliacao']] ?? $c['tipo_avaliacao']) ?> · peso <?= rtrim(rtrim(number_format((float) $c['peso'], 2, ',', ''), '0'), ',') ?></small>
                    </div>
                    <div class="nota-barra" aria-hidden="true"><i style="width: <?= $n === null ? 0 : max(2, min(100, $n)) ?>%"></i></div>
                    <div class="nota-valor">
                        <?php if ($n === null): ?>
                            <span class="txt-mudo">aguardando</span>
                        <?php else: ?>
                            <span class="num"><?= $br($n) ?></span>
                            <?php if (!$numerico): ?><span class="conceito-chip"><?= h($cc) ?></span><?php endif; ?>
                        <?php endif; ?>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    </section>
