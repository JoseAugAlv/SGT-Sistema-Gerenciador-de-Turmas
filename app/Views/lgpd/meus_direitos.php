<?php
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';
?>

<h1>Meus Direitos (LGPD)</h1>

<p>
    Consulte a <a href="<?= $basePath ?>/lgpd">Política de Privacidade</a>
    para entender como tratamos seus dados.
</p>

<hr>

<h2>Exportar meus dados</h2>
<p>
    Gera um arquivo JSON com todos os seus dados: perfil, turmas, grupos,
    avaliações, atas, materiais, notificações e alertas.
</p>

<form method="POST" action="<?= $basePath ?>/lgpd/exportar">
    <?= ViewHelper::csrfField() ?>
    <button type="submit"><i class="fas fa-download"></i> Exportar meus dados</button>
</form>

<hr>

<h2>Solicitar exclusão da conta</h2>
<p>
    A exclusão é <strong>permanente</strong> e remove seus dados pessoais
    (nome, email, telefone). Registros de auditoria são mantidos de forma
    anonimizada pelo prazo legal mínimo.
</p>
<p>
    <strong>Atenção:</strong> a solicitação passa por análise antes de ser
    executada. Você será notificado.
</p>

<form method="POST" action="<?= $basePath ?>/lgpd/solicitar-exclusao">
    <?= ViewHelper::csrfField() ?>

    <p>
        <label>Motivo (opcional)<br>
            <textarea name="motivo" rows="3" maxlength="500"></textarea>
        </label>
    </p>
    <p>
        <button type="submit"
                onclick="return confirm('Confirma a solicitação de exclusão da conta? Esta ação é irreversível após aprovação.')">
            <i class="fas fa-trash"></i> Solicitar exclusão da conta
        </button>
    </p>
</form>

<hr>

<h2>Minhas solicitações anteriores</h2>

<?php if (empty($solicitacoes)): ?>
    <p>Nenhuma solicitação registrada.</p>
<?php else: ?>
    <table border="1" cellpadding="6">
        <thead>
            <tr>
                <th>Data</th>
                <th>Tipo</th>
                <th>Status</th>
                <th>Arquivo</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($solicitacoes as $s): ?>
                <tr>
                    <td><?= h($s['created_at']) ?></td>
                    <td><?= h($s['tipo']) ?></td>
                    <td><?= h($s['status']) ?></td>
                    <td>
                        <?php if (!empty($s['arquivo_gerado'])): ?>
                            <a href="<?= $basePath ?>/lgpd/exportacao/<?= (int) $s['id'] ?>/baixar">
                                <i class="fas fa-download"></i> Baixar
                            </a>
                        <?php else: ?>
                            —
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>