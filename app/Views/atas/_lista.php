<?php
// app/Views/atas/_lista.php
// Lista de atas (representante, diretor e aluno).
// Espera: $listaAtas (array), $basePath, $projeto e $acaoAta (fn(array $ata): array de [rótulo, url, primário?]).

$mesesAta  = ['JAN','FEV','MAR','ABR','MAI','JUN','JUL','AGO','SET','OUT','NOV','DEZ'];
$statusAta = [
    'pendente'   => ['Aguardando diretor', 'pendente'],
    'preenchida' => ['Aguardando validação', 'preenchida'],
    'revisada'   => ['Validada', 'revisada'],
];
?>
<ul class="lista-tarefas lista-atas">
    <?php foreach ($listaAtas as $a):
        $ts = strtotime($a['data_ata'] ?? '') ?: null;
        [$stTexto, $stClasse] = $statusAta[$a['status']] ?? [$a['status'], ''];
        $atrasada = $a['status'] === 'pendente' && !empty($a['prazo_preenchimento']) && strtotime($a['prazo_preenchimento']) < strtotime('today');
    ?>
        <li data-status="<?= h($a['status']) ?>">
            <span class="tarefa-data num"><?= $ts ? date('d', $ts) : '—' ?><small><?= $ts ? $mesesAta[(int) date('n', $ts) - 1] : '' ?></small></span>
            <div class="tarefa-info">
                <strong><a class="ata-link" href="<?= $basePath ?>/atas/<?= (int) $a['id'] ?>"><?= h($a['titulo']) ?></a></strong>
                <small>
                    Grupo <?= h($a['grupo_nome'] ?? '—') ?>
                    <?php if (!empty($a['diretor_nome'])): ?> · <?= h($a['diretor_nome']) ?><?php endif; ?>
                    <?php if ($a['status'] === 'pendente' && !empty($a['prazo_preenchimento'])): ?>
                        · <span class="<?= $atrasada ? 'txt-alerta' : '' ?>">preencher até <?= ViewHelper::data($a['prazo_preenchimento']) ?><?= $atrasada ? ' (atrasada)' : '' ?></span>
                    <?php endif; ?>
                    <?php if (isset($a['presente'])): ?>
                        · você: <span class="<?= $a['presente'] === 'nao' ? 'txt-alerta' : '' ?>"><?= ['sim' => 'presente', 'nao' => 'ausente', 'justificado' => 'falta justificada'][$a['presente']] ?? '—' ?></span>
                    <?php endif; ?>
                </small>
            </div>
            <span class="ata-status-chip <?= $stClasse ?>"><?= $stTexto ?></span>
            <span class="ata-acoes-linha">
                <?php foreach ($acaoAta($a) as [$rotulo, $url, $primario]): ?>
                    <a class="tarefa-acao<?= $primario ? ' primaria' : '' ?>" href="<?= h($url) ?>"><?= h($rotulo) ?></a>
                <?php endforeach; ?>
            </span>
        </li>
    <?php endforeach; ?>
</ul>
