<?php
// app/Views/estudos/index.php
// Banco de materiais de estudo: disciplinas, próximas provas/entregas, busca, compartilhar e lista.
$cssPagina = ['projetos.css', 'estudos.css'];
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';
$tid = (int) $turma['id'];
$base = $basePath . '/turmas/' . $tid . '/estudos';
$uid = (int) $_SESSION['usuario']['id'];
$dataCurta = fn($d) => date('d/m', strtotime($d));
$tiposEv = ['prova' => 'Prova', 'entrega' => 'Entrega', 'tarefa' => 'Tarefa'];
$totalGeral = array_sum(array_column($disciplinas, 'total'));
?>

<section class="hero-section">
    <div>
        <div class="eyebrow"><span class="status-dot"></span> Estudos</div>
        <h1>Materiais de estudo</h1>
        <p class="hero-subtitle">Arquivos, links e resumos da <strong><?= h($turma['nome']) ?></strong>, por disciplina e por prova.</p>
    </div>
    <div class="hero-actions">
        <a class="button button-ghost" href="<?= $basePath ?>/turmas/<?= $tid ?>"><i class="fas fa-arrow-left"></i> Voltar</a>
    </div>
</section>

<div class="es-grade">
    <aside class="es-lado">
        <section class="panel">
            <h2 class="es-h2"><i class="fas fa-book"></i> Disciplinas</h2>
            <nav class="es-disc">
                <a href="<?= $base ?>" class="<?= $disciplina === '' && !$eventoAtual ? 'ativa' : '' ?>">Todas <b><?= $totalGeral ?></b></a>
                <?php foreach ($disciplinas as $d): ?>
                    <a href="<?= $base ?>?disciplina=<?= rawurlencode($d['nome']) ?>" class="<?= $disciplina === $d['nome'] ? 'ativa' : '' ?>"><?= h($d['nome']) ?> <b><?= $d['total'] ?></b></a>
                <?php endforeach; ?>
            </nav>
            <?php if (!$disciplinas): ?><p class="es-vazio">As disciplinas do horário de aulas aparecem aqui.</p><?php endif; ?>
        </section>
        <?php if ($eventos): ?>
            <section class="panel">
                <h2 class="es-h2"><i class="fas fa-calendar-check"></i> Próximas provas e entregas</h2>
                <ul class="es-eventos">
                    <?php foreach ($eventos as $e): ?>
                        <li><a href="<?= $base ?>?evento=<?= (int) $e['id'] ?>" class="<?= $eventoAtual && (int) $eventoAtual['id'] === (int) $e['id'] ? 'ativa' : '' ?>">
                            <span class="es-ev-data"><?= $dataCurta($e['data']) ?></span>
                            <span><b><?= h($e['titulo']) ?></b><small><?= $tiposEv[$e['tipo']] ?? '' ?> · <?= (int) $e['materiais'] ?> material<?= (int) $e['materiais'] === 1 ? '' : 'is' ?></small></span>
                        </a></li>
                    <?php endforeach; ?>
                </ul>
            </section>
        <?php endif; ?>
    </aside>

    <div class="es-principal">
        <form method="GET" action="<?= $base ?>" class="es-busca">
            <?php if ($disciplina !== ''): ?><input type="hidden" name="disciplina" value="<?= h($disciplina) ?>"><?php endif; ?>
            <i class="fas fa-magnifying-glass"></i>
            <input type="search" name="q" class="field" value="<?= h($busca) ?>" placeholder="Buscar material<?= $disciplina !== '' ? ' em ' . h($disciplina) : '' ?>">
        </form>

        <details class="panel es-nova"<?= !$materiais && $busca === '' ? ' open' : '' ?>>
            <summary><strong><i class="fas fa-plus"></i> Compartilhar material</strong></summary>
            <form method="POST" action="<?= $base ?>" enctype="multipart/form-data" class="es-form">
                <?= ViewHelper::csrfField() ?>
                <div class="es-tipo es-larga">
                    <?php foreach (Estudo::TIPOS as $k => [$nome, $ico]): ?>
                        <label><input type="radio" name="tipo" value="<?= $k ?>"<?= $k === 'arquivo' ? ' checked' : '' ?>><span><i class="fas <?= $ico ?>"></i> <?= $nome ?></span></label>
                    <?php endforeach; ?>
                </div>
                <label>Disciplina *
                    <input type="text" name="disciplina" class="field" list="es-lista-disc" maxlength="80" required value="<?= h($disciplina) ?>" placeholder="Ex.: Física">
                    <datalist id="es-lista-disc"><?php foreach ($disciplinas as $d): ?><option value="<?= h($d['nome']) ?>"><?php endforeach; ?></datalist>
                </label>
                <label>Título *<input type="text" name="titulo" class="field" maxlength="150" required placeholder="Ex.: Lista de exercícios cap. 3"></label>
                <label class="es-larga" data-campo="arquivo">Arquivo <small>(PDF, imagem ou ZIP, até 15 MB)</small><input type="file" name="arquivo" class="field" accept=".pdf,.zip,image/jpeg,image/png,image/webp"></label>
                <label class="es-larga" data-campo="link" hidden>Link<input type="url" name="url" class="field" maxlength="500" placeholder="https://"></label>
                <label class="es-larga" data-campo="resumo" hidden>Resumo<textarea name="texto" class="field" rows="6" maxlength="20000" placeholder="Escreva os pontos principais"></textarea></label>
                <?php if ($eventos): ?>
                    <label class="es-larga">Ajuda em qual prova ou entrega? <small>(opcional: a turma é avisada)</small>
                        <select name="evento_id" class="field">
                            <option value="">Nenhuma</option>
                            <?php foreach ($eventos as $e): ?><option value="<?= (int) $e['id'] ?>"<?= $eventoAtual && (int) $eventoAtual['id'] === (int) $e['id'] ? ' selected' : '' ?>><?= $dataCurta($e['data']) ?> · <?= h($e['titulo']) ?></option><?php endforeach; ?>
                        </select>
                    </label>
                <?php endif; ?>
                <p class="es-larga mb-0"><button type="submit" class="button button-primary" data-carregando="Enviando…"><i class="fas fa-share"></i> Compartilhar</button></p>
            </form>
        </details>

        <?php if ($eventoAtual): ?>
            <p class="es-filtro"><i class="fas fa-filter"></i> Materiais para <strong><?= h($eventoAtual['titulo']) ?></strong> (<?= date('d/m/Y', strtotime($eventoAtual['data'])) ?>) · <a href="<?= $base ?>">ver todos</a></p>
        <?php endif; ?>

        <?php if (!$materiais): ?>
            <section class="panel empty-state"><i class="fas fa-book-open"></i><p><?= $busca !== '' ? 'Nada encontrado para essa busca.' : 'Nenhum material aqui ainda. Compartilhe o primeiro!' ?></p></section>
        <?php else: ?>
            <ul class="es-lista">
                <?php foreach ($materiais as $m): [$tNome, $tIco] = Estudo::TIPOS[$m['tipo']]; $podeExcluir = $gestor || (int) $m['autor_id'] === $uid; ?>
                    <li class="panel es-item" id="m<?= (int) $m['id'] ?>">
                        <span class="es-ico es-<?= $m['tipo'] ?>"><i class="fas <?= $tIco ?>"></i></span>
                        <div class="es-info">
                            <div class="es-topo"><span class="es-disc-chip"><?= h($m['disciplina']) ?></span><?php if ($m['evento_titulo']): ?><span class="es-ev-chip"><i class="fas fa-calendar-check"></i> <?= h($m['evento_titulo']) ?> · <?= $dataCurta($m['evento_data']) ?></span><?php endif; ?></div>
                            <h3><?= h($m['titulo']) ?></h3>
                            <?php if ($m['tipo'] === 'resumo'): ?>
                                <details class="es-resumo"><summary>Ler resumo</summary><div><?= nl2br(h($m['texto'])) ?></div></details>
                            <?php endif; ?>
                            <small><?= h($m['autor'] ?? '—') ?> · <?= date('d/m/Y', strtotime($m['created_at'])) ?><?= $m['nome_original'] ? ' · ' . h($m['nome_original']) : '' ?></small>
                        </div>
                        <div class="es-acoes">
                            <?php if ($m['tipo'] === 'arquivo'): ?><a class="button button-secondary" href="<?= $basePath ?>/estudos/<?= (int) $m['id'] ?>/baixar" target="_blank" rel="noopener"><i class="fas fa-download"></i> Abrir</a><?php endif; ?>
                            <?php if ($m['tipo'] === 'link'): ?><a class="button button-secondary" href="<?= h($m['url']) ?>" target="_blank" rel="noopener noreferrer"><i class="fas fa-arrow-up-right-from-square"></i> Abrir link</a><?php endif; ?>
                            <?php if ($podeExcluir): ?>
                                <form method="POST" action="<?= $basePath ?>/estudos/<?= (int) $m['id'] ?>/excluir" class="d-inline"><?= ViewHelper::csrfField() ?><button type="submit" class="button button-ghost" aria-label="Remover" onclick="return confirm('Remover este material?')"><i class="fas fa-trash"></i></button></form>
                            <?php endif; ?>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</div>

<script>
(function () {
    var f = document.querySelector('.es-form'); if (!f) return;
    function trocar() {
        var t = f.querySelector('[name=tipo]:checked').value;
        f.querySelectorAll('[data-campo]').forEach(function (c) { c.hidden = c.dataset.campo !== t; });
    }
    f.addEventListener('change', function (e) { if (e.target.name === 'tipo') trocar(); });
    trocar();
})();
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
