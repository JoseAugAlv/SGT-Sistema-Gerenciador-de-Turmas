<?php
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';

// ---------- Verifica o papel do usuário ----------
require_once __DIR__ . '/../../Config/database.php';
require_once __DIR__ . '/../../Models/TurmaUsuario.php';

$u = $_SESSION['usuario'] ?? null;
$isMaster = $u && $u['tipo'] === 'master';

$tu = new TurmaUsuario();
$isRep = $u && !$isMaster && $tu->ehRepresentante((int) $projeto['turma_id'], (int) $u['id']);

// Só master ou representante pode editar/reabrir critérios
$podeGerenciar = $isMaster || $isRep;

$badgePeso = [
    'incompleto' => ['icon' => 'fas fa-hourglass-half',       'cor' => '#c80', 'txt' => 'Incompleto'],
    'completo'   => ['icon' => 'fas fa-check-circle',         'cor' => '#0a0', 'txt' => 'Completo'],
    'excedido'   => ['icon' => 'fas fa-exclamation-triangle', 'cor' => '#b00', 'txt' => 'Excedido'],
];
$b = $badgePeso[$statusPeso];

$tipoLabel = fn(string $t) => Criterio::TIPOS[$t] ?? $t;
$aplicLabel = fn(string $a) => Criterio::APLICAVEIS[$a] ?? $a;
?>

<h1><?= h($projeto['nome']) ?></h1>
<p>Turma: <a href="<?= $basePath ?>/turmas/<?= (int) $projeto['turma_id'] ?>"><?= h($projeto['turma_nome']) ?></a></p>

<?php require __DIR__ . '/../projetos/_nav.php'; ?>

<h2>Critérios (<?= count($criterios) ?>)</h2>

<p>
    Soma dos pesos:
    <strong style="color: <?= $b['cor'] ?>">
        <?= number_format($somaPesos, 2, ',', '.') ?> / 10.00
    </strong>
    <i class="<?= $b['icon'] ?>" style="color: <?= $b['cor'] ?>"></i>
    <?= $b['txt'] ?>
</p>

<?php if ($podeEditar): ?>
    <p>
        <a href="<?= $basePath ?>/projetos/<?= (int) $projeto['id'] ?>/criterios/criar">
            <i class="fas fa-plus"></i> Novo critério
        </a>
    </p>
<?php endif; ?>

<?php if (empty($criterios)): ?>
    <p>Nenhum critério cadastrado.</p>
<?php else: ?>
    <table border="1" cellpadding="6">
        <thead>
            <tr>
                <th>Nome</th>
                <th>Peso</th>
                <th>Tipo</th>
                <th>Aplicável</th>
                <th>Status</th>
                <th>Avaliações</th>
                <th>Detalhes</th>
                <?php if ($podeEditar): ?><th>Ações</th><?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($criterios as $c): ?>
                <tr>
                    <td><?= h($c['nome']) ?></td>
                    <td><?= number_format((float) $c['peso'], 2, ',', '.') ?></td>
                    <td><code><?= h($c['tipo_avaliacao']) ?></code></td>
                    <td><code><?= h($c['aplicavel_a']) ?></code></td>
                    <td>
                        <?php if ($c['bloqueado']): ?>
                            <i class="fas fa-lock" style="color:#b00"></i> Bloqueado
                        <?php else: ?>
                            <i class="fas fa-lock-open" style="color:#0a0"></i> Aberto
                        <?php endif; ?>
                    </td>
                    <td><?= (int) $c['total_avaliacoes'] + (int) $c['total_coletivas'] ?></td>
                    <td>
                        <button type="button" onclick="abrirModal('modal-criterio-<?= (int) $c['id'] ?>')">
                            <i class="fas fa-eye"></i> Ver detalhes
                        </button>
                    </td>
                    <?php if ($podeEditar): ?>
                        <td>
                            <a href="<?= $basePath ?>/criterios/<?= (int) $c['id'] ?>/editar" title="Editar">
                                <i class="fas fa-pen"></i>
                            </a>
                            <form method="POST" action="<?= $basePath ?>/criterios/<?= (int) $c['id'] ?>/excluir"
                                  style="display:inline">
                                <?= ViewHelper::csrfField() ?>
                                <button type="submit" title="Excluir"
                                        onclick="return confirm('Excluir este critério? Se houver avaliações, serão arquivadas por 30 dias.')">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                            <?php if ($c['bloqueado']): ?>
                                <a href="<?= $basePath ?>/criterios/<?= (int) $c['id'] ?>/reabrir" title="Reabrir">
                                    <i class="fas fa-rotate"></i>
                                </a>
                            <?php endif; ?>
                        </td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<?php foreach ($criterios as $c): ?>
    <div id="modal-criterio-<?= (int) $c['id'] ?>" class="modal-overlay"
         style="display:none; position:fixed; top:0; left:0; width:100%; height:100%;
                background:rgba(0,0,0,0.5); z-index:9000; padding:2rem; overflow:auto;">

        <div style="background:#fff; max-width:640px; margin:2rem auto; padding:2rem;
                    border-radius:8px; position:relative;">

            <button type="button" onclick="fecharModal('modal-criterio-<?= (int) $c['id'] ?>')"
                    style="position:absolute; top:8px; right:12px; font-size:1.5rem;
                           border:none; background:none; cursor:pointer;">
                &times;
            </button>

            <h2 style="margin-top:0;"><?= h($c['nome']) ?></h2>

            <table border="1" cellpadding="6" style="width:100%;">
                <tbody>
                    <tr>
                        <th style="text-align:left;">Peso</th>
                        <td><?= number_format((float) $c['peso'], 2, ',', '.') ?></td>
                    </tr>
                    <tr>
                        <th style="text-align:left;">Tipo de avaliação</th>
                        <td>
                            <?= h($tipoLabel($c['tipo_avaliacao'])) ?>
                            <br><small><code><?= h($c['tipo_avaliacao']) ?></code></small>
                        </td>
                    </tr>
                    <tr>
                        <th style="text-align:left;">Aplicável a</th>
                        <td>
                            <?= h($aplicLabel($c['aplicavel_a'])) ?>
                            <br><small><code><?= h($c['aplicavel_a']) ?></code></small>
                        </td>
                    </tr>
                    <tr>
                        <th style="text-align:left;">Etapa</th>
                        <td><?= h($c['etapa_nome'] ?? '—') ?></td>
                    </tr>
                    <tr>
                        <th style="text-align:left;">Prazo de avaliação</th>
                        <td><?= h($c['prazo_avaliacao'] ?? '—') ?></td>
                    </tr>
                    <tr>
                        <th style="text-align:left;">Status</th>
                        <td>
                            <?php if ($c['bloqueado']): ?>
                                <i class="fas fa-lock" style="color:#b00"></i> Bloqueado
                            <?php else: ?>
                                <i class="fas fa-lock-open" style="color:#0a0"></i> Aberto
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th style="text-align:left;">Avaliações registradas</th>
                        <td>
                            Individuais: <strong><?= (int) $c['total_avaliacoes'] ?></strong>
                            —
                            Coletivas: <strong><?= (int) $c['total_coletivas'] ?></strong>
                        </td>
                    </tr>
                    <tr>
                        <th style="text-align:left;">Criado em</th>
                        <td><?= h($c['created_at'] ?? '—') ?></td>
                    </tr>
                    <?php if (!empty($c['reaberto_em'])): ?>
                        <tr>
                            <th style="text-align:left;">Reaberto em</th>
                            <td><?= h($c['reaberto_em']) ?></td>
                        </tr>
                    <?php endif; ?>
                    <?php if (!empty($c['descricao'])): ?>
                        <tr>
                            <th style="text-align:left;">Descrição</th>
                            <td><?= nl2br(h($c['descricao'])) ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <p style="margin-bottom:0;">
                <button type="button" onclick="fecharModal('modal-criterio-<?= (int) $c['id'] ?>')">
                    <i class="fas fa-times"></i> Fechar
                </button>
            </p>
        </div>
    </div>
<?php endforeach; ?>

<script>
function abrirModal(id) {
    document.getElementById(id).style.display = 'block';
    document.body.style.overflow = 'hidden';
}

function fecharModal(id) {
    document.getElementById(id).style.display = 'none';
    document.body.style.overflow = '';
}

// Fecha ao clicar fora do box
document.querySelectorAll('.modal-overlay').forEach(function(overlay) {
    overlay.addEventListener('click', function(e) {
        if (e.target === overlay) {
            overlay.style.display = 'none';
            document.body.style.overflow = '';
        }
    });
});

// Fecha com ESC
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        document.querySelectorAll('.modal-overlay').forEach(function(o) {
            o.style.display = 'none';
        });
        document.body.style.overflow = '';
    }
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>