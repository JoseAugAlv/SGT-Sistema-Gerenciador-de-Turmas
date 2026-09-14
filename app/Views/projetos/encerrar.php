<?php
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';
?>
<?php require __DIR__ . '/../projetos/_nav.php'; ?>
<h1>Encerrar Projeto</h1>

<p>Você está prestes a encerrar:</p>
<p><strong><?= h($projeto['nome']) ?></strong> — turma <?= h($projeto['turma_nome']) ?></p>

<p style="color:#b00">
    <i class="fas fa-exclamation-triangle"></i>
    <strong>Atenção:</strong> esta ação é irreversível.
    O boletim de todos os alunos será congelado e edições posteriores em avaliações não alterarão o snapshot.
</p>

<form method="POST" action="<?= $basePath ?>/projetos/<?= (int) $projeto['id'] ?>/encerrar">
    <?= ViewHelper::csrfField() ?>

    <p>
        <label>
            <input type="checkbox" name="confirmo" value="1" required>
            Confirmo que desejo encerrar o projeto permanentemente.
        </label>
    </p>
    <p>
        <button type="submit" style="background:#b00;color:#fff"
                onclick="return confirm('Tem certeza absoluta?')">
            <i class="fas fa-lock"></i> Encerrar definitivamente
        </button>
        <a href="<?= $basePath ?>/projetos/<?= (int) $projeto['id'] ?>">Cancelar</a>
    </p>
</form>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>