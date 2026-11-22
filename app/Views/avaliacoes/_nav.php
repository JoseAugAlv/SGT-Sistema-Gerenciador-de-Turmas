<?php
// app/Views/avaliacoes/_nav.php
// Sub-nav de avaliações, filtrado por papel:
// a aba "Diretor" SÓ aparece se o usuário for diretor ativo em algum
// grupo do projeto. Master e representante veem as demais abas normalmente.

require_once __DIR__ . '/../../Config/database.php';
require_once __DIR__ . '/../../Models/TurmaUsuario.php';

$u = $_SESSION['usuario'] ?? null;
if (!$u) return;

$isMaster  = $u['tipo'] === 'master';
$turmaId   = (int) ($projeto['turma_id'] ?? 0);
$projetoId = (int) ($projeto['id'] ?? 0);

$isRep = false;
if (!$isMaster && $turmaId) {
    $tu    = new TurmaUsuario();
    $isRep = $tu->ehRepresentante($turmaId, (int) $u['id']);
}

// ---------- Diretor ativo em algum grupo DESTE projeto? ----------
$isDiretor = false;
if ($projetoId) {
    $pdo  = Database::getConnection();
    $stmt = $pdo->prepare("
        SELECT 1
        FROM grupo_diretores gd
        INNER JOIN grupos g ON g.id = gd.grupo_id
        WHERE g.projeto_id = ? AND gd.usuario_id = ? AND gd.ativo = 1
        LIMIT 1
    ");
    $stmt->execute([$projetoId, (int) $u['id']]);
    $isDiretor = (bool) $stmt->fetchColumn();
}

// ---------- Monta a lista de abas visíveis ----------
$chaves = [];

if ($isMaster || $isRep) {
    $chaves[] = 'rep';
}

// SÓ aparece se for diretor ativo
if ($isDiretor) {
    $chaves[] = 'dir';
}

// Aluno comum (sem papel de coordenação): começa pelo que falta fazer
if (!$isMaster && !$isRep && !$isDiretor) {
    $chaves[] = 'geral';
}

$chaves[] = 'par';
$chaves[] = 'auto';

if ($isMaster || $isRep) {
    $chaves[] = 'col';
}

$chaves[] = 'minhas';

// ---------- Definição das abas ----------
$todas = [
    'geral'  => ['url' => "/projetos/{$projetoId}/avaliacoes/geral",         'label' => 'A fazer',       'icon' => 'fas fa-list-check'],
    'rep'    => ['url' => "/projetos/{$projetoId}/avaliacoes/representante", 'label' => 'Representante', 'icon' => 'fas fa-user-tie'],
    'dir'    => ['url' => "/projetos/{$projetoId}/avaliacoes/diretor",       'label' => 'Diretor',       'icon' => 'fas fa-crown'],
    'par'    => ['url' => "/projetos/{$projetoId}/avaliacoes/pares",         'label' => 'Pares',         'icon' => 'fas fa-users'],
    'auto'   => ['url' => "/projetos/{$projetoId}/avaliacoes/auto",          'label' => 'Autoavaliação', 'icon' => 'fas fa-user-circle'],
    'col'    => ['url' => "/projetos/{$projetoId}/avaliacoes/coletiva",      'label' => 'Coletiva',      'icon' => 'fas fa-users-rectangle'],
    'minhas' => ['url' => "/projetos/{$projetoId}/avaliacoes/minhas",        'label' => 'Minhas Notas',  'icon' => 'fas fa-graduation-cap'],
];

// ---------- Detecta a seção ativa pela URL ----------
$uri   = $_SERVER['REQUEST_URI'] ?? '';
$secao = $chaves[0] ?? 'minhas';
if (strpos($uri, '/diretor')       !== false) $secao = 'dir';
if (strpos($uri, '/pares')         !== false) $secao = 'par';
if (strpos($uri, '/auto')          !== false) $secao = 'auto';
if (strpos($uri, '/coletiva')      !== false) $secao = 'col';
if (strpos($uri, '/minhas')        !== false) $secao = 'minhas';
if (strpos($uri, '/representante') !== false) $secao = 'rep';
if (strpos($uri, '/geral')         !== false) $secao = 'geral';
if (strpos($uri, '/parcial')       !== false) $secao = 'minhas';
?>

<nav class="aval-nav">
    <?php foreach ($chaves as $chave): ?>
        <?php $aba = $todas[$chave] ?? null; if (!$aba) continue; ?>
        <?php if ($chave === $secao): ?>
            <strong><i class="<?= h($aba['icon']) ?>"></i> <?= h($aba['label']) ?></strong>
        <?php else: ?>
            <a href="<?= $basePath . $aba['url'] ?>"><i class="<?= h($aba['icon']) ?>"></i> <?= h($aba['label']) ?></a>
        <?php endif; ?>
    <?php endforeach; ?>
</nav>