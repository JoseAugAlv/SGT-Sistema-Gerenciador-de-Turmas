<?php
// app/Views/materiais/_nav.php
// Abas do setor de materiais: Estoque · Solicitações · Histórico. Espera: $projeto, $basePath.
require_once __DIR__ . '/../../Models/SolicitacaoMaterial.php';

$matPid     = (int) $projeto['id'];
$matAbertas = (new SolicitacaoMaterial())->contarAbertas($matPid);
$matUri     = $_SERVER['REQUEST_URI'] ?? '';
$matAtual   = str_contains($matUri, '/solicitacoes') ? 'sol' : (str_contains($matUri, '/movimentacoes') ? 'hist' : 'estoque');
$matAbas    = [
    'estoque' => ['Estoque',      '/materiais',               'fa-boxes-stacked'],
    'sol'     => ['Solicitações', '/materiais/solicitacoes',  'fa-hand-holding-heart'],
    'hist'    => ['Histórico',    '/materiais/movimentacoes', 'fa-clock-rotate-left'],
];
?>
<nav class="mat-nav" aria-label="Seções de materiais">
    <?php foreach ($matAbas as $chave => [$rotulo, $url, $icone]): ?>
        <a href="<?= $basePath ?>/projetos/<?= $matPid . $url ?>" class="<?= $chave === $matAtual ? 'ativa' : '' ?>"<?= $chave === $matAtual ? ' aria-current="page"' : '' ?>>
            <i class="fas <?= $icone ?>"></i> <?= $rotulo ?>
            <?php if ($chave === 'sol' && $matAbertas > 0): ?><span class="mat-nav-conta"><?= $matAbertas ?></span><?php endif; ?>
        </a>
    <?php endforeach; ?>
</nav>
