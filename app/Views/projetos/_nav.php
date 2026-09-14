<?php
// app/Views/projetos/_nav.php

if (empty($projeto) || empty($projeto['id'])) {
    return;
}

require_once __DIR__ . '/../../Config/database.php';
require_once __DIR__ . '/../../Models/TurmaUsuario.php';

$pid   = (int) $projeto['id'];
$uri   = $_SERVER['REQUEST_URI'] ?? '';
$u     = $_SESSION['usuario'] ?? null;

$isMaster = $u && $u['tipo'] === 'master';
$turmaId  = (int) ($projeto['turma_id'] ?? 0);

$isRep     = false;
$isDiretor = false;

if ($u && !$isMaster && $turmaId) {
    $tu    = new TurmaUsuario();
    $isRep = $tu->ehRepresentante($turmaId, (int) $u['id']);

    $pdo  = Database::getConnection();
    $stmt = $pdo->prepare("
        SELECT 1
        FROM grupo_diretores gd
        INNER JOIN grupos g ON g.id = gd.grupo_id
        WHERE g.projeto_id = ? AND gd.usuario_id = ? AND gd.ativo = 1
        LIMIT 1
    ");
    $stmt->execute([$pid, (int) $u['id']]);
    $isDiretor = (bool) $stmt->fetchColumn();
}

$secaoAtual = 'etapas';
foreach (['grupos', 'criterios', 'avaliacoes', 'conceitos', 'relatorios'] as $s) {
    if (strpos($uri, '/' . $s) !== false) { $secaoAtual = $s; break; }
}
if (strpos($uri, '/etapas') !== false) $secaoAtual = 'etapas';

$todasAbas = [
    'etapas'     => ['icon' => 'fas fa-list-ol',         'label' => 'Etapas',     'url' => "/projetos/{$pid}",            'roles' => ['master','aluno']],
    'grupos'     => ['icon' => 'fas fa-users',           'label' => 'Grupos',     'url' => "/projetos/{$pid}/grupos",     'roles' => ['master','aluno']],
    'criterios'  => ['icon' => 'fas fa-clipboard-check', 'label' => 'Critérios',  'url' => "/projetos/{$pid}/criterios",  'roles' => ['master','aluno']],
    'avaliacoes' => ['icon' => 'fas fa-star',            'label' => 'Avaliações', 'url' => "/projetos/{$pid}/avaliacoes", 'roles' => ['master','aluno']],
    'conceitos'  => ['icon' => 'fas fa-sliders',         'label' => 'Conceitos',  'url' => "/projetos/{$pid}/conceitos",  'roles' => ['master','representante']],
    'relatorios' => ['icon' => 'fas fa-file-alt',        'label' => 'Relatórios', 'url' => "/projetos/{$pid}/relatorios", 'roles' => ['master','representante']],
];

$abasVisiveis = [];
foreach ($todasAbas as $chave => $aba) {
    if (in_array('master', $aba['roles'], true) && $isMaster)     { $abasVisiveis[$chave] = $aba; continue; }
    if (in_array('representante', $aba['roles'], true) && $isRep) { $abasVisiveis[$chave] = $aba; continue; }
    if (in_array('aluno', $aba['roles'], true) && $u)             { $abasVisiveis[$chave] = $aba; continue; }
}
?>

<nav>
    <?php $primeiro = true; ?>
    <?php foreach ($abasVisiveis as $chave => $aba): ?>
        <?php if (!$primeiro): ?> — <?php endif; $primeiro = false; ?>
        <?php $ativo = ($chave === $secaoAtual); ?>
        <a href="<?= $basePath . $aba['url'] ?>"
           style="text-decoration:none;<?= $ativo ? 'background:#e5e7eb;padding:4px 10px;border-radius:4px;font-weight:700;color:#111;' : '' ?>">
            <i class="<?= h($aba['icon']) ?>"></i> <?= h($aba['label']) ?>
        </a>
    <?php endforeach; ?>
</nav>

<hr>