<?php
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';
?>

<h1>Configurações de Turma</h1>
<p>Cadastre cursos e períodos disponíveis para montar novas turmas.</p>

<hr>

<h2>Cursos</h2>

<table border="1" cellpadding="6">
    <thead>
        <tr><th>Nome</th><th>Sigla</th><th>Status</th><th>Ações</th></tr>
    </thead>
    <tbody>
        <?php foreach ($cursos as $c): ?>
            <tr>
                <td><?= h($c['nome']) ?></td>
                <td><code><?= h($c['sigla']) ?></code></td>
                <td>
                    <?php if ($c['ativo']): ?>
                        <i class="fas fa-check-circle" style="color:#0a0"></i> Ativo
                    <?php else: ?>
                        <i class="fas fa-ban" style="color:#b00"></i> Inativo
                    <?php endif; ?>
                </td>
                <td>
                    <form method="POST" action="<?= $basePath ?>/configuracoes/turmas/curso/toggle/<?= (int) $c['id'] ?>" style="display:inline">
                        <?= ViewHelper::csrfField() ?>
                        <button type="submit"><?= $c['ativo'] ? 'Desativar' : 'Ativar' ?></button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<h3>Novo curso</h3>
<form method="POST" action="<?= $basePath ?>/configuracoes/turmas/curso/salvar">
    <?= ViewHelper::csrfField() ?>
    <p><label>Nome * <input type="text" name="nome" maxlength="100" required></label></p>
    <p><label>Sigla (2-10 chars, A-Z/0-9) * <input type="text" name="sigla" maxlength="10" required></label></p>
    <p><button type="submit"><i class="fas fa-plus"></i> Adicionar curso</button></p>
</form>

<hr>

<h2>Períodos</h2>

<table border="1" cellpadding="6">
    <thead>
        <tr><th>Nome</th><th>Sigla</th><th>Status</th><th>Ações</th></tr>
    </thead>
    <tbody>
        <?php foreach ($periodos as $p): ?>
            <tr>
                <td><?= h($p['nome']) ?></td>
                <td><code><?= h($p['sigla']) ?></code></td>
                <td>
                    <?php if ($p['ativo']): ?>
                        <i class="fas fa-check-circle" style="color:#0a0"></i> Ativo
                    <?php else: ?>
                        <i class="fas fa-ban" style="color:#b00"></i> Inativo
                    <?php endif; ?>
                </td>
                <td>
                    <form method="POST" action="<?= $basePath ?>/configuracoes/turmas/periodo/toggle/<?= (int) $p['id'] ?>" style="display:inline">
                        <?= ViewHelper::csrfField() ?>
                        <button type="submit"><?= $p['ativo'] ? 'Desativar' : 'Ativar' ?></button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<h3>Novo período</h3>
<form method="POST" action="<?= $basePath ?>/configuracoes/turmas/periodo/salvar">
    <?= ViewHelper::csrfField() ?>
    <p><label>Nome * <input type="text" name="nome" maxlength="50" required></label></p>
    <p><label>Sigla (2-10 chars, A-Z/0-9) * <input type="text" name="sigla" maxlength="10" required></label></p>
    <p><button type="submit"><i class="fas fa-plus"></i> Adicionar período</button></p>
</form>

<p><a href="<?= $basePath ?>/turmas"><i class="fas fa-arrow-left"></i> Voltar</a></p>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>