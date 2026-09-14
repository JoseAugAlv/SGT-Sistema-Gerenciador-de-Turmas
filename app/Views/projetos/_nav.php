<?php
// app/Views/projetos/_nav.php
// Espera $projeto no escopo. Renderiza o nav interno do projeto.
// Detecta a seção ativa pela URL, funcionando tanto em /projetos/... quanto em /grupos/..., /etapas/..., etc.

if (empty($projeto) || empty($projeto['id'])) {
    return; // fora do contexto de projeto — não renderiza nada
}

$pid = (int) $projeto['id'];
$uri = $_SERVER['REQUEST_URI'] ?? '';

$secaoAtual = 'etapas';
if (strpos($uri, '/grupos')     !== false) $secaoAtual = 'grupos';
elseif (strpos($uri, '/criterios')  !== false) $secaoAtual = 'criterios';
elseif (strpos($uri, '/avaliacoes') !== false) $secaoAtual = 'avaliacoes';
elseif (strpos($uri, '/conceitos')  !== false) $secaoAtual = 'conceitos';
elseif (strpos($uri, '/relatorios') !== false) $secaoAtual = 'relatorios';
elseif (strpos($uri, '/etapas')     !== false) $secaoAtual = 'etapas';
// Qualquer outra URL dentro de projeto cai em 'etapas' por padrão

$abas = [
    'etapas'     => ['icon' => 'fas fa-list-ol',         'label' => 'Etapas',     'url' => "/projetos/{$pid}"],
    'grupos'     => ['icon' => 'fas fa-users',           'label' => 'Grupos',     'url' => "/projetos/{$pid}/grupos"],
    'criterios'  => ['icon' => 'fas fa-clipboard-check', 'label' => 'Critérios',  'url' => "/projetos/{$pid}/criterios"],
    'avaliacoes' => ['icon' => 'fas fa-star',            'label' => 'Avaliações', 'url' => "/projetos/{$pid}/avaliacoes"],
    'conceitos'  => ['icon' => 'fas fa-sliders',         'label' => 'Conceitos',  'url' => "/projetos/{$pid}/conceitos"],
    'relatorios' => ['icon' => 'fas fa-file-alt',        'label' => 'Relatórios', 'url' => "/projetos/{$pid}/relatorios"],
];
?>

<nav>
    <?php foreach ($abas as $chave => $aba): ?>
        <?php if ($chave === $secaoAtual): ?>
            <strong><i class="<?= h($aba['icon']) ?>"></i> <?= h($aba['label']) ?></strong>
        <?php else: ?>
            <a href="<?= $basePath . $aba['url'] ?>"><i class="<?= h($aba['icon']) ?>"></i> <?= h($aba['label']) ?></a>
        <?php endif; ?>
        <?php if ($chave !== 'relatorios'): ?> — <?php endif; ?>
    <?php endforeach; ?>
</nav>

<hr>