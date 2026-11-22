<?php
// app/Views/tutorial/_nav.php
// Navegação entre os tutoriais por papel.
$uri = $_SERVER['REQUEST_URI'] ?? '';

$secao = 'index';
if (strpos($uri, '/tutorial/aluno')         !== false) $secao = 'aluno';
if (strpos($uri, '/tutorial/diretor')       !== false) $secao = 'diretor';
if (strpos($uri, '/tutorial/representante') !== false) $secao = 'representante';
if (strpos($uri, '/tutorial/master')        !== false) $secao = 'master';
if (strpos($uri, '/tutorial/exemplos')      !== false) $secao = 'exemplos';
if (strpos($uri, '/tutorial/exemplo/')      !== false) $secao = 'exemplos';

$abas = [
    'index'         => ['url' => '/tutorial',              'label' => 'Início',        'icon' => 'fas fa-house'],
    'aluno'         => ['url' => '/tutorial/aluno',        'label' => 'Aluno',         'icon' => 'fas fa-user-graduate'],
    'diretor'       => ['url' => '/tutorial/diretor',      'label' => 'Diretor',       'icon' => 'fas fa-crown'],
    'representante' => ['url' => '/tutorial/representante','label' => 'Representante', 'icon' => 'fas fa-user-tie'],
    'exemplos'      => ['url' => '/tutorial/exemplos',     'label' => 'Exemplos',      'icon' => 'fas fa-images'],
];
?>
<nav class="tut-nav">
    <?php foreach ($abas as $chave => $aba): ?>
        <?php if ($chave === $secao): ?>
            <strong><i class="<?= h($aba['icon']) ?>"></i> <?= h($aba['label']) ?></strong>
        <?php else: ?>
            <a href="<?= $basePath . $aba['url'] ?>">
                <i class="<?= h($aba['icon']) ?>"></i> <?= h($aba['label']) ?>
            </a>
        <?php endif; ?>
    <?php endforeach; ?>
</nav>