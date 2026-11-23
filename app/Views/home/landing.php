<?php
// app/Views/home/landing.php
// Página pública (visitante sem login). Página própria no sistema da taste-skill (public/assets),
// sem o layout interno do app. Cena 3D própria ligada à rolagem (#como-funciona: as telas do Prumo
// se abrem em camadas, public/js/landing-camadas.js); título que ondula sob o cursor (liquid-type);
// tema claro/escuro com a mesma chave do app (sgt-tema, public/js/landing-tema.js);
// animações de rolagem no resto da página (public/js/landing-rolagem.js) e efeitos de cursor/fundo
// (public/js/landing-efeitos.js: grade de pontos do topo, notificações flutuantes, luz nos painéis).
$basePath = App::getBasePath();
$appName  = App::getName();
$logado   = !empty($_SESSION['usuario']);
$a        = $basePath . '/assets';
$criar    = $basePath . ($logado ? '/turmas/nova' : '/login/cadastrar');
$e        = fn($t) => htmlspecialchars((string) $t, ENT_QUOTES, 'UTF-8');
$reais    = fn($v) => 'R$ ' . number_format((float) $v, 2, ',', '.');
// Planos de turma vindos do banco (o master muda preços no painel Comercial); os 4 primeiros com duração definida
require_once __DIR__ . "/../../Models/Plano.php";
$planosLanding = [];
try {
    foreach ((new Plano())->listar('turma') as $pl) {
        if (!empty($pl['meses']) && count($planosLanding) < 4) $planosLanding[] = $pl;
    }
} catch (Throwable $ex) { $planosLanding = []; error_log("landing planos: " . $ex->getMessage()); }
$menorPreco = $planosLanding ? min(array_map(fn($p) => (float) $p['preco'], $planosLanding)) : 99.90;
?><!doctype html>
<html lang="pt-BR" data-type="software" data-radius="soft" data-ground="sky" data-buttons="arrow" data-motion>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title><?= $e($appName) ?>: organização de turmas e grêmios escolares</title>
<meta name="description" content="Projetos, critérios, avaliações entre alunos, atas e agenda da turma num só lugar. O representante paga pela turma por Pix, a partir de R$ 99,90.">
<meta property="og:type" content="website">
<meta property="og:title" content="<?= $e($appName) ?>: organização de turmas e grêmios escolares">
<meta property="og:description" content="O representante monta projetos e avaliações; cada aluno acompanha pelo celular.">
<meta property="og:image" content="<?= $basePath ?>/img/share.jpg">
<meta name="twitter:card" content="summary_large_image">
<link rel="icon" href="<?= $basePath ?>/img/logo-prumo.png" type="image/png">
<link rel="manifest" href="<?= $basePath ?>/manifest.webmanifest">
<meta name="app-base" content="<?= $e($basePath) ?>">
<link rel="apple-touch-icon" href="<?= $basePath ?>/public/img/logo-prumo.png">
<meta name="theme-color" content="#f6f7fb">
<meta name="site-kind" content="software">
<meta name="page-type" content="software">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wdth,wght@85..100,400..700&family=IBM+Plex+Mono:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= $a ?>/system.css">
<link rel="stylesheet" href="<?= $a ?>/ui/cards.css">
<link rel="stylesheet" href="<?= $a ?>/motion/motion.css">
<link rel="stylesheet" href="<?= $a ?>/backgrounds/backgrounds.css">
<link rel="stylesheet" href="<?= $a ?>/layout/layout.css">
<link rel="stylesheet" href="<?= $a ?>/icons/icon.css">
<style>
:root {
  --p-bg: #f6f7fb; --p-surface: #eceef6; --p-ink: #0f1222; --p-muted: #646774;
  --p-accent: #3355e0; --p-accent-ink: #f6f7fb; --p-action: #3355e0; --p-action-ink: #f6f7fb;
  --p-inv-bg: #0f1222; --p-inv-surface: #1c1f2f; --p-inv-ink: #f6f7fb; --p-inv-muted: #9497a1;
  --p-inv-accent: #f6f7fb; --p-inv-accent-ink: #0f1222; --p-inv-action: #f6f7fb; --p-inv-action-ink: #0f1222;
  --font-display: "IBM Plex Sans", system-ui, sans-serif;
  --font-text: "IBM Plex Sans", system-ui, sans-serif;
  --font-mono: "IBM Plex Mono", ui-monospace, "SFMono-Regular", Menlo, Consolas, monospace;
  --display-weight: 500;
  --display-tracking: -0.025em;
  --display-tracking-xl: -0.035em;
}
/* Tema escuro: os mesmos tokens da paleta, invertidos (o kit monta tudo a partir deles) */
html[data-theme="dark"] {
  --p-bg: #0c0f1d; --p-surface: #161a2c; --p-ink: #eef0f8; --p-muted: #a6aabb;
  --p-accent: #7d95ff; --p-accent-ink: #0c0f1d; --p-action: #3f5fe6; --p-action-ink: #ffffff;
  --p-inv-bg: #f6f7fb; --p-inv-surface: #eceef6; --p-inv-ink: #0f1222; --p-inv-muted: #5c5f6c;
  --p-inv-accent: #0f1222; --p-inv-accent-ink: #f6f7fb; --p-inv-action: #0f1222; --p-inv-action-ink: #f6f7fb;
  --ground-bg: #131a2b; --ground-surface: #1b2338; --ground-ink: #e7ebf4; --ground-muted: #9ba6b9; --ground-accent: #7d95ff;
  color-scheme: dark;
}
</style>
<script>
// tema antes da primeira pintura (mesma chave do app; "misto" do app vira claro aqui)
(function () { var t = 'light'; try { t = localStorage.getItem('sgt-tema') === 'dark' ? 'dark' : 'light'; } catch (e) {}
  var h = document.documentElement; h.setAttribute('data-theme', t); h.setAttribute('data-tone', t); })();
</script>
<script>document.documentElement.classList.add('sc-js');setTimeout(()=>document.documentElement.classList.add('sc-safe'),2500)</script>
<link rel="stylesheet" href="<?= $a ?>/scenes/scenes.css">
<link rel="stylesheet" href="<?= $basePath ?>/css/landing-camadas.css">
<link rel="stylesheet" href="<?= $basePath ?>/css/landing-rolagem.css">
<link rel="stylesheet" href="<?= $basePath ?>/css/landing-efeitos.css">
<script>document.documentElement.classList.add('m-enter');setTimeout(()=>document.documentElement.classList.add('m-enter-done'),700)</script>
<script src="<?= $a ?>/backgrounds/backgrounds.js"></script>
<script>document.documentElement.classList.add('mo-js');setTimeout(()=>document.documentElement.classList.add('mo-safe'),2500)</script>
<link rel="modulepreload" href="<?= $a ?>/moments/moments.js">
<link rel="modulepreload" href="<?= $a ?>/moments/lib.js">
<link rel="modulepreload" href="<?= $a ?>/moments/liquid-type/moment.js">
<style>
/* Página: só o que fica dentro dos visuais (telas do Prumo em HTML) */
.nav__logo img { width: 28px; height: 28px; }
.pr { --line: color-mix(in oklab, var(--ink) 12%, transparent); --pb: #29946e; --pm: var(--accent); --pa: #e08a00; --pu: #d63548;
      font: 400 13px/1.45 var(--font-text); color: var(--ink); background: var(--bg); }
.pr * { box-sizing: border-box; }
.pr-bar { display: flex; align-items: center; gap: 12px; height: 44px; padding: 0 16px; border-bottom: 1px solid var(--line); color: var(--muted); }
.pr-bar b { color: var(--ink); font-weight: 500; }
.pr-dots { display: inline-flex; gap: 5px; } .pr-dots i { width: 9px; height: 9px; border-radius: 50%; background: var(--line); }
.pr-tab { padding: 4px 10px; border-radius: 6px; } .pr-tab.on { background: var(--surface); color: var(--ink); }
.pr-chip { display: inline-block; padding: 1px 8px; border-radius: 999px; font-size: 11px; font-weight: 500; background: var(--surface); color: var(--muted); }
.pr-chip.ok { background: color-mix(in oklab, var(--pb) 15%, transparent); color: #1d6b4f; }
.pr-chip.wait { background: color-mix(in oklab, var(--pa) 18%, transparent); color: #8a5300; }
.pr-k { font: 500 11px/1.4 var(--font-text); color: var(--muted); }
.pr-ev { --c: var(--pm); display: grid; grid-template-columns: auto 1fr auto; gap: 10px; align-items: center; padding: 9px 10px; border: 1px solid var(--line); border-left: 4px solid var(--c); border-radius: 10px; background: var(--bg); }
.pr-ev small { display: block; color: var(--muted); font-size: 12px; }
.pr-ev .pr-ico { width: 28px; height: 28px; border-radius: 8px; display: grid; place-items: center; background: color-mix(in oklab, var(--c) 14%, transparent); color: var(--c); font-weight: 600; font-size: 12px; }
.pr-ev.alta { --c: var(--pa); } .pr-ev.urg { --c: var(--pu); } .pr-ev.baixa { --c: var(--pb); }
.pr-ev time { font: 500 12px var(--font-mono); color: var(--muted); }
.pr-dia { padding: 12px 14px; border-radius: 12px; background: color-mix(in oklab, var(--pa) 10%, var(--bg)); border: 1px solid color-mix(in oklab, var(--pa) 30%, transparent); }
.pr-dia b { display: block; font-weight: 500; }
.pr-prog { height: 6px; border-radius: 3px; background: var(--surface); overflow: hidden; } .pr-prog i { display: block; height: 100%; background: var(--accent); border-radius: 3px; }

/* Herói: o painel da turma */
.pr-win { position: absolute; inset: 0; display: grid; grid-template-rows: auto 1fr; border: 1px solid var(--line); border-radius: var(--r-lg); overflow: hidden; }
.pr-win__body { display: grid; grid-template-columns: 168px 1fr; min-height: 0; }
.pr-side { padding: 14px 10px; display: grid; align-content: start; gap: 2px; border-right: 1px solid var(--line); color: var(--muted); }
.pr-side span { padding: 6px 10px; border-radius: 6px; } .pr-side span.on { background: var(--surface); color: var(--ink); }
.pr-side small { margin: 12px 10px 4px; font-size: 11px; }
.pr-main { padding: 18px 20px; display: grid; align-content: start; gap: 14px; overflow: hidden; }
.pr-main h3 { margin: 0; font: 500 20px/1.2 var(--font-display); letter-spacing: -0.01em; }
.pr-list { display: grid; gap: 8px; }
.hero__media.pr { background: var(--bg); }

/* Recursos: telas pequenas dentro dos cartões */
#recursos .panel { --p-h: 300px; display: grid; place-items: center; padding: 24px; }
.pr-mini { width: min(420px, 100%); display: grid; gap: 8px; padding: 16px; border: 1px solid var(--line); border-radius: 14px; background: var(--bg); box-shadow: 0 10px 30px rgba(15, 18, 34, .06); }
.pr-mini__head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px; }
.pr-mini__head b { font-weight: 500; }
.pr-row { display: flex; justify-content: space-between; align-items: center; gap: 10px; padding: 8px 0; border-bottom: 1px solid var(--line); }
.pr-row:last-child { border-bottom: 0; }
.pr-mono { font-family: var(--font-mono); font-size: 12px; }
.pr-clock { display: flex; gap: 8px; }
.pr-clock span { flex: 1; display: grid; justify-items: center; padding: 10px 4px; border-radius: 10px; background: var(--surface); }
.pr-clock b { font: 500 24px/1 var(--font-mono); } .pr-clock small { font-size: 11px; color: var(--muted); margin-top: 4px; }
.pr-pres { display: flex; flex-wrap: wrap; gap: 6px; }
.pr-pres span { padding: 3px 9px; border-radius: 999px; font-size: 12px; background: color-mix(in oklab, var(--pb) 14%, transparent); color: #1d6b4f; }
.pr-pres span.off { background: var(--surface); color: var(--muted); text-decoration: line-through; }

/* "Baixar o app" no topo */
.hero__app { display: inline-flex; align-items: center; gap: 10px; margin-top: var(--s-4); padding: 8px 14px 8px 10px; border-radius: 999px;
  color: var(--ink); text-decoration: none; font-size: var(--t-small); border: 1px solid color-mix(in oklab, var(--ink) 14%, transparent);
  background: color-mix(in oklab, var(--accent) 6%, var(--bg)); transition: border-color .2s ease, background-color .2s ease; }
.hero__app svg { color: var(--accent); flex: none; }
.hero__app span { color: var(--muted); } .hero__app b { color: var(--ink); font-weight: 600; }
@media (hover: hover) { .hero__app:hover { border-color: color-mix(in oklab, var(--accent) 45%, transparent); background: color-mix(in oklab, var(--accent) 11%, var(--bg)); } }
.hero__app:focus-visible { outline: 2px solid var(--accent); outline-offset: 3px; }

/* Créditos do autor (fim da página) */
.credito { display: flex; justify-content: space-between; align-items: center; gap: var(--s-6); flex-wrap: wrap; padding: var(--s-6);
  border-radius: var(--r-lg); background: color-mix(in oklab, var(--accent) 9%, var(--bg)); border: 1px solid color-mix(in oklab, var(--accent) 28%, transparent); }
.credito__selo { margin: 0; font: 600 var(--t-small)/1.3 var(--font-text); letter-spacing: .08em; text-transform: uppercase; color: var(--accent); }
.credito__nome { margin: 4px 0 2px; font: var(--display-weight) clamp(26px, 3.4vw, 40px)/1.05 var(--font-display); letter-spacing: var(--display-tracking); color: var(--ink); }
.credito__sub { margin: 0; color: var(--muted); }
.credito__links { display: flex; gap: var(--s-3); flex-wrap: wrap; }
.credito__links .btn { display: inline-flex; align-items: center; gap: 8px; }
@media (max-width: 639px) { .credito { padding: var(--s-5); } .credito__links, .credito__links .btn { width: 100%; justify-content: center; } }

/* Afirmação com o título que ondula */
#sem-planilha .mo-lt__type { font-size: clamp(52px, 8.4vw, 124px); line-height: 1; max-width: 10ch; margin: 0; letter-spacing: var(--display-tracking-xl); }
#sem-planilha p { margin: 0; max-width: 40ch; font-size: var(--t-lead); color: var(--ink); }
#sem-planilha .sp-lado { display: grid; gap: var(--s-7); align-content: end; }
#sem-planilha .fatos { display: grid; grid-template-columns: 1fr; gap: var(--s-5); margin: 0; }
#sem-planilha .fatos div { padding-top: var(--s-4); border-top: 1px solid var(--line); }
@media (max-width: 1023px) { #sem-planilha .sp-lado { margin-top: var(--s-6); } #sem-planilha .fatos { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
.close__word { line-height: 1.04; }
#planos .rows__name small { display: block; margin-top: 2px; color: var(--muted); font-size: var(--t-small); }
.footer__legal .legais { display: flex; flex-wrap: wrap; gap: var(--s-2) var(--s-5); }
@media (max-width: 379px) { .nav__logo img { width: 22px; height: 22px; } .nav:not(.is-open) .nav__actions .btn { padding-inline: 10px; } }
.pr-pix { display: grid; grid-template-columns: 96px 1fr; gap: 14px; align-items: center; }
.pr-qr { width: 96px; height: 96px; border-radius: 8px; background:
  repeating-linear-gradient(90deg, var(--ink) 0 6px, transparent 6px 12px), repeating-linear-gradient(0deg, var(--ink) 0 6px, transparent 6px 12px);
  background-blend-mode: normal; -webkit-mask: repeating-conic-gradient(#000 0 25%, transparent 0 50%) 0 0 / 24px 24px; mask: repeating-conic-gradient(#000 0 25%, transparent 0 50%) 0 0 / 24px 24px; opacity: .85; }
.pr-code { font: 400 11px/1.4 var(--font-mono); color: var(--muted); word-break: break-all; padding: 8px; border-radius: 8px; background: var(--surface); }
#planos .frame { display: grid; place-items: center; padding: 24px; background: var(--surface); border-radius: var(--r-lg); min-height: 440px; }
@media (max-width: 639px) { #planos .frame { min-height: 0; padding: 12px; } }
#sem-planilha .fatos dt { font-size: var(--t-small); color: var(--muted); }
#sem-planilha .fatos dd { margin: 4px 0 0; font: var(--display-weight) var(--t-h3)/1.2 var(--font-display); }
@media (max-width: 1023px) { #sem-planilha .mo-lt__type { font-size: 15vw; max-width: 9ch; } #sem-planilha .fatos dd { font-size: 28px; } }
@media (max-width: 639px) { #sem-planilha .fatos { grid-template-columns: 1fr; gap: var(--s-4); } }
@media (min-width: 1024px) and (max-width: 1279px) { #recursos .panel { --p-h: 196px; } }

/* Grêmio: quadro das diretorias */
.pr-board { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px; padding: 20px; height: 100%; align-content: start; }
.pr-dir { display: grid; gap: 6px; padding: 14px; border: 1px solid var(--line); border-radius: 12px; background: var(--bg); }
.pr-dir h4 { margin: 0 0 4px; font: 500 15px/1.2 var(--font-text); }
.pr-pessoa { display: flex; align-items: center; gap: 8px; font-size: 13px; }
.pr-pessoa i { width: 22px; height: 22px; border-radius: 50%; display: grid; place-items: center; font-size: 10px; font-style: normal; background: var(--surface); color: var(--ink); font-weight: 600; }
.pr-pessoa em { margin-left: auto; font-style: normal; font-size: 11px; color: var(--muted); }
#gremios .frame { min-height: 520px; background: var(--surface); border-radius: var(--r-lg); overflow: hidden; }

@media (max-width: 1023px) { .pr-win__body { grid-template-columns: 1fr; } .pr-side { display: none; } }
@media (max-width: 639px) {
  #recursos .panel { --p-h: 168px; padding: 8px; }
  #recursos .pr-mini { padding: 10px; gap: 4px; } #recursos .pr-mini .pr-row:nth-child(n+4), #recursos .pr-mini .pr-ev:nth-child(n+4) { display: none; }
  .pr-main { padding: 14px; gap: 10px; } .pr-tab:not(.on) { display: none; }
  .pr-board { grid-template-columns: 1fr; padding: 12px; } #gremios .frame { min-height: 0; }
  .pr-dir:nth-child(n+5) { display: none; }
}

/* Botão de tema (claro/escuro): fica visível também no celular, ao lado do botão principal */
.tema-btn { display: inline-grid; place-items: center; width: 40px; height: 40px; flex: none; border-radius: var(--r-btn); border: 1px solid var(--line-strong);
            background: transparent; color: var(--ink); cursor: pointer; transition: background-color var(--dur) var(--ease); }
.tema-btn:hover { background: var(--surface); }
.tema-btn svg { width: 18px; height: 18px; }
.tema-btn__sol { display: none; }
html[data-theme="dark"] .tema-btn__sol { display: block; } html[data-theme="dark"] .tema-btn__lua { display: none; }
.nav:not(.is-open) .nav__actions > .tema-btn { display: inline-grid; }
@media (max-width: 639px) { .tema-btn { width: 44px; height: 44px; } }
@media (max-width: 419px) { .nav:not(.is-open) .nav__actions > .btn--primary { display: none; } }
/* selos coloridos legíveis no tema escuro */
html[data-theme="dark"] .pr-chip.ok, html[data-theme="dark"] .pr-pres span { color: #7fdcb2; }
html[data-theme="dark"] .pr-chip.wait { color: #f3c070; }
html[data-theme="dark"] .pr-mini { box-shadow: 0 10px 30px rgba(0, 0, 0, .35); }
/* Alturas: o rodapé cresce com o conteúdo (em telas baixas o nome gigante subia por cima dos links)
   e, em telas baixas, as seções também, em vez de cortar ou sobrepor */
footer.closing { height: auto; min-height: 100vh; min-height: 100svh; }
footer.closing > .wrap { height: auto; min-height: calc(100svh - var(--padt) - var(--padb)); }
.close__word { font-size: clamp(56px, min(14.5vw, 24svh), 216px); }
@media (max-height: 820px) {
  main > :is(section, .section):not(.sc):not(.hero) { height: auto; min-height: calc(var(--h, 1) * 100svh); }
  main > :is(section, .section):not(.sc):not(.hero) > .wrap { height: auto; }
}
.footer__legal { align-items: center; }
h1, h2, h3 { hyphens: manual; -webkit-hyphens: manual; }
html[data-theme="dark"] .footer--statement { --action: var(--p-action); --action-ink: var(--p-action-ink); }
html[data-theme="dark"] .cm__tela { box-shadow: 0 1px 2px rgba(0, 0, 0, .3), 0 24px 48px -28px rgba(0, 0, 0, .6); }
</style>
</head>
<body>
<!-- type: software
     deal: hero split, nav nav-centre, close close-word, footer footer-statement, rhythm space, buttons arrow, mark: logo do cliente, ground sky
     palette: cores da marca (logo Prumo violeta, levado ao azul pela regra sem violeta), Host Grotesk + JetBrains Mono
     cena 3D própria (camadas) em #como-funciona · moment: liquid-type (cursor) em #sem-planilha -->
<header class="nav nav--centre">
  <div class="wrap nav__bar">
    <a class="skip-link" href="#top">Pular para o conteúdo</a>
    <a class="nav__logo" href="#top"><img src="<?= $basePath ?>/img/logo-prumo.png" alt="" width="28" height="28"><?= $e($appName) ?></a>
    <nav class="nav__links" aria-label="Principal">
      <div class="nav__item">
        <a class="nav__link" href="#recursos">Recursos <svg class="chev" viewBox="0 0 12 12" aria-hidden="true"><path d="M3 4.5l3 3 3-3" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg></a>
        <div class="nav__menu">
          <div class="wrap grid">
            <div class="menu-col span-3">
              <p class="t-label">Turma</p>
              <a href="#como-funciona">Como funciona<span>Da turma criada ao boletim</span></a>
              <a href="#recursos">Convites<span>A turma entra pelo email</span></a>
              <a href="#recursos">Agenda<span>Provas e entregas por prioridade</span></a>
            </div>
            <div class="menu-col span-3">
              <p class="t-label">Grêmio</p>
              <a href="#gremios">Diretorias<span>Criadas pelo presidente</span></a>
              <a href="#recursos">Reuniões<span>Pauta, presença e ata</span></a>
            </div>
          </div>
        </div>
      </div>
      <div class="nav__item"><a class="nav__link" href="#como-funciona">Como funciona</a></div>
      <div class="nav__item"><a class="nav__link" href="#gremios">Grêmios</a></div>
      <div class="nav__item"><a class="nav__link" href="#planos">Planos</a></div>
    </nav>
    <div class="nav__actions">
      <button class="tema-btn" type="button" data-alternar-tema aria-label="Mudar para o tema escuro" title="Tema claro ou escuro">
        <svg class="tema-btn__lua" viewBox="0 0 20 20" aria-hidden="true"><path d="M16.5 12.6A7 7 0 0 1 7.4 3.5a7 7 0 1 0 9.1 9.1Z" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/></svg>
        <svg class="tema-btn__sol" viewBox="0 0 20 20" aria-hidden="true"><circle cx="10" cy="10" r="3.6" fill="none" stroke="currentColor" stroke-width="1.6"/><path d="M10 1.8v2.1M10 16.1v2.1M1.8 10h2.1M16.1 10h2.1M4.2 4.2l1.5 1.5M14.3 14.3l1.5 1.5M4.2 15.8l1.5-1.5M14.3 5.7l1.5-1.5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
      </button>
      <a class="nav__link" href="<?= $basePath ?><?= $logado ? '/' : '/login' ?>"><?= $logado ? 'Painel' : 'Entrar' ?></a>
      <a class="btn btn--primary" href="<?= $criar ?>">Criar minha turma</a>
    </div>
    <button class="nav__toggle" type="button" aria-expanded="false" aria-label="Menu">
      <svg width="18" height="18" viewBox="0 0 18 18" aria-hidden="true"><path d="M2 5h14M2 13h14" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
    </button>
  </div>
</header>

<main id="top">
  <!-- HERÓI: o painel da turma, em HTML -->
  <section class="hero hero--split" data-enter aria-labelledby="hero-h">
    <div class="wrap"><div class="hero-text grid hero__grid">
      <div class="hero__text span-5">
        <p class="hero__kicker"><span class="dot" aria-hidden="true"></span>Plano por turma a partir de <?= $reais($menorPreco) ?>, por Pix</p>
        <h1 id="hero-h">Gestão de turmas e grêmios escolares</h1>
        <p class="hero__lead">O representante monta projetos, critérios e avaliações. Cada aluno vê no celular o que precisa fazer e como está sendo avaliado.</p>
        <div class="btn-row">
          <a class="btn btn--primary" href="<?= $criar ?>">Criar minha turma<svg class="m-arrow" viewBox="0 0 12 12" aria-hidden="true"><path class="m-arrow__stem" d="M1 6h8"/><path class="m-arrow__head" d="M4.5 2.5 8 6l-3.5 3.5"/></svg></a>
          <a class="btn btn--secondary" href="#planos">Ver os planos</a>
        </div>
        <a class="hero__app" href="#" data-instalar-app>
          <svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><rect x="6" y="2.5" width="12" height="19" rx="2.6" fill="none" stroke="currentColor" stroke-width="1.7"/><path d="M12 7.5v6.5m0 0-2.6-2.6M12 14l2.6-2.6M10.5 18.5h3" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
          <span><b>Baixar o app</b> · Android, iPhone e computador</span>
        </a>
      </div>
      <div class="hero__media pr span-6 start-7" role="img" aria-label="Painel da turma 231-3ANO-INFO-M no Prumo, com aviso do dia, agenda e projeto">
        <div class="pr-win">
          <div class="pr-bar"><span class="pr-dots"><i></i><i></i><i></i></span><b><?= $e($appName) ?></b><span class="pr-tab on">Painel</span><span class="pr-tab">Agenda</span><span class="pr-tab">Projetos</span></div>
          <div class="pr-win__body">
            <div class="pr-side"><small>Turmas</small><span class="on">231-3ANO-INFO-M</span><span>Grêmio ETEC</span><small>Turma</small><span>Avisos</span><span>Reuniões</span><span>Alunos</span></div>
            <div class="pr-main">
              <div><span class="pr-k">Você é representante · 38 alunos</span><h3>231-3ANO-INFO-M</h3></div>
              <div class="pr-dia"><span class="pr-k">Aviso do dia</span><b>Prova de física amanhã, 08:00</b><span>Capítulos 3 e 4. Tragam calculadora.</span></div>
              <div class="pr-list">
                <div class="pr-ev alta"><span class="pr-ico">P</span><span>Prova de física<small>Toda a turma · alta</small></span><time>08:00</time></div>
                <div class="pr-ev urg"><span class="pr-ico">E</span><span>Entrega do protótipo<small>Feira de Ciências · urgente</small></span><time>sex</time></div>
                <div class="pr-ev"><span class="pr-ico">R</span><span>Reunião dos diretores<small>Sala 12</small></span><time>14:30</time></div>
                <div class="pr-ev baixa"><span class="pr-ico">T</span><span>Revisar ata de terça<small>Só você</small></span><time>qua</time></div>
              </div>
              <div><span class="pr-k">Feira de Ciências · 14 de 22 critérios avaliados</span><div class="pr-prog"><i style="width:64%"></i></div></div>
            </div>
          </div>
        </div>
        <div class="ef-notas" aria-hidden="true">
          <div class="ef-nota" style="--d: 1.4; --dx: -1; --dy: -1; --i: 0"><span class="ef-nota__ico ok"><svg viewBox="0 0 16 16"><path d="M3.5 8.5l3 3 6-7" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span><span><b>Ana Clara avaliou o grupo</b><small>Energia solar · agora</small></span></div>
          <div class="ef-nota" style="--d: 2; --dx: 1; --dy: -0.4; --i: 1"><span class="ef-nota__ico">MB</span><span><b>Nota publicada</b><small>Protótipo funcionando</small></span></div>
          <div class="ef-nota" style="--d: 1.1; --dx: 1; --dy: 1; --i: 2"><span class="ef-nota__ico at"><svg viewBox="0 0 16 16"><path d="M4 2.5h6l2.5 2.5v8.5H4z M6.5 8h4 M6.5 10.5h4" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/></svg></span><span><b>Ata aprovada</b><small>4 de 5 presentes</small></span></div>
        </div>
        <div class="ly-float">
          <div class="ly-float__row"><span class="ly-live">Contagem</span><span>12 dias</span></div>
          <div class="ly-float__title">Feira de Ciências, montagem dos estandes</div>
        </div>
      </div>
    </div></div>
  </section>

  <!-- CENA 3D (própria): as telas do Prumo se abrem em camadas com a rolagem, um passo por camada.
       Parada (celular, movimento reduzido): os passos e a tela do boletim. JS: public/js/landing-camadas.js -->
  <section class="sc cm" id="como-funciona" data-camadas style="--h: 1" aria-labelledby="cm-h">
    <div class="sc__stage cm__stage">
      <div class="cm__texto">
        <p class="t-label">Como funciona</p>
        <h2 id="cm-h">Do zero ao boletim em quatro passos</h2>
        <ol class="cm__passos">
          <li data-passo="0"><span class="cm__n">1</span><div><b>Crie a turma</b><span>Convide os colegas pelo email ou por código.</span></div></li>
          <li data-passo="1"><span class="cm__n">2</span><div><b>Monte o projeto</b><span>Critérios com peso, grupos e diretores.</span></div></li>
          <li data-passo="2"><span class="cm__n">3</span><div><b>Organize a rotina</b><span>Provas, entregas e reuniões na agenda.</span></div></li>
          <li data-passo="3"><span class="cm__n">4</span><div><b>Feche o boletim</b><span>Cada aluno vê a nota de cada critério.</span></div></li>
        </ol>
      </div>
      <div class="cm__dica" aria-hidden="true">
        <span class="cm__dica-seta"><svg viewBox="0 0 20 20"><path d="M5 8l5 5 5-5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
        <span class="cm__dica-txt">Role para baixo para ver os 4 passos</span>
        <span class="cm__dica-pts"><i></i><i></i><i></i><i></i></span>
      </div>
      <div class="cm__cena" aria-hidden="true">
        <div class="cm__pilha">
          <!-- camada 1: turma e convites -->
          <div class="cm__tela pr" data-camada="0">
            <div class="pr-bar"><span class="pr-dots"><i></i><i></i><i></i></span><b><?= $e($appName) ?></b><span class="pr-tab on">Turma</span><span class="pr-tab">Convites</span></div>
            <div class="cm__corpo">
              <div class="cm__topo"><div><span class="pr-k">Turma nova</span><h4>231-3ANO-INFO-M</h4></div><span class="pr-chip ok">Código 7K2-PQ9</span></div>
              <div class="pr-row"><span class="pr-mono">ana.c•••@gmail.com</span><span class="pr-chip ok">Entrou</span></div>
              <div class="pr-row"><span class="pr-mono">bruno.l•••@outlook.com</span><span class="pr-chip ok">Entrou</span></div>
              <div class="pr-row"><span class="pr-mono">carla.s•••@gmail.com</span><span class="pr-chip wait">Convite enviado</span></div>
              <div class="pr-row"><span class="pr-mono">davi.r•••@hotmail.com</span><span class="pr-chip wait">Convite enviado</span></div>
            </div>
            <span class="cm__rotulo">Turma</span>
          </div>
          <!-- camada 2: projeto e critérios -->
          <div class="cm__tela pr" data-camada="1">
            <div class="pr-bar"><span class="pr-dots"><i></i><i></i><i></i></span><b><?= $e($appName) ?></b><span class="pr-tab on">Projeto</span><span class="pr-tab">Grupos</span></div>
            <div class="cm__corpo">
              <div class="cm__topo"><div><span class="pr-k">Novo projeto</span><h4>Feira de Ciências</h4></div><span class="cm__escala"><i class="on">Menção</i><i>0–10</i><i>0–100</i></span></div>
              <div class="cm__crit"><span>Protótipo funcionando</span><span class="bar" style="--v:.4"><b></b></span><b>4</b></div>
              <div class="cm__crit"><span>Apresentação</span><span class="bar" style="--v:.3"><b></b></span><b>3</b></div>
              <div class="cm__crit"><span>Ata das reuniões</span><span class="bar" style="--v:.2"><b></b></span><b>2</b></div>
              <div class="cm__crit"><span>Participação</span><span class="bar" style="--v:.1"><b></b></span><b>1</b></div>
            </div>
            <span class="cm__rotulo">Projeto</span>
          </div>
          <!-- camada 3: agenda do mês -->
          <div class="cm__tela pr" data-camada="2">
            <div class="pr-bar"><span class="pr-dots"><i></i><i></i><i></i></span><b><?= $e($appName) ?></b><span class="pr-tab on">Agenda</span><span class="pr-tab">Semana</span></div>
            <div class="cm__corpo">
              <div class="cm__topo"><div><span class="pr-k">Dezembro</span><h4>Agenda da turma</h4></div><span class="pr-chip">6 eventos</span></div>
              <div class="cm__mes">
                <?php for ($d = 1; $d <= 28; $d++):
                    $cls = [3 => 'alta', 5 => 'urg', 9 => 'med', 12 => 'baixa', 18 => 'alta', 22 => 'med'][$d] ?? ''; ?>
                <span class="<?= $cls ? 'ev ' . $cls : '' ?>"><?= $d ?></span>
                <?php endfor; ?>
              </div>
            </div>
            <span class="cm__rotulo">Agenda</span>
          </div>
          <!-- camada 4: boletim -->
          <div class="cm__tela pr" data-camada="3">
            <div class="pr-bar"><span class="pr-dots"><i></i><i></i><i></i></span><b><?= $e($appName) ?></b><span class="pr-tab on">Boletim</span><span class="pr-tab">Atas</span></div>
            <div class="cm__corpo">
              <div class="cm__topo"><div><span class="pr-k">Feira de Ciências</span><h4>Ana Clara · média MB</h4></div><span class="pr-chip ok">Fechado</span></div>
              <div class="cm__crit"><span>Protótipo funcionando</span><span class="bar" style="--v:.92"><b></b></span><b>MB</b></div>
              <div class="cm__crit"><span>Apresentação</span><span class="bar" style="--v:.86"><b></b></span><b>MB</b></div>
              <div class="cm__crit"><span>Ata das reuniões</span><span class="bar" style="--v:.74"><b></b></span><b>B</b></div>
              <div class="cm__crit"><span>Participação</span><span class="bar" style="--v:.9"><b></b></span><b>MB</b></div>
              <span class="pr-k">Avaliado por 3 colegas, o diretor do grupo e o representante</span>
            </div>
            <span class="cm__rotulo">Boletim</span>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- RECURSOS: quatro partes do dia a dia, em duas telas -->
  <section class="section g2" id="recursos" aria-labelledby="recursos-h">
    <div class="wrap">
      <div class="group">
        <div class="section-head">
          <h2 id="recursos-h">Do convite à reunião. <span class="muted">O representante cria, a turma acompanha.</span></h2>
        </div>
        <div class="grid">
          <article class="card card--row card--open span-6">
            <h3>Convites por email</h3>
            <p>Cole os emails da lista de chamada; cada aluno já entra na turma e só completa o cadastro.</p>
            <div class="panel">
              <div class="pr pr-mini" role="img" aria-label="Lista de convites com o status de cada email">
                <div class="pr-mini__head"><b>Convites · 231-3ANO-INFO-M</b><span class="pr-chip">3 de 38</span></div>
                <div class="pr-row"><span class="pr-mono">ana.c•••@gmail.com</span><span class="pr-chip ok">Entrou</span></div>
                <div class="pr-row"><span class="pr-mono">bruno.l•••@outlook.com</span><span class="pr-chip ok">Entrou</span></div>
                <div class="pr-row"><span class="pr-mono">carla.s•••@gmail.com</span><span class="pr-chip wait">Convite enviado</span></div>
              </div>
            </div>
          </article>
          <article class="card card--row card--open span-6">
            <h3>Agenda com prioridade</h3>
            <p>Agenda da turma com cor por prioridade e lembrete na véspera, mais a agenda pessoal de cada aluno.</p>
            <div class="panel">
              <div class="pr pr-mini" role="img" aria-label="Agenda da semana com eventos coloridos por prioridade">
                <div class="pr-mini__head"><b>Esta semana</b><span class="pr-chip">5 eventos</span></div>
                <div class="pr-ev urg"><span class="pr-ico">E</span><span>Entrega do protótipo<small>Sexta · urgente</small></span><time>08:00</time></div>
                <div class="pr-ev alta"><span class="pr-ico">P</span><span>Prova de física<small>Quinta · alta</small></span><time>10:00</time></div>
                <div class="pr-ev baixa"><span class="pr-ico">S</span><span>Estudar capítulo 4<small>Só você · baixa</small></span><time>qua</time></div>
              </div>
            </div>
          </article>
        </div>
      </div>
      <div class="grid">
        <article class="card card--row card--open span-6">
          <h3>Atas de reunião</h3>
          <p>Pauta, presença e decisões ficam salvas e entram sozinhas no calendário da turma.</p>
          <div class="panel">
            <div class="pr pr-mini" role="img" aria-label="Ata de reunião com pauta e lista de presença">
              <div class="pr-mini__head"><b>Reunião dos diretores · ter 14:30</b><span class="pr-chip ok">Realizada</span></div>
              <div class="pr-row"><span>1. Divisão dos estandes</span><span class="pr-k">decidido</span></div>
              <div class="pr-row"><span>2. Compra de materiais</span><span class="pr-k">R$ 86,40</span></div>
              <div class="pr-pres"><span>Ana Clara</span><span>Bruno</span><span>Davi</span><span class="off">Elisa</span></div>
            </div>
          </div>
        </article>
        <article class="card card--row card--open span-6">
          <h3>Avisos com contagem regressiva</h3>
          <p>Um aviso para a turma inteira, com o relógio contando até a entrega, a prova ou a feira.</p>
          <div class="panel">
            <div class="pr pr-mini" role="img" aria-label="Aviso com contagem regressiva para a Feira de Ciências">
              <div class="pr-mini__head"><b>Feira de Ciências</b><span class="pr-k">até 05/12, 08:00</span></div>
              <div class="pr-clock"><span><b>12</b><small>dias</small></span><span><b>07</b><small>horas</small></span><span><b>41</b><small>min</small></span></div>
            </div>
          </div>
        </article>
      </div>
    </div>
  </section>

  <!-- AFIRMAÇÃO: o título ondula sob o cursor (liquid-type) -->
  <section class="section" id="sem-planilha" aria-labelledby="sp-h">
    <div class="wrap">
      <div class="ef-faixa" aria-hidden="true">
        <div class="ef-faixa__linha" data-dir="-1"><span class="ef-selo"><i></i>Boletim em PDF</span><span class="ef-selo"><i></i>Avaliação entre pares</span><span class="ef-selo"><i></i>Atas com presença</span><span class="ef-selo"><i></i>Agenda por prioridade</span><span class="ef-selo"><i></i>Convites por email</span><span class="ef-selo"><i></i>Pagamento por Pix</span><span class="ef-selo"><i></i>Contagem regressiva</span><span class="ef-selo"><i></i>Diretorias do grêmio</span><span class="ef-selo"><i></i>Menção, 0–10 ou 0–100</span><span class="ef-selo"><i></i>Vice-representante</span><span class="ef-selo"><i></i>Notificações por email</span><span class="ef-selo"><i></i>Tema escuro</span><span class="ef-selo"><i></i>Boletim em PDF</span><span class="ef-selo"><i></i>Avaliação entre pares</span><span class="ef-selo"><i></i>Atas com presença</span><span class="ef-selo"><i></i>Agenda por prioridade</span><span class="ef-selo"><i></i>Convites por email</span><span class="ef-selo"><i></i>Pagamento por Pix</span><span class="ef-selo"><i></i>Contagem regressiva</span><span class="ef-selo"><i></i>Diretorias do grêmio</span><span class="ef-selo"><i></i>Menção, 0–10 ou 0–100</span><span class="ef-selo"><i></i>Vice-representante</span><span class="ef-selo"><i></i>Notificações por email</span><span class="ef-selo"><i></i>Tema escuro</span></div>
        <div class="ef-faixa__linha" data-dir="1"><span class="ef-selo"><i></i>Contagem regressiva</span><span class="ef-selo"><i></i>Diretorias do grêmio</span><span class="ef-selo"><i></i>Menção, 0–10 ou 0–100</span><span class="ef-selo"><i></i>Vice-representante</span><span class="ef-selo"><i></i>Notificações por email</span><span class="ef-selo"><i></i>Tema escuro</span><span class="ef-selo"><i></i>Boletim em PDF</span><span class="ef-selo"><i></i>Avaliação entre pares</span><span class="ef-selo"><i></i>Atas com presença</span><span class="ef-selo"><i></i>Agenda por prioridade</span><span class="ef-selo"><i></i>Convites por email</span><span class="ef-selo"><i></i>Pagamento por Pix</span><span class="ef-selo"><i></i>Contagem regressiva</span><span class="ef-selo"><i></i>Diretorias do grêmio</span><span class="ef-selo"><i></i>Menção, 0–10 ou 0–100</span><span class="ef-selo"><i></i>Vice-representante</span><span class="ef-selo"><i></i>Notificações por email</span><span class="ef-selo"><i></i>Tema escuro</span><span class="ef-selo"><i></i>Boletim em PDF</span><span class="ef-selo"><i></i>Avaliação entre pares</span><span class="ef-selo"><i></i>Atas com presença</span><span class="ef-selo"><i></i>Agenda por prioridade</span><span class="ef-selo"><i></i>Convites por email</span><span class="ef-selo"><i></i>Pagamento por Pix</span></div>
      </div>
      <div class="grid grid--top">
        <div class="span-7">
          <h2 id="sp-h" class="mo-lt__type" data-moment="liquid-type" data-moment-opts='{"strength": 1}'>Turma inteira num lugar, sem planilha</h2>
        </div>
        <div class="sp-lado span-4 start-9">
          <p>Critérios, notas, atas e avisos ficam na turma, com o histórico de quem mudou o quê e quando.</p>
          <dl class="fatos">
            <div><dt>Por turma</dt><dd>Até 50 pessoas</dd></div>
            <div><dt>Representantes</dt><dd>2 por turma</dd></div>
            <div><dt>Pagamento</dt><dd>Só por Pix</dd></div>
          </dl>
        </div>
      </div>
    </div>
  </section>

  <!-- GRÊMIOS: texto à esquerda, o quadro das diretorias à direita -->
  <section class="section" id="gremios" aria-labelledby="gremios-h">
    <div class="wrap">
      <div class="grid grid--top">
        <div class="span-5 stack-6">
          <h2 id="gremios-h">Grêmio estudantil com diretorias e reuniões</h2>
          <p class="t-lead">O presidente cria as diretorias e distribui diretores, coordenadores e membros. Reuniões com pauta e ata, calendário e projetos do grêmio.</p>
          <p><a class="link" href="<?= $basePath ?>/precos#gremios">Ver os planos do grêmio</a></p>
        </div>
        <div class="frame span-6 start-7">
          <div class="pr pr-board" role="img" aria-label="Quadro das diretorias do grêmio, com a presidente ligada às quatro diretorias">
            <svg class="ef-linhas" aria-hidden="true"></svg>
            <div class="ef-pres"><i>MA</i><span><b>Marina Alves</b><small>Presidente do grêmio</small></span></div>
            <div class="pr-dir"><h4>Esportes</h4><div class="pr-pessoa"><i>DA</i>Davi<em>diretor</em></div><div class="pr-pessoa"><i>EL</i>Elisa<em>coordenadora</em></div><div class="pr-pessoa"><i>FE</i>Felipe<em>membro</em></div></div>
            <div class="pr-dir"><h4>Cultura</h4><div class="pr-pessoa"><i>GA</i>Gabriela<em>diretora</em></div><div class="pr-pessoa"><i>HE</i>Heitor<em>membro</em></div></div>
            <div class="pr-dir"><h4>Comunicação</h4><div class="pr-pessoa"><i>IS</i>Isadora<em>diretora</em></div><div class="pr-pessoa"><i>JO</i>João<em>coordenador</em></div></div>
            <div class="pr-dir"><h4>Finanças</h4><div class="pr-pessoa"><i>KA</i>Karina<em>diretora</em></div><div class="pr-pessoa"><i>LU</i>Lucas<em>membro</em></div></div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- PLANOS e DÚVIDAS -->
  <section class="section" id="planos" aria-labelledby="planos-h">
    <div class="wrap">
      <div class="grid grid--top">
        <div class="span-5 stack-6">
          <h2 id="planos-h">Planos por turma. <span class="muted">O representante paga, a turma toda usa.</span></h2>
          <ul class="rows">
            <?php foreach ($planosLanding as $pl): ?>
            <li><span class="rows__name"><?= $e($pl['nome']) ?><small><?= (int) $pl['meses'] ?> meses · <?= (int) $pl['projetos'] ?> projetos<?= !empty($pl['inclui_dia_a_dia']) ? ' · agenda inclusa' : '' ?></small></span><span class="rows__value"><?= $reais($pl['preco']) ?></span></li>
            <?php endforeach; ?>
          </ul>
          <p><a class="link" href="<?= $basePath ?>/precos">Comparar todos os planos</a></p>
        </div>
        <div class="frame span-6 start-7">
          <div class="pr pr-mini" role="img" aria-label="Tela de pagamento do plano Anual por Pix, com QR code e código copia e cola">
            <div class="pr-mini__head"><b>Plano Anual · 231-3ANO-INFO-M</b><span class="pr-chip wait ef-pix-esp">Aguardando Pix</span><span class="pr-chip ok ef-pix-ok">Pago</span></div>
            <div class="pr-row"><span>Plano Anual, 12 meses</span><span class="pr-mono">R$ 279,90</span></div>
            <div class="pr-row"><span>Cupom ESCOLA10</span><span class="pr-mono">− R$ 27,99</span></div>
            <div class="pr-row"><b>Total</b><b class="pr-mono">R$ 251,91</b></div>
            <div class="pr-pix"><span class="ef-qr-wrap"><span class="pr-qr" aria-hidden="true"></span><span class="ef-check" aria-hidden="true"><svg viewBox="0 0 52 52"><circle cx="26" cy="26" r="23" /><path d="M15 27l7.5 7.5L38 18" /></svg></span></span><span class="pr-code">00020126580014br.gov.bcb.pix0136prumo-exemplo-5204000053039865406251.91</span></div>
            <span class="pr-k">A turma é liberada assim que o Pix for confirmado.</span>
          </div>
        </div>
      </div>
    </div>
  </section>
</main>

<footer class="footer closing footer--statement" id="contact" aria-labelledby="close-h">
  <div class="wrap">
    <div class="closing__cta close--word">
      <div class="section-head--split">
        <div><h2 id="close-h">Crie a turma e convide os colegas</h2><p class="t-lead">A turma é liberada assim que a 1ª parcela for paga.</p></div>
        <a class="btn btn--primary" href="<?= $criar ?>">Criar minha turma<svg class="m-arrow" viewBox="0 0 12 12" aria-hidden="true"><path class="m-arrow__stem" d="M1 6h8"/><path class="m-arrow__head" d="M4.5 2.5 8 6l-3.5 3.5"/></svg></a>
      </div>
      <p class="close__word" aria-hidden="true"><?= $e($appName) ?></p>
    </div>
    <div class="closing__links">
      <div class="grid">
        <div class="span-4 stack-4">
          <a class="nav__logo" href="#top"><img src="<?= $basePath ?>/img/logo-prumo.png" alt="" width="28" height="28"><?= $e($appName) ?></a>
          <p class="footer__lede">Projetos, avaliações, atas e agenda de turmas e grêmios escolares.</p>
        </div>
        <div class="span-2 footer__col"><h3>Produto</h3><ul><li><a href="#como-funciona">Como funciona</a></li><li><a href="#recursos">Recursos</a></li><li><a href="#gremios">Grêmios</a></li></ul></div>
        <div class="span-2 footer__col"><h3>Planos</h3><ul><li><a href="<?= $basePath ?>/precos">Preços</a></li><li><a href="<?= $basePath ?>/escolas">Para escolas</a></li></ul></div>
        <div class="span-2 footer__col"><h3>Conta</h3><ul><li><a href="<?= $basePath ?>/login">Entrar</a></li><li><a href="<?= $basePath ?>/login/cadastrar">Cadastro</a></li><li><a href="#" data-instalar-app>Baixar o app</a></li></ul></div>
        <div class="span-2 footer__col"><h3>Ajuda</h3><ul><li><a href="<?= $basePath ?>/tutorial">Tutorial</a></li><li><a href="<?= $basePath ?>/sobre">Sobre</a></li></ul></div>
      </div>
      <!-- créditos do autor -->
      <aside class="credito" aria-label="Créditos">
        <div class="credito__texto">
          <p class="credito__selo">Criado e pensado por</p>
          <p class="credito__nome">Jose Augusto Alves</p>
          <p class="credito__sub">Do desenho das telas ao código do <?= $e($appName) ?>.</p>
        </div>
        <div class="credito__links">
          <a class="btn btn--primary" href="https://josealvesdev.com" target="_blank" rel="noopener">
            <svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" d="M12 3a9 9 0 1 0 0 18 9 9 0 0 0 0-18Zm-8.5 9h17M12 3c2.5 2.6 3.7 5.6 3.7 9s-1.2 6.4-3.7 9c-2.5-2.6-3.7-5.6-3.7-9S9.5 5.6 12 3Z"/></svg>
            Portfólio · josealvesdev.com
          </a>
          <a class="btn btn--secondary" href="https://github.com/JoseAugAlv" target="_blank" rel="noopener">
            <svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path fill="currentColor" d="M12 .5a11.5 11.5 0 0 0-3.64 22.41c.58.1.79-.25.79-.56v-2c-3.2.7-3.88-1.37-3.88-1.37-.53-1.33-1.28-1.69-1.28-1.69-1.05-.71.08-.7.08-.7 1.16.08 1.77 1.19 1.77 1.19 1.03 1.77 2.71 1.26 3.37.96.1-.75.4-1.26.73-1.55-2.55-.29-5.24-1.28-5.24-5.69 0-1.26.45-2.29 1.19-3.1-.12-.29-.52-1.46.11-3.05 0 0 .97-.31 3.17 1.18a11 11 0 0 1 5.77 0c2.2-1.49 3.17-1.18 3.17-1.18.63 1.59.23 2.76.11 3.05.74.81 1.19 1.84 1.19 3.1 0 4.42-2.7 5.39-5.26 5.68.41.36.78 1.06.78 2.14v3.17c0 .31.21.67.8.56A11.5 11.5 0 0 0 12 .5Z"/></svg>
            GitHub
          </a>
        </div>
      </aside>
      <div class="footer__legal">
        <span>&copy; <?= date('Y') ?> <?= $e($appName) ?></span>
        <span class="legais"><a href="<?= $basePath ?>/lgpd">Privacidade</a><a href="<?= $basePath ?>/termos">Termos</a></span>
        <div class="socials"></div>
      </div>
    </div>
  </div>
</footer>

<script type="module" src="<?= $a ?>/system.js"></script>
<script type="module" src="<?= $a ?>/motion/motion.js"></script>
<script type="module" src="<?= $a ?>/layout/layout.js"></script>
<script type="module" src="<?= $basePath ?>/js/landing-camadas.js"></script>
<script src="<?= $basePath ?>/public/js/pwa.js"></script>
<script src="<?= $basePath ?>/js/landing-tema.js"></script>
<script src="<?= $basePath ?>/js/landing-rolagem.js" defer></script>
<script src="<?= $basePath ?>/js/landing-efeitos.js" defer></script>
</body>
</html>
