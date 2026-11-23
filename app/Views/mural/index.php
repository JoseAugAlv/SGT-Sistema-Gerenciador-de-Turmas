<?php
// app/Views/mural/index.php
// Mural de fotos e conquistas: publicar, aprovar (representante), curtir e ver a foto ampliada.
$cssPagina = ['projetos.css', 'mural.css'];
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';
$tid = (int) $turma['id'];
$uid = (int) $_SESSION['usuario']['id'];
$img = fn($a) => $basePath . '/public/' . $a;
?>

<section class="hero-section">
    <div>
        <div class="eyebrow"><span class="status-dot"></span> Mural</div>
        <h1>Mural da turma</h1>
        <p class="hero-subtitle">Fotos e conquistas da <strong><?= h($turma['nome']) ?></strong>.</p>
    </div>
    <div class="hero-actions">
        <a class="button button-ghost" href="<?= $basePath ?>/turmas/<?= $tid ?>"><i class="fas fa-arrow-left"></i> Voltar</a>
    </div>
</section>

<details class="panel mb-16 mu-nova"<?= !$posts && !$pendentes ? ' open' : '' ?>>
    <summary><strong><i class="fas fa-plus"></i> Publicar no mural</strong><?= $gestor ? '' : ' <small>· o representante aprova antes de aparecer</small>' ?></summary>
    <form method="POST" action="<?= $basePath ?>/turmas/<?= $tid ?>/mural" enctype="multipart/form-data" class="mu-form" data-parcial="#mu-conteudo" data-parcial-limpar>
        <?= ViewHelper::csrfField() ?>
        <div class="mu-tipo" role="radiogroup" aria-label="Tipo">
            <label><input type="radio" name="tipo" value="foto" checked><span><i class="fas fa-image"></i> Foto</span></label>
            <label><input type="radio" name="tipo" value="conquista"><span><i class="fas fa-trophy"></i> Conquista</span></label>
        </div>
        <label>Título *<input type="text" name="titulo" class="field" maxlength="120" required placeholder="Ex.: 1º lugar na Feira de Ciências"></label>
        <label>Legenda<textarea name="texto" class="field" rows="2" maxlength="600" placeholder="Conte um pouco"></textarea></label>
        <label class="mu-foto">Foto <small>(JPG, PNG ou WEBP, até 5 MB; opcional na conquista)</small><input type="file" name="foto" accept="image/jpeg,image/png,image/webp" class="field"></label>
        <div class="mu-icones" hidden>
            <span>Ícone da conquista</span>
            <?php foreach (Mural::ICONES as $k => [$ico, $nome]): ?>
                <label title="<?= $nome ?>"><input type="radio" name="icone" value="<?= $k ?>"<?= $k === 'trofeu' ? ' checked' : '' ?>><span><i class="fas <?= $ico ?>"></i></span></label>
            <?php endforeach; ?>
        </div>
        <p class="mb-0"><button type="submit" class="button button-primary" data-carregando="Enviando…"><i class="fas fa-paper-plane"></i> <?= $gestor ? 'Publicar' : 'Enviar para aprovação' ?></button></p>
    </form>
</details>

<div id="mu-conteudo" data-atualizar="30">
<?php if ($pendentes): ?>
    <section class="panel mb-16" id="pendentes">
        <h2 class="mu-h2"><i class="fas fa-hourglass-half"></i> <?= $gestor ? 'Esperando sua aprovação' : 'Suas publicações em análise' ?> (<?= count($pendentes) ?>)</h2>
        <ul class="mu-pendentes">
            <?php foreach ($pendentes as $p): ?>
                <li>
                    <?php if ($p['arquivo']): ?><img src="<?= h($img($p['arquivo'])) ?>" alt="" loading="lazy"><?php else: ?><span class="mu-ico-mini"><i class="fas <?= Mural::ICONES[$p['icone'] ?? 'trofeu'][0] ?>"></i></span><?php endif; ?>
                    <div><strong><?= h($p['titulo']) ?></strong><small>por <?= h($p['autor'] ?? '—') ?></small></div>
                    <?php if ($gestor): ?>
                        <form method="POST" action="<?= $basePath ?>/mural/<?= (int) $p['id'] ?>/aprovar" data-parcial="#mu-conteudo" class="d-inline"><?= ViewHelper::csrfField() ?><button class="button button-primary" type="submit">Aprovar</button></form>
                    <?php endif; ?>
                    <form method="POST" action="<?= $basePath ?>/mural/<?= (int) $p['id'] ?>/excluir" data-parcial="#mu-conteudo" class="d-inline"><?= ViewHelper::csrfField() ?><button class="button button-ghost" type="submit" onclick="return confirm('Excluir esta publicação?')"><?= $gestor ? 'Recusar' : 'Cancelar' ?></button></form>
                </li>
            <?php endforeach; ?>
        </ul>
    </section>
<?php endif; ?>

<?php if (!$posts): ?>
    <section class="panel empty-state"><i class="fas fa-images"></i><p>O mural ainda está vazio. Que tal a primeira foto da turma?</p></section>
<?php else: ?>
    <div class="mu-grade">
        <?php foreach ($posts as $p): $podeExcluir = $gestor || (int) $p['autor_id'] === $uid; ?>
            <article class="mu-post<?= $p['tipo'] === 'conquista' ? ' conquista' : '' ?><?= $p['fixado'] ? ' fixado' : '' ?>">
                <?php if ($p['arquivo']): ?>
                    <button type="button" class="mu-img" data-ampliar="<?= h($img($p['arquivo'])) ?>" data-titulo="<?= h($p['titulo']) ?>">
                        <img src="<?= h($img($p['arquivo'])) ?>" alt="<?= h($p['titulo']) ?>" loading="lazy">
                    </button>
                <?php elseif ($p['tipo'] === 'conquista'): ?>
                    <div class="mu-conquista-ico"><i class="fas <?= Mural::ICONES[$p['icone'] ?? 'trofeu'][0] ?>"></i></div>
                <?php endif; ?>
                <div class="mu-corpo">
                    <?php if ($p['fixado']): ?><span class="mu-fixo"><i class="fas fa-thumbtack"></i> Fixado</span><?php endif; ?>
                    <?php if ($p['tipo'] === 'conquista'): ?><span class="mu-selo"><i class="fas fa-trophy"></i> Conquista</span><?php endif; ?>
                    <h3><?= h($p['titulo']) ?></h3>
                    <?php if ($p['texto']): ?><p><?= nl2br(h($p['texto'])) ?></p><?php endif; ?>
                    <div class="mu-pe">
                        <button type="button" class="mu-curtir<?= $p['curti'] ? ' ativo' : '' ?>" data-curtir="<?= (int) $p['id'] ?>" aria-pressed="<?= $p['curti'] ? 'true' : 'false' ?>">
                            <i class="<?= $p['curti'] ? 'fas' : 'far' ?> fa-heart"></i> <span><?= (int) $p['curtidas'] ?></span>
                        </button>
                        <small><?= h($p['autor'] ?? '') ?> · <?= date('d/m', strtotime($p['created_at'])) ?></small>
                        <?php if ($gestor || $podeExcluir): ?>
                            <details class="mu-menu">
                                <summary aria-label="Opções"><i class="fas fa-ellipsis"></i></summary>
                                <div>
                                    <?php if ($gestor): ?><form method="POST" action="<?= $basePath ?>/mural/<?= (int) $p['id'] ?>/fixar" data-parcial="#mu-conteudo"><?= ViewHelper::csrfField() ?><button type="submit"><i class="fas fa-thumbtack"></i> <?= $p['fixado'] ? 'Desafixar' : 'Fixar no topo' ?></button></form><?php endif; ?>
                                    <?php if ($podeExcluir): ?><form method="POST" action="<?= $basePath ?>/mural/<?= (int) $p['id'] ?>/excluir" data-parcial="#mu-conteudo"><?= ViewHelper::csrfField() ?><button type="submit" onclick="return confirm('Excluir esta publicação?')"><i class="fas fa-trash"></i> Excluir</button></form><?php endif; ?>
                                </div>
                            </details>
                        <?php endif; ?>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

</div>

<dialog class="mu-ampliada" id="mu-ampliada" aria-label="Foto ampliada">
    <button type="button" class="mu-fechar" aria-label="Fechar"><i class="fas fa-xmark"></i></button>
    <img alt=""><p></p>
</dialog>

<script>
(function () {
    var base = <?= json_encode($basePath) ?>, token = document.querySelector('meta[name="csrf-token"]').content;
    // tipo: conquista mostra os ícones e deixa a foto opcional
    var form = document.querySelector('.mu-form');
    if (form) form.addEventListener('change', function () {
        var conq = form.querySelector('[name=tipo]:checked').value === 'conquista';
        form.querySelector('.mu-icones').hidden = !conq;
    });
    // curtir sem recarregar (evento delegado: continua valendo depois das atualizações automáticas)
    document.addEventListener('click', function (ev) {
        var b = ev.target.closest('[data-curtir]'); if (!b || b.disabled) return;
        (function () {
            b.disabled = true;
            fetch(base + '/mural/' + b.dataset.curtir + '/curtir', { method: 'POST', headers: { 'X-CSRF-Token': token }, credentials: 'same-origin' })
                .then(function (r) { return r.json(); }).then(function (d) {
                    if (!d.ok) return;
                    b.classList.toggle('ativo', d.curtiu); b.setAttribute('aria-pressed', d.curtiu);
                    b.querySelector('i').className = (d.curtiu ? 'fas' : 'far') + ' fa-heart';
                    b.querySelector('span').textContent = d.total;
                    if (d.curtiu) { b.classList.remove('pulso'); void b.offsetWidth; b.classList.add('pulso'); }
                }).finally(function () { b.disabled = false; });
        })();
    });
    // foto ampliada (delegado)
    var dlg = document.getElementById('mu-ampliada');
    document.addEventListener('click', function (ev) {
        var b = ev.target.closest('[data-ampliar]'); if (!b) return;
        dlg.querySelector('img').src = b.dataset.ampliar; dlg.querySelector('img').alt = b.dataset.titulo;
        dlg.querySelector('p').textContent = b.dataset.titulo; dlg.showModal();
    });
    dlg.querySelector('.mu-fechar').addEventListener('click', function () { dlg.close(); });
    dlg.addEventListener('click', function (e) { if (e.target === dlg) dlg.close(); });
})();
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
