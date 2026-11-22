<?php
// app/Views/master/usuarios.php
// Lista de usuários com busca por nome/email e atribuição de tipo.

$tituloPagina = 'Gerenciar Usuários — ' . App::getName();
$cssPagina    = 'master.css';

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';

// Bloqueio de segurança
if (empty($_SESSION['usuario']) || ($_SESSION['usuario']['tipo'] ?? '') !== 'master') {
    header('Location: ' . $basePath . '/');
    exit;
}

$pdo = Database::getConnection();

// ============ BUSCA ============
$busca = trim($_GET['busca'] ?? '');
$like  = '%' . $busca . '%';

// ============ LISTA DE USUÁRIOS ============
$usuarios = [];
try {
    if ($busca !== '') {
        $stmt = $pdo->prepare("
            SELECT id, nome, email, tipo, ativo, ultimo_acesso, created_at
            FROM usuarios
            WHERE nome LIKE ? OR email LIKE ?
            ORDER BY nome ASC
        ");
        $stmt->execute([$like, $like]);
    } else {
        $stmt = $pdo->query("
            SELECT id, nome, email, tipo, ativo, ultimo_acesso, created_at
            FROM usuarios
            ORDER BY nome ASC
        ");
    }
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $usuarios = [];
}

// ============ ESTATÍSTICAS GLOBAIS (sem filtro) ============
$totalGeral    = (int) $pdo->query("SELECT COUNT(*) FROM usuarios")->fetchColumn();
$ativosGeral   = (int) $pdo->query("SELECT COUNT(*) FROM usuarios WHERE ativo = 1")->fetchColumn();
$inativosGeral = $totalGeral - $ativosGeral;
$mastersGeral  = (int) $pdo->query("SELECT COUNT(*) FROM usuarios WHERE tipo = 'master'")->fetchColumn();

$totalFiltrado = count($usuarios);
?>

<section class="master-section">
    <div class="master-content">

        <!-- ============ HERO ============ -->
        <header class="master-hero">
            <div>
                <h1><i class="fas fa-users-cog"></i> Gerenciar Usuários</h1>
                <p class="master-subtitle">
                    <i class="fas fa-user-shield"></i>
                    Visualize, edite e altere o tipo de acesso dos usuários do sistema.
                </p>
            </div>
            <span class="badge-count">
                <i class="fas fa-users"></i> <?= $totalGeral ?> usuário(s)
            </span>
        </header>

        <!-- ============ STATS ============ -->
        <div class="master-stats" style="grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));">
            <article class="master-stat-card">
                <span class="label">Total</span>
                <span class="valor"><?= $totalGeral ?></span>
                <span class="foot">usuários cadastrados</span>
            </article>

            <article class="master-stat-card accent-green">
                <span class="label">Ativos</span>
                <span class="valor"><?= $ativosGeral ?></span>
                <span class="foot">com acesso liberado</span>
            </article>

            <article class="master-stat-card accent-red">
                <span class="label">Inativos</span>
                <span class="valor"><?= $inativosGeral ?></span>
                <span class="foot">acesso bloqueado</span>
            </article>

            <article class="master-stat-card accent-orange">
                <span class="label">Masters</span>
                <span class="valor"><?= $mastersGeral ?></span>
                <span class="foot">acesso total</span>
            </article>
        </div>

        <!-- ============ BARRA DE BUSCA ============ -->
        <form method="GET" action="<?= $basePath ?>/master/usuarios" class="master-search">
            <span class="search-icon"><i class="fas fa-magnifying-glass"></i></span>

            <input type="text"
                   name="busca"
                   value="<?= h($busca) ?>"
                   placeholder="Pesquisar por nome ou email..."
                   autocomplete="off"
                   autofocus>

            <?php if ($busca !== ''): ?>
                <a class="search-clear"
                   href="<?= $basePath ?>/master/usuarios"
                   title="Limpar busca">
                    <i class="fas fa-xmark"></i>
                </a>
            <?php endif; ?>

            <button type="submit" class="btn-new">
                <i class="fas fa-search"></i> Pesquisar
            </button>
        </form>

        <!-- ============ INFO DA BUSCA ============ -->
        <?php if ($busca !== ''): ?>
            <div class="master-info">
                <i class="fas fa-filter"></i>
                <strong><?= $totalFiltrado ?></strong> resultado(s) para
                <strong>"<?= h($busca) ?>"</strong>
                <a href="<?= $basePath ?>/master/usuarios"
                   style="margin-left:auto; color: var(--primary); font-weight:700; text-decoration:none; font-size:12px;">
                    <i class="fas fa-xmark"></i> Limpar
                </a>
            </div>
        <?php endif; ?>

        <!-- ============ AÇÃO NOVO ============ -->
        <div class="master-box box-primary">
            <div class="box-actions">
                <span class="box-info-text">
                    <i class="fas fa-circle-info"></i>
                    <strong>Master</strong> tem acesso total. <strong>Aluno</strong> vê apenas suas turmas e avaliações.
                </span>
                <a class="btn-new" href="<?= $basePath ?>/usuarios/criar">
                    <i class="fas fa-user-plus"></i> Novo usuário
                </a>
            </div>
        </div>

        <!-- ============ LISTA ============ -->
        <?php if (empty($usuarios)): ?>
            <div class="empty-state">
                <i class="fas fa-<?= $busca !== '' ? 'magnifying-glass' : 'users' ?>"></i>
                <?php if ($busca !== ''): ?>
                    <h3>Nenhum resultado</h3>
                    <p>Nenhum usuário encontrado para "<strong><?= h($busca) ?></strong>".</p>
                    <a class="btn-new" href="<?= $basePath ?>/master/usuarios">
                        <i class="fas fa-xmark"></i> Limpar busca
                    </a>
                <?php else: ?>
                    <h3>Nenhum usuário cadastrado</h3>
                    <p>Comece cadastrando o primeiro usuário do sistema.</p>
                    <a class="btn-new" href="<?= $basePath ?>/usuarios/criar">
                        <i class="fas fa-user-plus"></i> Cadastrar usuário
                    </a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="master-table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nome</th>
                            <th>Email</th>
                            <th>Tipo</th>
                            <th>Status</th>
                            <th style="text-align:right;">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($usuarios as $u): ?>
                            <?php
                            $uid      = (int) $u['id'];
                            $tipo     = $u['tipo'] ?? 'aluno';
                            $ativo    = !empty($u['ativo']);
                            $souEu    = $uid === (int) $_SESSION['usuario']['id'];
                            $perfilCl = $tipo === 'master' ? 'perfil-1' : 'perfil-4';

                            // Destaca o termo buscado no nome/email
                            $destacar = function (string $texto) use ($busca): string {
                                if ($busca === '') return h($texto);
                                $escaped = preg_quote($busca, '/');
                                return preg_replace(
                                    '/(' . $escaped . ')/iu',
                                    '<mark>$1</mark>',
                                    h($texto)
                                );
                            };
                            ?>

                            <tr>
                                <td><strong>#<?= $uid ?></strong></td>

                                <td>
                                    <strong><?= $destacar($u['nome']) ?></strong>
                                    <?php if ($souEu): ?>
                                        <small style="color: var(--primary); font-weight: 700;">
                                            <i class="fas fa-user"></i> você
                                        </small>
                                    <?php elseif (!empty($u['ultimo_acesso'])): ?>
                                        <small>último acesso: <?= h($u['ultimo_acesso']) ?></small>
                                    <?php endif; ?>
                                </td>

                                <td><?= $destacar($u['email']) ?></td>

                                <td>
                                    <span class="status-badge <?= $perfilCl ?>">
                                        <?php if ($tipo === 'master'): ?>
                                            <i class="fas fa-crown"></i> Master
                                        <?php else: ?>
                                            <i class="fas fa-user-graduate"></i> Aluno
                                        <?php endif; ?>
                                    </span>
                                </td>

                                <td>
                                    <?php if ($ativo): ?>
                                        <span class="status-badge ativo">
                                            <i class="fas fa-check"></i> Ativo
                                        </span>
                                    <?php else: ?>
                                        <span class="status-badge inativo">
                                            <i class="fas fa-ban"></i> Inativo
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <td style="text-align:right; white-space:nowrap;">
                                    <button type="button"
                                            class="btn btn-sm btn-edit"
                                            title="Alterar tipo"
                                            onclick='abrirModalAtribuir(
                                                <?= $uid ?>,
                                                <?= json_encode($u["nome"], JSON_UNESCAPED_UNICODE) ?>,
                                                <?= json_encode($tipo) ?>
                                            )'>
                                        <i class="fas fa-user-tag"></i> Perfil
                                    </button>

                                    <a class="btn btn-sm btn-edit"
                                       title="Editar"
                                       href="<?= $basePath ?>/usuarios/editar?id=<?= $uid ?>">
                                        <i class="fas fa-pen"></i>
                                    </a>

                                    <?php if (!$souEu): ?>
                                        <a class="btn btn-sm btn-danger"
                                           title="Desativar"
                                           href="<?= $basePath ?>/usuarios/excluir?id=<?= $uid ?>"
                                           onclick="return confirm('Desativar este usuário? O acesso será bloqueado.')">
                                            <i class="fas fa-user-slash"></i>
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="master-info">
                <i class="fas fa-circle-info"></i>
                <?php if ($busca !== ''): ?>
                    Mostrando <strong><?= $totalFiltrado ?></strong> de
                    <strong><?= $totalGeral ?></strong> usuário(s)
                <?php else: ?>
                    Total de usuários: <strong><?= $totalGeral ?></strong>
                    · Ativos: <strong><?= $ativosGeral ?></strong>
                    · Inativos: <strong><?= $inativosGeral ?></strong>
                    · Masters: <strong><?= $mastersGeral ?></strong>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- ============ VOLTAR ============ -->
        <div class="master-voltar">
            <a class="btn" href="<?= $basePath ?>/master"
               style="padding:9px 14px; border-radius:9px; border:1px solid var(--border); color:var(--muted); text-decoration:none; font-size:13px; font-weight:700; display:inline-flex; align-items:center; gap:6px;">
                <i class="fas fa-arrow-left"></i> Voltar ao painel
            </a>
        </div>

    </div>
</section>

<!-- ============================================================ -->
<!-- MODAL — ALTERAR TIPO DE ACESSO                                -->
<!-- ============================================================ -->
<div id="modalAtribuir" class="master-modal">
    <div class="modal-box">
        <div class="modal-header">
            <h2>
                <i class="fas fa-user-tag" style="color: var(--primary);"></i>
                Alterar tipo de acesso
            </h2>
            <button type="button" class="modal-close" onclick="fecharModal()">&times;</button>
        </div>

        <div class="modal-body">
            <p>
                <strong>Usuário</strong>
                <span id="modalUsuarioNome" style="font-weight:700; color: var(--text);"></span>
            </p>

            <form action="<?= $basePath ?>/usuarios/atribuir-perfil" method="POST">
                <?= ViewHelper::csrfField() ?>
                <input type="hidden" id="modalIdUsuario" name="id_usuario">

                <div class="form-group">
                    <label for="modalTipo">Tipo de acesso</label>
                    <select id="modalTipo" name="tipo" class="form-control" required>
                        <option value="aluno">Aluno — acesso limitado</option>
                        <option value="master">Master — acesso total</option>
                    </select>
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn"
                            onclick="fecharModal()"
                            style="padding:9px 14px; border-radius:9px; border:1px solid var(--border); color:var(--muted); font-size:13px; font-weight:700; background:transparent; cursor:pointer;">
                        <i class="fas fa-xmark"></i> Cancelar
                    </button>
                    <button type="submit" class="btn-new">
                        <i class="fas fa-save"></i> Salvar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function abrirModalAtribuir(id, nome, tipo) {
    document.getElementById('modalIdUsuario').value = id;
    document.getElementById('modalUsuarioNome').textContent = nome;
    document.getElementById('modalTipo').value = tipo || 'aluno';
    document.getElementById('modalAtribuir').classList.add('active');
    document.body.style.overflow = 'hidden';
}

function fecharModal() {
    document.getElementById('modalAtribuir').classList.remove('active');
    document.body.style.overflow = '';
}

document.getElementById('modalAtribuir').addEventListener('click', function (e) {
    if (e.target === this) fecharModal();
});

document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') fecharModal();
});

// ============================================================
// Atalho: "/" foca na busca, ESC limpa
// ============================================================
document.addEventListener('keydown', function (e) {
    var input = document.querySelector('.master-search input[name="busca"]');
    if (!input) return;

    // "/" fora de um input foca na busca
    if (e.key === '/' && document.activeElement !== input
        && !['INPUT', 'TEXTAREA', 'SELECT'].includes(document.activeElement.tagName)) {
        e.preventDefault();
        input.focus();
        input.select();
    }
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>