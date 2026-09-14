<?php
$uri = $_SERVER['REQUEST_URI'] ?? '';
$secao = 'rep';
if (strpos($uri, '/diretor') !== false) $secao = 'dir';
if (strpos($uri, '/minhas')  !== false) $secao = 'minhas';
?>

<nav>
    <?php if ($secao === 'rep'): ?><strong><?php else: ?><a href="<?= $basePath ?>/projetos/<?= (int) $projeto['id'] ?>/avaliacoes/representante"><?php endif; ?>
        <i class="fas fa-user-tie"></i> Representante
    <?php if ($secao === 'rep'): ?></strong><?php else: ?></a><?php endif; ?>
    —
    <?php if ($secao === 'dir'): ?><strong><?php else: ?><a href="<?= $basePath ?>/projetos/<?= (int) $projeto['id'] ?>/avaliacoes/diretor"><?php endif; ?>
        <i class="fas fa-crown"></i> Diretor
    <?php if ($secao === 'dir'): ?></strong><?php else: ?></a><?php endif; ?>
    —
    <?php if ($secao === 'minhas'): ?><strong><?php else: ?><a href="<?= $basePath ?>/projetos/<?= (int) $projeto['id'] ?>/avaliacoes/minhas"><?php endif; ?>
        <i class="fas fa-graduation-cap"></i> Minhas Notas
    <?php if ($secao === 'minhas'): ?></strong><?php else: ?></a><?php endif; ?>
</nav>
<hr>