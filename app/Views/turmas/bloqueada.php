<?php
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
?>

<h1><i class="fas fa-ban" style="color:#b00"></i> Turma bloqueada</h1>
<p><?= h($turma['bloqueada_motivo'] ?? 'Contate o administrador.') ?></p>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>