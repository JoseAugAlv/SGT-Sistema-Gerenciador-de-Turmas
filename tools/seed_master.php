<?php
// tools/seed_master.php
// Rodar via CLI: php tools/seed_master.php
if (php_sapi_name() !== 'cli') {
    die("Este script só roda via linha de comando.\n");
}

require __DIR__ . '/../app/Core/App.php';
App::init();
require __DIR__ . '/../app/Config/database.php';

$pdo = Database::getConnection();

$email      = 'master@adm.com';
$senhaPlana = 'admin123';
$hash       = password_hash($senhaPlana, PASSWORD_BCRYPT, ['cost' => 12]);

$stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
$stmt->execute([$email]);
$id = $stmt->fetchColumn();

if ($id) {
    $pdo->prepare("UPDATE usuarios SET senha = ? WHERE id = ?")->execute([$hash, $id]);
    echo "[OK] Master já existia — senha atualizada.\n";
} else {
    $pdo->prepare("
        INSERT INTO usuarios (nome, email, senha, tipo, email_confirmado, primeiro_login, ativo, lgpd_aceito, lgpd_data)
        VALUES ('Master', ?, ?, 'master', 1, 0, 1, 1, NOW())
    ")->execute([$email, $hash]);

    $id = $pdo->lastInsertId();
    $pdo->prepare("INSERT INTO preferencias_usuario (usuario_id) VALUES (?)")->execute([$id]);
    echo "[OK] Master criado.\n";
}

echo "Email: {$email}\n";
echo "Senha: {$senhaPlana}\n";
echo "Hash gerado: {$hash}\n";