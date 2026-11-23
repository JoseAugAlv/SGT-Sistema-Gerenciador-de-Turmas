<?php
// app/Views/gremio/pagina_publica.php
// Página pública do grêmio (sem login). Página própria, nas cores do grêmio, pronta para compartilhar.
$e = fn($t) => htmlspecialchars((string) $t, ENT_QUOTES, 'UTF-8');
$cor = preg_match('/^#[0-9a-fA-F]{6}$/', (string) $p['cor_primaria']) ? $p['cor_primaria'] : '#3355e0';
$logo = $p['logo'] ? $basePath . '/public/' . $p['logo'] : $basePath . '/public/img/logo-prumo.png';
$brl = fn($v) => 'R$ ' . number_format((float) $v, 2, ',', '.');
$meses = ['jan', 'fev', 'mar', 'abr', 'mai', 'jun', 'jul', 'ago', 'set', 'out', 'nov', 'dez'];
$icones = ['trofeu' => 'fa-trophy', 'medalha' => 'fa-medal', 'estrela' => 'fa-star', 'foguete' => 'fa-rocket', 'coracao' => 'fa-heart'];
$cargo = ['diretor' => 'Diretor(a)', 'coordenador' => 'Coordenador(a)', 'membro' => 'Membro'];
$resumo = $p['apresentacao'] ? mb_strimwidth(preg_replace('/\s+/', ' ', $p['apresentacao']), 0, 155, '…') : 'Página oficial do ' . $p['nome'] . '.';
?><!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= $e($p['nome']) ?></title>
<meta name="description" content="<?= $e($resumo) ?>">
<meta property="og:title" content="<?= $e($p['nome']) ?>">
<meta property="og:description" content="<?= $e($resumo) ?>">
<meta property="og:image" content="<?= $e($logo) ?>">
<meta name="theme-color" content="<?= $e($cor) ?>">
<link rel="icon" href="<?= $e($logo) ?>">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link rel="stylesheet" href="<?= ViewHelper::asset('css/gremio-publico.css') ?>">
<style>:root { --cor: <?= $e($cor) ?>; }</style>
</head>
<body>
<header class="gpp-topo">
    <div class="gpp-wrap gpp-hero">
        <img src="<?= $e($logo) ?>" alt="" class="gpp-logo" width="96" height="96">
        <div>
            <p class="gpp-eyebrow">Grêmio estudantil</p>
            <h1><?= $e($p['nome']) ?></h1>
            <?php if ($presidencia): ?><p class="gpp-pres"><i class="fas fa-user-tie"></i> Presidência: <?= $e(implode(' e ', $presidencia)) ?></p><?php endif; ?>
            <?php if ($p['instagram']): ?><a class="gpp-insta" href="https://instagram.com/<?= $e($p['instagram']) ?>" target="_blank" rel="noopener noreferrer"><i class="fab fa-instagram"></i> @<?= $e($p['instagram']) ?></a><?php endif; ?>
        </div>
    </div>
</header>

<main class="gpp-wrap">
    <?php if ($p['apresentacao']): ?>
        <section class="gpp-bloco gpp-sobre"><h2>Quem somos</h2><p><?= nl2br($e($p['apresentacao'])) ?></p></section>
    <?php endif; ?>

    <?php if ($eventos): ?>
        <section class="gpp-bloco">
            <h2><i class="fas fa-calendar-days"></i> Próximos eventos</h2>
            <ul class="gpp-eventos">
                <?php foreach ($eventos as $ev): $t = strtotime($ev['data']); ?>
                    <li><span class="gpp-data"><b><?= date('d', $t) ?></b><?= $meses[(int) date('n', $t) - 1] ?></span>
                        <span><strong><?= $e($ev['titulo']) ?></strong><small><?= $ev['tipo'] === 'reuniao' ? 'Reunião geral' : 'Evento' ?><?= $ev['hora'] ? ' · ' . substr($ev['hora'], 0, 5) : '' ?><?= $ev['local'] ? ' · ' . $e($ev['local']) : '' ?></small></span></li>
                <?php endforeach; ?>
            </ul>
        </section>
    <?php endif; ?>

    <?php if ($diretorias): ?>
        <section class="gpp-bloco">
            <h2><i class="fas fa-sitemap"></i> Diretorias</h2>
            <div class="gpp-dirs">
                <?php foreach ($diretorias as $d): ?>
                    <article class="gpp-dir">
                        <h3><?= $e($d['nome']) ?></h3>
                        <?php if ($d['descricao']): ?><p><?= $e($d['descricao']) ?></p><?php endif; ?>
                        <ul><?php foreach ($d['membros'] as $m): ?><li><span><?= $e($m['nome']) ?></span><small><?= $cargo[$m['cargo']] ?? $e($m['cargo']) ?></small></li><?php endforeach; ?></ul>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

    <?php if ($conquistas): ?>
        <section class="gpp-bloco">
            <h2><i class="fas fa-trophy"></i> Conquistas</h2>
            <div class="gpp-conq">
                <?php foreach ($conquistas as $c): ?>
                    <article><i class="fas <?= $icones[$c['icone'] ?? 'trofeu'] ?? 'fa-trophy' ?>"></i><h3><?= $e($c['titulo']) ?></h3><?php if ($c['texto']): ?><p><?= $e($c['texto']) ?></p><?php endif; ?></article>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

    <?php if ($contas): ?>
        <section class="gpp-bloco">
            <h2><i class="fas fa-scale-balanced"></i> Transparência</h2>
            <div class="gpp-contas">
                <div><span>Saldo em caixa</span><b><?= $brl($contas['saldo']) ?></b></div>
                <div><span>Total arrecadado</span><b class="e"><?= $brl($contas['entradas']) ?></b></div>
                <div><span>Total gasto</span><b class="s"><?= $brl($contas['saidas']) ?></b></div>
            </div>
            <p class="gpp-nota">Os comprovantes de cada gasto ficam disponíveis para os estudantes do grêmio.</p>
        </section>
    <?php endif; ?>
</main>

<footer class="gpp-rodape gpp-wrap">Página feita com <a href="<?= $e($basePath) ?>/">Prumo</a> · organização de turmas e grêmios escolares</footer>
</body>
</html>
