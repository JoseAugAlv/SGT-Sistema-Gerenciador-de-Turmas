<?php
// app/Views/avaliacoes/_nav.php
// Sub-nav de avaliações, filtrado por papel com prioridade:
// master > representante > diretor > aluno.
// Representante NÃO vê a aba Diretor.

require_once __DIR__ . '/../../Config/database.php';
require_once __DIR__ . '/../../Models/TurmaUsuario.php';

$u = $_SESSION['usuario'] ?? null;

if (!$u) {
    return; // ninguém logado — não renderiza nada
}

$isMaster = $u['tipo'] === 'master';
$turmaId  = (int) ($projeto['turma_id'] ?? 0);
$projetoId = (int) ($projeto['id'] ?? 0);

// Verifica se é representante da turma
$isRep = false;
if (!$isMaster && $turmaId) {
    $tu    = new TurmaUsuario();
    $isRep = $tu->ehRepresentante($turmaId, (int) $u['id']);
}

// Verifica se é diretor ativo em algum grupo do projeto (só se NÃO for rep)
$isDiretor = false;
if (!$isMaster && !$isRep && $projetoId) {
    $pdo  = Database::getConnection();
    $stmt = $pdo->prepare("
        SELECT 1 FROM grupo_diretores gd
        INNER JOIN grupos g ON g.id = gd.grupo_id
        WHERE g.projeto_id = ? AND gd.usuario_id = ? AND gd.ativo = 1
        LIMIT 1
    ");
    $stmt->execute([$projetoId, (int) $u['id']]);
    $isDiretor = (bool) $stmt->fetchColumn();
}

// Define a lista de abas conforme o papel (rep não vê dir)
if ($isMaster) {
    $chaves = ['rep', 'dir', 'par', 'auto', 'col', 'minhas'];
} elseif ($isRep) {
    $chaves = ['rep', 'col', 'par', 'auto', 'minhas']; // SEM 'dir'
} elseif ($isDiretor) {
    $chaves = ['dir', 'par', 'auto', 'minhas'];
} else {
    $chaves = ['par', 'auto', 'minhas'];
}

// Metadados de cada aba
$todas = [
    'rep'    => ['url' => "/projetos/{$projetoId}/avaliacoes/representante", 'label' => 'Representante', 'icon' => 'fas fa-user-tie'],
    'dir'    => ['url' => "/projetos/{$projetoId}/avaliacoes/diretor",       'label' => 'Diretor',       'icon' => 'fas fa-crown'],
    'par'    => ['url' => "/projetos/{$projetoId}/avaliacoes/pares",         'label' => 'Pares',         'icon' => 'fas fa-users'],
    'auto'   => ['url' => "/projetos/{$projetoId}/avaliacoes/auto",          'label' => 'Autoavaliação', 'icon' => 'fas fa-user-circle'],
    'col'    => ['url' => "/projetos/{$projetoId}/avaliacoes/coletiva",      'label' => 'Coletiva',      'icon' => 'fas fa-users-rectangle'],
    'minhas' => ['url' => "/projetos/{$projetoId}/avaliacoes/minhas",        'label' => 'Minhas Notas',  'icon' => 'fas fa-graduation-cap'],
];

// Detecta seção ativa
$uri   = $_SERVER['REQUEST_URI'] ?? '';
$secao = $chaves[0];

if (strpos($uri, '/diretor')       !== false) $secao = 'dir';
if (strpos($uri, '/pares')         !== false) $secao = 'par';
if (strpos($uri, '/auto')          !== false) $secao = 'auto';
if (strpos($uri, '/coletiva')      !== false) $secao = 'col';
if (strpos($uri, '/minhas')        !== false) $secao = 'minhas';
if (strpos($uri, '/representante') !== false) $secao = 'rep';
?>

<nav>
    <?php $primeiro = true; ?>
    <?php foreach ($chaves as $chave): ?>
        <?php if (!isset($todas[$chave])) continue; ?>
        <?php $aba = $todas[$chave]; ?>
        <?php if (!$primeiro): ?> — <?php endif; $primeiro = false; ?>
        <?php $ativo = ($chave === $secao); ?>
        <a href="<?= $basePath . $aba['url'] ?>"
           style="text-decoration:none;<?= $ativo ? 'background:#e5e7eb;padding:4px 10px;border-radius:4px;font-weight:700;color:#111;' : '' ?>">
            <i class="<?= h($aba['icon']) ?>"></i> <?= h($aba['label']) ?>
        </a>
    <?php endforeach; ?>
</nav>
<hr>