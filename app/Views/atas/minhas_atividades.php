<?php
$cssPagina = ['atas.css'];
$tituloPagina = 'Minhas atividades — ' . App::getName();

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';

// Agrupa por projeto
$porProjeto = [];
foreach ($atividades as $a) {
    $porProjeto[(int) $a['projeto_id']]['nome']  = $a['projeto_nome'];
    $porProjeto[(int) $a['projeto_id']]['turma'] = $a['turma_nome'];
    $porProjeto[(int) $a['projeto_id']]['itens'][] = $a;
}
$mencoes  = count(array_filter($atividades, fn($a) => (int) $a['mencionado'] === 1));
$minhas   = count(array_filter($atividades, fn($a) => (int) $a['autor_id'] === $meuId));
$statusAta = ['pendente' => 'em preenchimento', 'preenchida' => 'aguardando validação', 'revisada' => 'validada'];
?>

<section class="hero-section">
    <div>
        <h1>Minhas atividades</h1>
        <p class="hero-subtitle">Tudo o que foi registrado nas atas com o seu nome, em todos os projetos.</p>
    </div>
</section>

<?php if (empty($atividades)): ?>
    <div class="lista-vazia">
        <i class="fas fa-clipboard-user"></i>
        <span>Você ainda não foi mencionado em nenhuma atividade. Quando alguém registrar você numa ata, ela aparece aqui e você recebe um aviso.</span>
    </div>
<?php else: ?>
    <section class="grade-numeros" aria-label="Resumo">
        <div class="grade-celula"><span class="num"><?= count($atividades) ?></span><small>atividades</small></div>
        <div class="grade-celula"><span class="num"><?= $mencoes ?></span><small>em que você foi mencionado</small></div>
        <div class="grade-celula"><span class="num"><?= $minhas ?></span><small>registradas por você</small></div>
    </section>

    <?php foreach ($porProjeto as $proj): ?>
        <section class="para-fazer">
            <div class="bloco-titulo"><h2><?= h($proj['nome']) ?> · <?= h($proj['turma']) ?></h2></div>
            <ul class="lista-atividades">
                <?php foreach ($proj['itens'] as $a): ?>
                    <li id="atividade-<?= (int) $a['id'] ?>">
                        <div class="atv-topo">
                            <strong><?= h($a['nome']) ?></strong>
                            <span class="atv-data"><?= ViewHelper::data($a['data_ata']) ?></span>
                        </div>
                        <?php if (!empty($a['descricao'])): ?><p><?= nl2br(h($a['descricao'])) ?></p><?php endif; ?>
                        <small>
                            Ata <a href="<?= $basePath ?>/atas/<?= (int) $a['ata_id'] ?>"><?= h($a['ata_titulo']) ?></a>
                            · Grupo <?= h($a['grupo_nome']) ?>
                            · <?= (int) $a['autor_id'] === $meuId ? 'registrada por você' : 'registrada por ' . h($a['autor_nome'] ?? '—') ?>
                            · <?= $statusAta[$a['ata_status']] ?? h($a['ata_status']) ?>
                        </small>
                    </li>
                <?php endforeach; ?>
            </ul>
        </section>
    <?php endforeach; ?>
<?php endif; ?>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
