<?php
// app/Views/projetos/_nav.php
// Abas do projeto filtradas por papel. A aba ativa fica destacada.

if (empty($projeto) || empty($projeto['id'])) {
    return;
}

$cssPagina = 'projetos.css';

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';

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

// Detecta a seção ativa
$secaoAtual = 'etapas';
foreach (['grupos', 'atas', 'criterios', 'avaliacoes', 'conceitos', 'relatorios', 'materiais'] as $s) {
    if (strpos($uri, '/' . $s) !== false) { $secaoAtual = $s; break; }
}
if (strpos($uri, '/etapas') !== false) $secaoAtual = 'etapas';
if (strpos($uri, '/solicitacoes/') !== false) $secaoAtual = 'materiais'; // pedidos de material

$todasAbas = [
    'etapas'     => ['icon' => 'fas fa-list-ol',          'label' => 'Etapas',     'url' => "/projetos/{$pid}",             'roles' => ['master','aluno']],
    'grupos'     => ['icon' => 'fas fa-users',            'label' => 'Grupos',     'url' => "/projetos/{$pid}/grupos",      'roles' => ['master','aluno']],
    'atas'       => ['icon' => 'fas fa-book',             'label' => 'Atas',       'url' => "/projetos/{$pid}/atas",        'roles' => ['master','aluno']],
    'criterios'  => ['icon' => 'fas fa-clipboard-check',  'label' => 'Critérios',  'url' => "/projetos/{$pid}/criterios",   'roles' => ['master','aluno']],
    'avaliacoes' => ['icon' => 'fas fa-star',             'label' => 'Avaliações', 'url' => "/projetos/{$pid}/avaliacoes",  'roles' => ['master','aluno']],
    'conceitos'  => ['icon' => 'fas fa-sliders',          'label' => 'Conceitos',  'url' => "/projetos/{$pid}/conceitos",   'roles' => ['master','representante']],
    'relatorios' => ['icon' => 'fas fa-file-alt',         'label' => 'Relatórios', 'url' => "/projetos/{$pid}/relatorios",  'roles' => ['master','representante']],
    'materiais'  => ['icon' => 'fas fa-boxes',            'label' => 'Materiais',  'url' => "/projetos/{$pid}/materiais",   'roles' => ['master','representante','diretor', 'aluno']],
];

$abasVisiveis = [];
foreach ($todasAbas as $chave => $aba) {
    if (in_array('master', $aba['roles'], true) && $isMaster)     { $abasVisiveis[$chave] = $aba; continue; }
    if (in_array('representante', $aba['roles'], true) && $isRep) { $abasVisiveis[$chave] = $aba; continue; }
    if (in_array('diretor', $aba['roles'], true) && $isDiretor)   { $abasVisiveis[$chave] = $aba; continue; }
    if (in_array('aluno', $aba['roles'], true) && $u)             { $abasVisiveis[$chave] = $aba; continue; }
}
?>

<nav class="proj-nav" aria-label="Seções do projeto">
    <?php foreach ($abasVisiveis as $chave => $aba): ?>
        <a href="<?= $basePath . $aba['url'] ?>" class="<?= $chave === $secaoAtual ? 'ativo' : '' ?>"<?= $chave === $secaoAtual ? ' aria-current="page"' : '' ?>>
            <i class="<?= h($aba['icon']) ?>" aria-hidden="true"></i>
            <span><?= h($aba['label']) ?></span>
        </a>
    <?php endforeach; ?>
</nav>