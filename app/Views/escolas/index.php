<?php
// app/Views/escolas/index.php
// Página pública "Para escolas": benefícios do pacote, simulador de preço por volume e pedido de proposta.
$e = fn($t) => htmlspecialchars((string) $t, ENT_QUOTES, 'UTF-8');
$app = App::getName();
$planosJs = array_map(fn($p) => ['id' => (int) $p['id'], 'nome' => $p['nome'], 'meses' => (int) $p['meses'], 'preco' => (float) $p['preco'], 'dia' => (bool) $p['inclui_dia_a_dia']], $planos);
$anual = 0; foreach ($planos as $i => $p) if (($p['codigo'] ?? '') === 'anual') $anual = $i;
?><!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= $e($app) ?> para escolas</title>
<meta name="description" content="Pacote do <?= $e($app) ?> para escolas: todas as turmas e o grêmio organizados, com desconto por volume e um código por turma.">
<link rel="icon" href="<?= $basePath ?>/public/img/logo-prumo.png">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link rel="stylesheet" href="<?= ViewHelper::asset('css/escolas.css') ?>">
</head>
<body>
<header class="es-nav"><div class="es-wrap">
    <a class="es-marca" href="<?= $basePath ?>/"><img src="<?= $basePath ?>/public/img/logo-prumo.png" alt="" width="28" height="28"> <?= $e($app) ?></a>
    <nav><a href="<?= $basePath ?>/precos">Planos</a><a href="#simulador">Simular</a><a href="<?= $basePath ?>/login">Entrar</a><a class="es-btn" href="#proposta">Pedir proposta</a></nav>
</div></header>

<section class="es-hero"><div class="es-wrap">
    <p class="es-selo"><i class="fas fa-school"></i> Para escolas e coordenações</p>
    <h1>Todas as turmas da escola organizadas, com preço de pacote</h1>
    <p class="es-lead">A escola contrata uma vez e recebe um código para cada turma. Os representantes ativam sem pagar nada, e a coordenação acompanha tudo.</p>
    <div class="es-acoes"><a class="es-btn grande" href="#simulador">Simular o pacote</a><a class="es-link" href="#proposta">Falar com a gente <i class="fas fa-arrow-right"></i></a></div>
</div></section>

<section class="es-wrap es-beneficios">
    <article><i class="fas fa-ticket"></i><h3>Um código por turma</h3><p>Cada turma ativa o plano com o próprio código, de graça para o representante.</p></article>
    <article><i class="fas fa-percent"></i><h3>Desconto por volume</h3><p>Quanto mais turmas, menor o preço de cada uma<?php $f = array_values($faixas); ?>: até <?= rtrim(rtrim(number_format(max($f), 1, ',', ''), '0'), ',') ?>% de desconto.</p></article>
    <article><i class="fas fa-landmark"></i><h3>Grêmio incluído</h3><p>O grêmio da escola também entra, com diretorias, reuniões em ata e página pública.</p></article>
    <article><i class="fas fa-file-invoice"></i><h3>Pagamento único</h3><p>Uma cobrança para a escola, por Pix, com nota e prestação de contas.</p></article>
</section>

<section class="es-wrap es-sim" id="simulador">
    <div class="es-sim-texto">
        <h2>Simule o pacote</h2>
        <p>Escolha quantas turmas e por quanto tempo. O preço já sai com o desconto da faixa.</p>
        <ul class="es-faixas">
            <?php foreach ($faixas as $min => $pct): ?><li data-min="<?= $min ?>"><b><?= $min ?>+ turmas</b><span><?= rtrim(rtrim(number_format($pct, 1, ',', ''), '0'), ',') ?>% off</span></li><?php endforeach; ?>
        </ul>
    </div>
    <div class="es-sim-caixa">
        <label for="sim-turmas">Quantas turmas? <output id="sim-turmas-v">10</output></label>
        <input type="range" id="sim-turmas" min="1" max="60" value="10">
        <span class="es-rotulo">Duração</span>
        <div class="es-planos" role="radiogroup">
            <?php foreach ($planos as $i => $p): ?>
                <label><input type="radio" name="sim-plano" value="<?= $i ?>"<?= $i === $anual ? ' checked' : '' ?>><span><?= $e($p['nome']) ?><small><?= (int) $p['meses'] ?> meses</small></span></label>
            <?php endforeach; ?>
        </div>
        <div class="es-res">
            <div><span>Por turma</span><b id="sim-por">—</b><s id="sim-de"></s></div>
            <div><span>Total do pacote</span><b id="sim-total">—</b><small id="sim-economia"></small></div>
        </div>
        <a class="es-btn grande cheio" href="#proposta" id="sim-pedir">Pedir proposta com estes números</a>
    </div>
</section>

<section class="es-wrap es-form-sec" id="proposta">
    <h2>Pedir proposta</h2>
    <?php if ($enviado): ?>
        <div class="es-ok"><i class="fas fa-circle-check"></i><div><strong>Pedido recebido!</strong><p>Vamos responder no email informado em até 2 dias úteis.</p></div></div>
    <?php else: ?>
        <?php if ($erro): ?><p class="es-erro"><?= $e($erro) ?></p><?php endif; ?>
        <form method="POST" action="<?= $basePath ?>/escolas/contato" class="es-form">
            <input type="hidden" name="_csrf_token" value="<?= $e(CsrfMiddleware::generateToken()) ?>">
            <input type="text" name="site" class="es-mel" tabindex="-1" autocomplete="off" aria-hidden="true">
            <label>Seu nome *<input type="text" name="nome" required maxlength="120" value="<?= $e($antigo['nome'] ?? '') ?>"></label>
            <label>Escola *<input type="text" name="escola" required maxlength="150" value="<?= $e($antigo['escola'] ?? '') ?>"></label>
            <label>Email *<input type="email" name="email" required maxlength="150" value="<?= $e($antigo['email'] ?? '') ?>"></label>
            <label>Telefone / WhatsApp<input type="tel" name="telefone" maxlength="30" value="<?= $e($antigo['telefone'] ?? '') ?>"></label>
            <label>Cidade<input type="text" name="cidade" maxlength="100" value="<?= $e($antigo['cidade'] ?? '') ?>"></label>
            <label>Número de turmas<input type="number" name="turmas" id="f-turmas" min="1" max="500" value="<?= (int) ($antigo['turmas'] ?? 10) ?>"></label>
            <label>Plano de interesse<select name="plano_id" id="f-plano"><?php foreach ($planos as $i => $p): ?><option value="<?= (int) $p['id'] ?>"<?= $i === $anual ? ' selected' : '' ?>><?= $e($p['nome']) ?></option><?php endforeach; ?></select></label>
            <label class="larga">Mensagem<textarea name="mensagem" rows="3" maxlength="2000" placeholder="Conte um pouco da escola"><?= $e($antigo['mensagem'] ?? '') ?></textarea></label>
            <p class="larga"><button type="submit" class="es-btn grande cheio">Enviar pedido</button></p>
        </form>
    <?php endif; ?>
</section>

<footer class="es-rodape es-wrap"><?= $e($app) ?> · organização de turmas e grêmios escolares · <a href="<?= $basePath ?>/termos">Termos</a> · <a href="<?= $basePath ?>/lgpd">Privacidade</a></footer>

<script>
(function () {
    var planos = <?= json_encode($planosJs, JSON_UNESCAPED_UNICODE) ?>, faixas = <?= json_encode($faixas) ?>;
    var r = document.getElementById('sim-turmas'), brl = function (v) { return v.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' }); };
    function desc(n) { var d = 0; Object.keys(faixas).forEach(function (m) { if (n >= +m) d = faixas[m]; }); return d; }
    function calc() {
        var n = +r.value, i = +document.querySelector('[name=sim-plano]:checked').value, p = planos[i], d = desc(n);
        var por = p.preco * (1 - d / 100), total = por * n;
        document.getElementById('sim-turmas-v').textContent = n;
        document.getElementById('sim-por').textContent = brl(por);
        document.getElementById('sim-de').textContent = d ? brl(p.preco) : '';
        document.getElementById('sim-total').textContent = brl(total);
        document.getElementById('sim-economia').textContent = d ? 'Economia de ' + brl(p.preco * n - total) + ' (' + String(d).replace('.', ',') + '%)' : 'A partir de 5 turmas entra o desconto';
        document.querySelectorAll('.es-faixas li').forEach(function (li) { li.classList.toggle('ativa', +li.dataset.min <= n && desc(n) === faixas[li.dataset.min]); });
        document.getElementById('f-turmas') && (document.getElementById('f-turmas').value = n);
        var fp = document.getElementById('f-plano'); if (fp) fp.value = p.id;
    }
    r.addEventListener('input', calc);
    document.querySelectorAll('[name=sim-plano]').forEach(function (x) { x.addEventListener('change', calc); });
    calc();
})();
</script>
</body>
</html>
