<?php
// app/Views/reunioes/qr.php
// Tela de projeção do QR de presença: o código muda a cada 30 s (busca em /reunioes/{id}/qr/status),
// mostra quantos já marcaram e os últimos nomes. "Tela cheia" deixa só o QR para o projetor.
$cssPagina = 'projetos.css';
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';
$id = (int) $r['id'];
?>

<section class="qr-palco" id="qr-palco">
    <div class="qr-esq">
        <p class="qr-eyebrow"><i class="fas fa-qrcode"></i> Presença por QR Code</p>
        <h1><?= h($r['titulo']) ?></h1>
        <p class="qr-instrucao">Aponte a câmera do celular para o código e entre com a sua conta do <?= h(App::getName()) ?>. A presença é marcada na hora.</p>
        <div class="qr-numeros">
            <div><b id="qr-total"><?= (int) $total ?></b><span>de <?= (int) $convocados ?> marcaram</span></div>
            <div><b id="qr-restam">--:--</b><span>para fechar</span></div>
        </div>
        <ul class="qr-ultimos" id="qr-ultimos">
            <?php foreach ($presentes as $p): ?><li><i class="fas fa-check"></i> <?= h($p['nome']) ?> <small><?= h($p['hora']) ?></small></li><?php endforeach; ?>
        </ul>
        <div class="qr-acoes">
            <button type="button" class="button button-secondary" id="qr-cheia"><i class="fas fa-expand"></i> Tela cheia</button>
            <form method="POST" action="<?= $basePath ?>/reunioes/<?= $id ?>/qr/fechar" class="d-inline">
                <?= ViewHelper::csrfField() ?><button type="submit" class="button button-ghost"><i class="fas fa-lock"></i> Fechar QR</button>
            </form>
        </div>
    </div>
    <div class="qr-dir">
        <div class="qr-codigo" id="qr-codigo" aria-label="QR Code de presença"><?= $svg ?></div>
        <div class="qr-ciclo"><i id="qr-ciclo-barra" style="--s: <?= (int) $proximo ?>s"></i></div>
        <small class="qr-dica">O código muda a cada 30 segundos: foto do QR não vale.</small>
    </div>
</section>

<style>
.qr-palco { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1.1fr); gap: 32px; align-items: center; background: var(--surface); border: 1px solid var(--border); border-radius: 20px; padding: 32px; min-height: calc(100vh - 180px); }
.qr-palco:fullscreen { border-radius: 0; min-height: 100vh; padding: 5vh 6vw; background: var(--surface); }
.qr-eyebrow { color: var(--primary); font-weight: 700; margin: 0 0 8px; }
.qr-esq h1 { margin: 0 0 10px; font-size: clamp(26px, 3.2vw, 44px); line-height: 1.1; }
.qr-instrucao { color: var(--muted); font-size: 17px; max-width: 42ch; }
.qr-numeros { display: flex; gap: 28px; margin: 20px 0; }
.qr-numeros b { display: block; font-size: clamp(34px, 4.5vw, 60px); line-height: 1; font-variant-numeric: tabular-nums; color: var(--text); }
.qr-numeros span { color: var(--muted); }
.qr-ultimos { list-style: none; padding: 0; margin: 0 0 18px; display: grid; gap: 6px; max-height: 240px; overflow: hidden; }
.qr-ultimos li { display: flex; gap: 8px; align-items: center; animation: qr-entra .5s ease; }
.qr-ultimos li i { color: var(--green); } .qr-ultimos small { color: var(--muted); margin-left: auto; }
.qr-acoes { display: flex; gap: 8px; flex-wrap: wrap; }
.qr-palco:fullscreen .qr-acoes { display: none; }
.qr-dir { display: grid; justify-items: center; gap: 12px; }
.qr-codigo { width: min(100%, 62vh, 560px); aspect-ratio: 1; background: #fff; border-radius: 18px; padding: 18px; box-shadow: 0 30px 60px -30px rgba(0,0,0,.4); }
.qr-codigo svg { width: 100%; height: 100%; display: block; }
.qr-ciclo { width: min(100%, 62vh, 560px); height: 5px; border-radius: 3px; background: var(--border); overflow: hidden; }
.qr-ciclo i { display: block; height: 100%; background: var(--primary); transform-origin: 0 50%; animation: qr-ciclo var(--s) linear forwards; }
.qr-dica { color: var(--muted); }
@keyframes qr-ciclo { from { transform: scaleX(1); } to { transform: scaleX(0); } }
@keyframes qr-entra { from { opacity: 0; transform: translateY(-6px); } }
.qr-total-pulso { animation: qr-pulso .6s ease; }
@keyframes qr-pulso { 50% { transform: scale(1.18); color: var(--green); } }
@media (max-width: 860px) { .qr-palco { grid-template-columns: 1fr; padding: 20px; min-height: 0; } .qr-dir { order: -1; } }
</style>

<script>
(function () {
    var url = <?= json_encode($basePath . '/reunioes/' . $id . '/qr/status') ?>;
    var restam = <?= (int) $restam ?>, total = <?= (int) $total ?>;
    var elRestam = document.getElementById('qr-restam'), elTotal = document.getElementById('qr-total');
    var elCodigo = document.getElementById('qr-codigo'), elUlt = document.getElementById('qr-ultimos'), barra = document.getElementById('qr-ciclo-barra');
    function relogio() {
        var m = Math.floor(restam / 60), s = restam % 60;
        elRestam.textContent = m + ':' + (s < 10 ? '0' : '') + s;
    }
    function esc(t) { var d = document.createElement('div'); d.textContent = t; return d.innerHTML; }
    function atualizar() {
        fetch(url, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' }).then(function (r) { return r.json(); }).then(function (d) {
            if (!d.aberto) { location.href = <?= json_encode($basePath . '/reunioes/' . $id . '#presenca') ?>; return; }
            if (elCodigo.dataset.url !== d.url) {
                elCodigo.innerHTML = d.svg; elCodigo.dataset.url = d.url;
                barra.style.animation = 'none'; void barra.offsetWidth; barra.style.setProperty('--s', d.proximo + 's'); barra.style.animation = '';
            }
            restam = d.restam;
            if (d.total !== total) {
                total = d.total; elTotal.textContent = total;
                elTotal.classList.remove('qr-total-pulso'); void elTotal.offsetWidth; elTotal.classList.add('qr-total-pulso');
                elUlt.innerHTML = d.presentes.map(function (p) { return '<li><i class="fas fa-check"></i> ' + esc(p.nome) + ' <small>' + esc(p.hora) + '</small></li>'; }).join('');
            }
        }).catch(function () {});
    }
    relogio();
    setInterval(function () { if (restam > 0) restam--; relogio(); }, 1000);
    setInterval(atualizar, 4000);
    // troca o código bem na virada dos 30 s
    setTimeout(function () { atualizar(); setInterval(atualizar, 30000); }, (<?= (int) $proximo ?> + 0.3) * 1000);
    document.getElementById('qr-cheia').addEventListener('click', function () {
        var p = document.getElementById('qr-palco');
        if (p.requestFullscreen) p.requestFullscreen();
    });
})();
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
