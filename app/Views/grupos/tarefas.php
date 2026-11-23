<?php
// app/Views/grupos/tarefas.php
// Kanban do grupo: arrastar cartões entre colunas (no celular, botões ‹ ›), criar/editar tarefa e participação.
$cssPagina = ['projetos.css', 'kanban.css'];
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';
$gid = (int) $grupo['id'];
$hoje = date('Y-m-d');
$iniciais = function ($n) { $p = preg_split('/\s+/', trim(preg_replace('/\s*\(diretor\)$/', '', (string) $n))); return mb_strtoupper(mb_substr($p[0] ?? '', 0, 1) . mb_substr(count($p) > 1 ? end($p) : '', 0, 1)); };
$colunas = array_keys(Tarefa::COLUNAS);
$formTarefa = function (?array $t = null) use ($pessoas, $atas) { ?>
    <label>Título *<input type="text" name="titulo" class="field" maxlength="150" required value="<?= h($t['titulo'] ?? '') ?>" placeholder="Ex.: Montar a maquete"></label>
    <label>Responsável
        <select name="responsavel_id" class="field"><option value="">Ninguém ainda</option>
            <?php foreach ($pessoas as $id => $nome): ?><option value="<?= $id ?>"<?= (int) ($t['responsavel_id'] ?? 0) === $id ? ' selected' : '' ?>><?= h($nome) ?></option><?php endforeach; ?>
        </select>
    </label>
    <label>Prazo<input type="date" name="prazo" class="field" value="<?= h($t['prazo'] ?? '') ?>"></label>
    <?php if ($atas): ?>
        <label>Combinada na ata
            <select name="ata_id" class="field"><option value="">Nenhuma</option>
                <?php foreach ($atas as $a): ?><option value="<?= (int) $a['id'] ?>"<?= (int) ($t['ata_id'] ?? 0) === (int) $a['id'] ? ' selected' : '' ?>><?= h($a['titulo']) ?></option><?php endforeach; ?>
            </select>
        </label>
    <?php endif; ?>
    <label class="kb-larga">Detalhes<textarea name="descricao" class="field" rows="2" maxlength="3000"><?= h($t['descricao'] ?? '') ?></textarea></label>
<?php };
?>

<section class="hero-section">
    <div>
        <div class="eyebrow"><span class="status-dot"></span> Tarefas do grupo</div>
        <h1><?= h($grupo['nome']) ?></h1>
        <p class="hero-subtitle">Projeto <strong><?= h($projeto['nome']) ?></strong> · arraste os cartões entre as colunas.</p>
    </div>
    <div class="hero-actions">
        <a class="button button-ghost" href="<?= $basePath ?>/grupos/<?= $gid ?>"><i class="fas fa-arrow-left"></i> Grupo</a>
    </div>
</section>

<details class="panel mb-16">
    <summary><strong><i class="fas fa-plus"></i> Nova tarefa</strong></summary>
    <form method="POST" action="<?= $basePath ?>/grupos/<?= $gid ?>/tarefas" class="kb-form" data-parcial="#kb-quadro,#kb-part-area" data-parcial-limpar><?= ViewHelper::csrfField() ?><?php $formTarefa(); ?>
        <p class="kb-larga mb-0"><button type="submit" class="button button-primary">Criar tarefa</button></p>
    </form>
</details>

<div class="kb-quadro" id="kb-quadro" data-atualizar="20">
    <?php foreach (Tarefa::COLUNAS as $col => [$nome, $ico]): $ci = array_search($col, $colunas, true); ?>
        <section class="kb-coluna kb-<?= $col ?>" data-coluna="<?= $col ?>">
            <header><i class="fas <?= $ico ?>"></i> <?= $nome ?> <b class="kb-cont"><?= count($quadro[$col]) ?></b></header>
            <div class="kb-lista" data-lista="<?= $col ?>">
                <?php foreach ($quadro[$col] as $t):
                    $atrasada = $t['prazo'] && $t['prazo'] < $hoje && $col !== 'feito'; ?>
                    <article class="kb-cartao<?= $atrasada ? ' atrasada' : '' ?>" draggable="true" data-id="<?= (int) $t['id'] ?>">
                        <h3><?= h($t['titulo']) ?></h3>
                        <?php if ($t['descricao']): ?><p><?= nl2br(h(mb_strimwidth($t['descricao'], 0, 160, '…'))) ?></p><?php endif; ?>
                        <div class="kb-meta">
                            <?php if ($t['prazo']): ?><span class="kb-prazo"><i class="far fa-calendar"></i> <?= date('d/m', strtotime($t['prazo'])) ?></span><?php endif; ?>
                            <?php if ($t['ata_titulo']): ?><a class="kb-ata" href="<?= $basePath ?>/atas/<?= (int) $t['ata_id'] ?>" title="<?= h($t['ata_titulo']) ?>"><i class="fas fa-file-lines"></i> Ata</a><?php endif; ?>
                            <?php if ($t['responsavel']): ?><span class="kb-resp" title="<?= h($t['responsavel']) ?>"><?= h($iniciais($t['responsavel'])) ?></span><?php endif; ?>
                        </div>
                        <div class="kb-mover">
                            <button type="button" data-ir="<?= $colunas[$ci - 1] ?? '' ?>" aria-label="Voltar coluna"<?= $ci === 0 ? ' disabled' : '' ?>><i class="fas fa-chevron-left"></i></button>
                            <details class="kb-editar">
                                <summary>Editar</summary>
                                <form method="POST" action="<?= $basePath ?>/tarefas/<?= (int) $t['id'] ?>/atualizar" class="kb-form" data-parcial="#kb-quadro,#kb-part-area"><?= ViewHelper::csrfField() ?><?php $formTarefa($t); ?>
                                    <p class="kb-larga mb-0"><button type="submit" class="button button-primary">Salvar</button></p>
                                </form>
                                <form method="POST" action="<?= $basePath ?>/tarefas/<?= (int) $t['id'] ?>/excluir" data-parcial="#kb-quadro,#kb-part-area"><?= ViewHelper::csrfField() ?><button type="submit" class="button button-ghost" onclick="return confirm('Excluir esta tarefa?')"><i class="fas fa-trash"></i> Excluir</button></form>
                            </details>
                            <button type="button" data-ir="<?= $colunas[$ci + 1] ?? '' ?>" aria-label="Avançar coluna"<?= $ci === count($colunas) - 1 ? ' disabled' : '' ?>><i class="fas fa-chevron-right"></i></button>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endforeach; ?>
</div>

<div id="kb-part-area">
<?php if ($participacao): ?>
    <section class="panel mt-16">
        <h2 class="kb-h2"><i class="fas fa-chart-simple"></i> Participação no quadro</h2>
        <p class="field-help">Tarefas concluídas por integrante: ajuda o diretor na hora de avaliar.</p>
        <ul class="kb-part">
            <?php $max = max(1, max(array_map(fn($p) => (int) $p['feitas'] + (int) $p['abertas'], $participacao)));
            foreach ($participacao as $p): ?>
                <li><span><?= h($p['nome']) ?></span>
                    <div class="kb-part-barra"><i class="f" style="width: <?= round($p['feitas'] / $max * 100) ?>%"></i><i class="a" style="width: <?= round($p['abertas'] / $max * 100) ?>%"></i></div>
                    <small><?= (int) $p['feitas'] ?> feita<?= (int) $p['feitas'] === 1 ? '' : 's' ?> · <?= (int) $p['abertas'] ?> em aberto</small></li>
            <?php endforeach; ?>
        </ul>
    </section>
<?php endif; ?>
</div>

<script>
(function () {
    var base = <?= json_encode($basePath) ?>, token = document.querySelector('meta[name="csrf-token"]').content;
    var quadro = document.getElementById('kb-quadro'), arrastando = null;
    function salvar(cartao) {
        var lista = cartao.parentElement, col = lista.dataset.lista;
        var ordem = Array.prototype.map.call(lista.querySelectorAll('.kb-cartao'), function (c) { return c.dataset.id; }).join(',');
        var corpo = new URLSearchParams({ coluna: col, ordem: ordem });
        fetch(base + '/tarefas/' + cartao.dataset.id + '/mover', { method: 'POST', body: corpo, headers: { 'X-CSRF-Token': token }, credentials: 'same-origin' });
        atualizar();
    }
    function atualizar() {
        var cols = Array.prototype.map.call(quadro.querySelectorAll('.kb-coluna'), function (c) { return c.dataset.coluna; });
        quadro.querySelectorAll('.kb-coluna').forEach(function (c) {
            c.querySelector('.kb-cont').textContent = c.querySelectorAll('.kb-cartao').length;
            c.querySelectorAll('.kb-cartao').forEach(function (k) {
                var i = cols.indexOf(c.dataset.coluna), b = k.querySelectorAll('[data-ir]');
                b[0].dataset.ir = cols[i - 1] || ''; b[0].disabled = i === 0;
                b[1].dataset.ir = cols[i + 1] || ''; b[1].disabled = i === cols.length - 1;
                k.classList.toggle('feito', c.dataset.coluna === 'feito');
            });
        });
    }
    // arrastar (computador)
    quadro.addEventListener('dragstart', function (e) { var c = e.target.closest('.kb-cartao'); if (!c) return; arrastando = c; c.classList.add('arrastando'); e.dataTransfer.effectAllowed = 'move'; });
    quadro.addEventListener('dragend', function () { if (arrastando) { arrastando.classList.remove('arrastando'); salvar(arrastando); arrastando = null; } quadro.querySelectorAll('.kb-lista').forEach(function (l) { l.classList.remove('alvo'); }); });
    quadro.addEventListener('dragover', function (e) {
        var lista = e.target.closest('.kb-lista'); if (!lista || !arrastando) return;
        e.preventDefault(); lista.classList.add('alvo');
        var depois = Array.prototype.filter.call(lista.querySelectorAll('.kb-cartao:not(.arrastando)'), function (c) { var r = c.getBoundingClientRect(); return e.clientY < r.top + r.height / 2; })[0];
        if (depois) lista.insertBefore(arrastando, depois); else lista.appendChild(arrastando);
    });
    quadro.addEventListener('dragleave', function (e) { var l = e.target.closest('.kb-lista'); if (l && !l.contains(e.relatedTarget)) l.classList.remove('alvo'); });
    // botões ‹ › (celular e teclado)
    quadro.addEventListener('click', function (e) {
        var b = e.target.closest('[data-ir]'); if (!b || !b.dataset.ir) return;
        var c = b.closest('.kb-cartao'), destino = quadro.querySelector('[data-lista="' + b.dataset.ir + '"]');
        destino.appendChild(c); c.classList.add('chegou'); setTimeout(function () { c.classList.remove('chegou'); }, 500); salvar(c);
    });
    atualizar();
    // depois de criar/editar/excluir ou da atualização automática: recalcula contadores e setas
    document.addEventListener('parcial:atualizado', function (e) { if (e.detail === quadro) atualizar(); });
})();
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
