<?php
// app/Views/projetos/_lista.php
// Lista de projetos (usada em projetos/index e projetos/geral).
// Espera: $projetos (array), $basePath.

$modosLabel = ['etapa' => 'Avaliação por etapa', 'geral' => 'Avaliação geral'];
$hojeLista  = new DateTime('today');
?>
<ul class="lista-tarefas lista-projetos">
    <?php foreach ($projetos as $p):
        $prazoTxt = 'sem prazo';
        $prazoCls = '';
        if (!empty($p['prazo'])) {
            $dtPrazo = new DateTime($p['prazo']);
            $dias = (int) $hojeLista->diff($dtPrazo)->format('%r%a');
            $prazoTxt = 'prazo ' . $dtPrazo->format('d/m/Y');
            if (!$p['encerrado']) {
                if ($dias < 0)       { $prazoTxt .= ' · vencido'; $prazoCls = 'txt-alerta'; }
                elseif ($dias === 0) { $prazoTxt .= ' · hoje';    $prazoCls = 'txt-alerta'; }
                elseif ($dias <= 7)  { $prazoTxt .= ' · em ' . $dias . ' dia(s)'; }
            }
        }
    ?>
        <li class="<?= $p['encerrado'] ? 'encerrado' : '' ?>">
            <span class="tarefa-icone"><i class="fas <?= $p['encerrado'] ? 'fa-lock' : 'fa-diagram-project' ?>"></i></span>
            <div class="tarefa-info">
                <strong><?= h($p['nome']) ?></strong>
                <small>
                    <?= $p['encerrado'] ? 'Encerrado' : 'Em andamento' ?> ·
                    <?= h($modosLabel[$p['modo_avaliacao']] ?? $p['modo_avaliacao']) ?> ·
                    <?= (int) ($p['total_etapas'] ?? 0) ?> etapa(s) ·
                    <span class="<?= $prazoCls ?>"><?= h($prazoTxt) ?></span>
                </small>
            </div>
            <a class="tarefa-acao" href="<?= $basePath ?>/projetos/<?= (int) $p['id'] ?>">Abrir</a>
        </li>
    <?php endforeach; ?>
</ul>
