<?php
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';
?>
<?php require __DIR__ . '/../projetos/_nav.php'; ?>

<h1><?= h($grupo['nome']) ?></h1>
<p>Projeto: <a href="<?= $basePath ?>/projetos/<?= (int) $projeto['id'] ?>">
    <?= h($projeto['nome']) ?>
</a></p>
<p>
    Modo grupo: <code><?= h($grupo['modo_avaliacao_grupo']) ?></code>
    — Modo padrão: <code><?= h($grupo['modo_avaliacao_por']) ?></code>
</p>

<?php if ($podeEditar): ?>
    <p>
        <a href="<?= $basePath ?>/grupos/<?= (int) $grupo['id'] ?>/editar">
            <i class="fas fa-pen"></i> Editar grupo
        </a>
        —
        <a href="<?= $basePath ?>/grupos/<?= (int) $grupo['id'] ?>/diretores">
            <i class="fas fa-crown"></i> Gerenciar diretores
        </a>
        —
        <a href="<?= $basePath ?>/grupos/<?= (int) $grupo['id'] ?>/diretores/historico">
            <i class="fas fa-history"></i> Histórico de diretores
        </a>
    </p>
<?php else: ?>
    <p>
        <a href="<?= $basePath ?>/grupos/<?= (int) $grupo['id'] ?>/diretores/historico">
            <i class="fas fa-history"></i> Histórico de diretores
        </a>
    </p>
<?php endif; ?>

<h2>Diretores ativos (<?= count($diretores) ?>)</h2>
<?php if (empty($diretores)): ?>
    <p>Nenhum diretor nomeado.</p>
<?php else: ?>
    <ul>
        <?php foreach ($diretores as $d): ?>
            <li>
                <i class="fas fa-crown"></i>
                <?= h($d['nome']) ?> — <?= h($d['email']) ?>
                <small>(nomeado em <?= h($d['nomeado_em']) ?>)</small>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<h2>Membros (<?= count($membros) ?>)</h2>
<?php if (empty($membros)): ?>
    <p>Nenhum membro.</p>
<?php else: ?>
    <table border="1" cellpadding="6">
        <thead><tr><th>Nome</th><th>Email</th><th>Entrou em</th><?php if ($podeEditar): ?><th></th><?php endif; ?></tr></thead>
        <tbody>
            <?php foreach ($membros as $m): ?>
                <tr>
                    <td><?= h($m['nome']) ?></td>
                    <td><?= h($m['email']) ?></td>
                    <td><?= h($m['entrou_em']) ?></td>
                    <?php if ($podeEditar): ?>
                        <td>
                            <form method="POST" action="<?= $basePath ?>/grupos/<?= (int) $grupo['id'] ?>/membros/remover" style="display:inline">
                                <?= ViewHelper::csrfField() ?>
                                <input type="hidden" name="usuario_id" value="<?= (int) $m['usuario_id'] ?>">
                                <button type="submit" onclick="return confirm('Remover este membro?')">
                                    <i class="fas fa-user-minus"></i> Remover
                                </button>
                            </form>
                        </td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<?php if ($podeEditar): ?>
    <h3>Adicionar membro</h3>
    <?php if (empty($candidatos)): ?>
        <p>Nenhum aluno disponível na turma (todos já estão no grupo).</p>
    <?php else: ?>
        <form method="POST" action="<?= $basePath ?>/grupos/<?= (int) $grupo['id'] ?>/membros/adicionar">
            <?= ViewHelper::csrfField() ?>
            <p><label>Aluno<br>
                <select name="usuario_id" required>
                    <option value="">Selecione</option>
                    <?php foreach ($candidatos as $c): ?>
                        <option value="<?= (int) $c['id'] ?>"><?= h($c['nome']) ?> — <?= h($c['email']) ?></option>
                    <?php endforeach; ?>
                </select></label></p>
            <p><button type="submit"><i class="fas fa-user-plus"></i> Adicionar</button></p>
        </form>
    <?php endif; ?>

    <hr>
    <form method="POST" action="<?= $basePath ?>/grupos/<?= (int) $grupo['id'] ?>/excluir"
          onsubmit="return confirm('Excluir este grupo? Membros, diretores e avaliações do grupo serão perdidos.')">
        <?= ViewHelper::csrfField() ?>
        <p>
            <button type="submit" style="background:#b00;color:#fff">
                <i class="fas fa-trash"></i> Excluir grupo
            </button>
        </p>
    </form>
<?php endif; ?>

<p>
    <a href="<?= $basePath ?>/projetos/<?= (int) $projeto['id'] ?>/grupos">
        <i class="fas fa-arrow-left"></i> Voltar aos grupos
    </a>
</p>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>