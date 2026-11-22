<?php
$cssPagina = ['projetos.css', 'avaliacoes.css'];

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';

// Primeiro o que ainda falta responder, depois o que já foi feito
$aFazer = array_values(array_filter($criterios, fn($c) => ($pendencias[(int) $c['id']] ?? null) !== null));
$feitos = array_values(array_filter($criterios, fn($c) => ($pendencias[(int) $c['id']] ?? null) === null));
// Obrigatórios primeiro, depois pelo prazo mais próximo
usort($aFazer, fn($a, $b) => [(int) empty($a['obrigatorio']), $a['prazo_avaliacao'] ?? '9999'] <=> [(int) empty($b['obrigatorio']), $b['prazo_avaliacao'] ?? '9999']);
$linkDo = function (array $c) use ($basePath, $projeto): ?string {
    $pp = $basePath . '/projetos/' . (int) $projeto['id'];
    return match ($c['tipo_avaliacao']) {
        'pares'         => $pp . '/avaliacoes/pares?criterio=' . (int) $c['id'],
        'autoavaliacao' => $pp . '/avaliacoes/auto',
        default         => null,
    };
};
?>

<section class="hero-section">
    <div>
        <h1>Avaliações</h1>
        <p class="hero-subtitle">
            <?= h($projeto['nome']) ?> ·
            <a href="<?= $basePath ?>/turmas/<?= (int) $projeto['turma_id'] ?>"><?= h($projeto['turma_nome']) ?></a>
        </p>
    </div>
</section>

<?php require __DIR__ . '/../projetos/_nav.php'; ?>
<?php require __DIR__ . '/_nav.php'; ?>

<section class="para-fazer">
    <div class="bloco-titulo">
        <h2>O que falta fazer</h2>
        <?php if ($feitos): ?><span class="bloco-link txt-mudo"><?= count($feitos) ?> concluído(s)</span><?php endif; ?>
    </div>
    <?php if (empty($criterios)): ?>
        <div class="lista-vazia"><i class="fas fa-clipboard-check"></i><span>Nenhum critério aberto neste projeto.</span></div>
    <?php elseif (empty($aFazer)): ?>
        <div class="lista-vazia"><i class="fas fa-circle-check"></i><span>Tudo em dia. Você já respondeu todas as avaliações abertas.</span></div>
    <?php else: ?>
        <ul class="lista-tarefas">
            <?php foreach ($aFazer as $c): $link = $linkDo($c); ?>
                <li>
                    <span class="tarefa-icone"><i class="fas <?= $c['tipo_avaliacao'] === 'pares' ? 'fa-user-group' : 'fa-user-check' ?>"></i></span>
                    <div class="tarefa-info">
                        <strong><?= h($c['nome']) ?></strong>
                        <small>
                            <?php if (!empty($c['obrigatorio'])): ?><span class="txt-alerta">Obrigatório</span> · <?php endif; ?>
                            <?= h($pendencias[(int) $c['id']]) ?>
                            <?php if (!empty($c['prazo_avaliacao'])): ?> · até <?= h(date('d/m H:i', strtotime($c['prazo_avaliacao']))) ?><?php endif; ?>
                        </small>
                    </div>
                    <?php if ($link): ?><a class="tarefa-acao" href="<?= h($link) ?>">Avaliar</a><?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</section>

<?php if (!empty($boletim['linhas'])): ?>
    <div class="bloco-titulo"><h2>Como estou indo</h2></div>
    <?php require __DIR__ . '/_boletim.php'; ?>
<?php endif; ?>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
