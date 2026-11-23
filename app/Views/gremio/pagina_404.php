<?php
// app/Views/gremio/pagina_404.php — página pública inexistente ou desligada.
?><!doctype html>
<html lang="pt-BR">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Página não encontrada</title>
<link rel="stylesheet" href="<?= ViewHelper::asset('css/gremio-publico.css') ?>"></head>
<body class="gpp-404">
<main><h1>Página não encontrada</h1><p>Este grêmio não existe ou a página dele está desligada no momento.</p><a href="<?= htmlspecialchars($basePath) ?>/">Ir para o Prumo</a></main>
</body>
</html>
