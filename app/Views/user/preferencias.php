<?php
// app/Views/user/preferencias.php
//
// Alias de /notificacoes/preferencias para acesso pela área "Minha Conta".
// Redireciona o usuário para a tela correta, mantendo o padrão de URL.

$basePath = App::getBasePath();
header('Location: ' . $basePath . '/notificacoes/preferencias');
exit;