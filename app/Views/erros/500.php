<?php
// app/Views/erros/500.php
// Página amigável quando algo quebra em produção (o detalhe vai para logs/php_errors.log, visto pelo master em /master/erros).
$basePath = $basePath ?? (class_exists('App') ? App::getBasePath() : '');
?><!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Algo deu errado — Prumo</title>
<style>
  :root { --bg: #f5f7fb; --card: #fff; --ink: #0f1222; --muted: #5b6478; --accent: #2563eb; }
  @media (prefers-color-scheme: dark) { :root { --bg: #0d1120; --card: #151a2b; --ink: #e5e9f5; --muted: #a3acc2; } }
  * { box-sizing: border-box; }
  body { margin: 0; min-height: 100vh; display: grid; place-items: center; padding: 16px; background: var(--bg); color: var(--ink);
         font: 16px/1.55 system-ui, -apple-system, "Segoe UI", Roboto, sans-serif; }
  .card { width: min(460px, 100%); background: var(--card); border-radius: 20px; padding: 32px 28px; text-align: center;
          box-shadow: 0 30px 70px -30px rgba(15, 18, 34, .35); }
  img { border-radius: 14px; }
  h1 { font-size: 22px; margin: 14px 0 6px; }
  p { color: var(--muted); margin: 0 0 22px; }
  .acoes { display: flex; gap: 10px; justify-content: center; flex-wrap: wrap; }
  a, button { border: 0; border-radius: 12px; padding: 11px 18px; font: inherit; font-weight: 700; text-decoration: none; cursor: pointer; }
  .sim { background: var(--accent); color: #fff; }
  .nao { background: color-mix(in oklab, var(--accent) 10%, var(--card)); color: var(--ink); }
</style>
</head>
<body>
  <main class="card">
    <img src="<?= htmlspecialchars($basePath) ?>/public/img/logo-prumo.png" alt="" width="56" height="56">
    <h1>Algo deu errado por aqui</h1>
    <p>Não foi nada que você fez. O erro já ficou registrado para ser corrigido. Tente de novo em instantes.</p>
    <div class="acoes">
      <button type="button" class="sim" onclick="location.reload()">Tentar de novo</button>
      <a class="nao" href="<?= htmlspecialchars($basePath) ?>/">Ir para o início</a>
    </div>
  </main>
</body>
</html>
