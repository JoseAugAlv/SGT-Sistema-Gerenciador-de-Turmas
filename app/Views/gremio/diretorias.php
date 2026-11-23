<?php
// app/Views/gremio/diretorias.php
// Diretorias do grêmio: o presidente cria; presidente e vice distribuem diretor, coordenadores e membros.
$cssPagina = 'projetos.css';
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';
require_once __DIR__ . '/../../Models/Diretoria.php';
$tid = (int) $turma['id'];
$jaExistem = array_map(fn($d) => mb_strtolower($d['nome']), $diretorias);
$sugestoes = array_values(array_filter(Diretoria::SUGESTOES, fn($s) => !in_array(mb_strtolower($s), $jaExistem, true)));
$semDiretoria = array_filter($membros, function ($m) use ($diretorias) {
    foreach ($diretorias as $d) foreach ($d['membros'] as $x) if ((int) $x['usuario_id'] === (int) $m['id']) return false;
    return true;
});
$icoCargo = ['diretor' => 'fa-crown', 'coordenador' => 'fa-star', 'membro' => 'fa-user'];
?>

<section class="hero-section">
    <div>
        <div class="eyebrow"><span class="status-dot"></span> Grêmio</div>
        <h1>Diretorias</h1>
        <p class="hero-subtitle"><strong><?= h($turma['nome']) ?></strong> · <?= count($diretorias) ?> diretoria(s) · <?= count($semDiretoria) ?> pessoa(s) ainda sem diretoria</p>
    </div>
    <div class="hero-actions">
        <a class="button button-ghost" href="<?= $basePath ?>/turmas/<?= $tid ?>"><i class="fas fa-arrow-left"></i> Grêmio</a>
        <a class="button button-ghost" href="<?= $basePath ?>/turmas/<?= $tid ?>/reunioes"><i class="fas fa-people-group"></i> Reuniões</a>
    </div>
</section>

<?php if ($souPresidente): ?>
    <section class="panel mb-16">
        <h2 class="an-h2"><i class="fas fa-plus"></i> Criar diretoria</h2>
        <form method="POST" action="<?= $basePath ?>/turmas/<?= $tid ?>/diretorias" class="dir-criar">
            <?= ViewHelper::csrfField() ?>
            <input type="text" name="nome" class="field" maxlength="300" required placeholder="Ex.: Esportes (ou várias, separadas por vírgula)" id="dir-nome">
            <button type="submit" class="button button-primary">Criar</button>
        </form>
        <?php if ($sugestoes): ?>
            <p class="dir-sugestoes">Sugestões:
                <?php foreach ($sugestoes as $s): ?><button type="button" class="tarefa-acao" data-sugestao="<?= h($s) ?>">+ <?= h($s) ?></button><?php endforeach; ?>
            </p>
        <?php endif; ?>
    </section>
<?php endif; ?>

<?php if (!$diretorias): ?>
    <div class="lista-vazia"><i class="fas fa-sitemap"></i><span><?= $souPresidente ? 'Crie as diretorias do grêmio acima.' : 'O presidente ainda não criou as diretorias.' ?></span></div>
<?php else: ?>
    <div class="dir-grade">
        <?php foreach ($diretorias as $d): $did = (int) $d['id']; ?>
            <article class="panel dir-card" id="d<?= $did ?>">
                <div class="dir-topo">
                    <h2><?= h($d['nome']) ?></h2>
                    <span class="txt-mudo"><?= count($d['membros']) ?> pessoa(s)</span>
                </div>
                <?php if ($d['descricao']): ?><p class="field-help"><?= h($d['descricao']) ?></p><?php endif; ?>
                <ul class="dir-membros">
                    <?php foreach ($d['membros'] as $m): ?>
                        <li class="c-<?= h($m['cargo']) ?>">
                            <i class="fas <?= $icoCargo[$m['cargo']] ?>"></i>
                            <span><strong><?= h($m['nome']) ?></strong><small><?= Diretoria::CARGOS[$m['cargo']] ?></small></span>
                            <?php if ($podeDistribuir): ?>
                                <form method="POST" action="<?= $basePath ?>/diretorias/<?= $did ?>/membros/remover" class="d-inline">
                                    <?= ViewHelper::csrfField() ?><input type="hidden" name="usuario_id" value="<?= (int) $m['usuario_id'] ?>">
                                    <button type="submit" class="horario-x" aria-label="Tirar da diretoria"><i class="fas fa-xmark"></i></button>
                                </form>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                    <?php if (!$d['membros']): ?><li class="txt-mudo">Ninguém ainda.</li><?php endif; ?>
                </ul>
                <?php if ($podeDistribuir): ?>
                    <form method="POST" action="<?= $basePath ?>/diretorias/<?= $did ?>/membros" class="dir-add">
                        <?= ViewHelper::csrfField() ?>
                        <select name="usuario_id" class="field" required aria-label="Pessoa">
                            <option value="">Adicionar pessoa…</option>
                            <?php foreach ($membros as $mb): ?><option value="<?= (int) $mb['id'] ?>"><?= h($mb['nome']) ?></option><?php endforeach; ?>
                        </select>
                        <select name="cargo" class="field" aria-label="Cargo"><?php foreach (Diretoria::CARGOS as $k => $rot): ?><option value="<?= $k ?>"<?= $k === 'membro' ? ' selected' : '' ?>><?= $rot ?></option><?php endforeach; ?></select>
                        <button type="submit" class="button button-secondary">OK</button>
                    </form>
                <?php endif; ?>
                <?php if ($souPresidente): ?>
                    <details class="mt-12">
                        <summary class="text-link">Editar ou excluir</summary>
                        <form method="POST" action="<?= $basePath ?>/diretorias/<?= $did ?>/atualizar" class="dir-editar">
                            <?= ViewHelper::csrfField() ?>
                            <input type="text" name="nome" class="field" value="<?= h($d['nome']) ?>" maxlength="100" required aria-label="Nome">
                            <textarea name="descricao" class="field" rows="2" maxlength="500" placeholder="O que essa diretoria faz"><?= h($d['descricao'] ?? '') ?></textarea>
                            <button type="submit" class="button button-secondary">Salvar</button>
                        </form>
                        <form method="POST" action="<?= $basePath ?>/diretorias/<?= $did ?>/excluir">
                            <?= ViewHelper::csrfField() ?>
                            <button type="submit" class="button button-ghost txt-alerta" onclick="return confirm('Excluir a diretoria <?= h($d['nome']) ?>?')">Excluir diretoria</button>
                        </form>
                    </details>
                <?php endif; ?>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<style>
.an-h2 { margin: 0 0 10px; font-size: 16px; } .an-h2 i { color: var(--primary); }
.dir-criar { display: flex; gap: 8px; flex-wrap: wrap; } .dir-criar .field { flex: 1 1 260px; margin: 0; }
.dir-sugestoes { margin: 10px 0 0; font-size: 13px; color: var(--muted); display: flex; flex-wrap: wrap; gap: 6px; align-items: center; }
.dir-grade { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 14px; }
.dir-card { margin: 0; display: flex; flex-direction: column; gap: 8px; }
.dir-topo { display: flex; justify-content: space-between; align-items: baseline; gap: 8px; } .dir-topo h2 { margin: 0; font-size: 17px; }
.dir-membros { list-style: none; margin: 0; padding: 0; display: grid; gap: 6px; }
.dir-membros li { display: grid; grid-template-columns: 20px 1fr auto; align-items: center; gap: 8px; }
.dir-membros li span small { display: block; color: var(--muted); font-size: 12px; }
.dir-membros .c-diretor i { color: var(--orange); } .dir-membros .c-coordenador i { color: var(--primary); } .dir-membros .c-membro i { color: var(--muted); }
.dir-add { display: grid; grid-template-columns: 1fr auto auto; gap: 6px; margin-top: auto; } .dir-add .field { margin: 0; min-width: 0; }
.dir-editar { display: grid; gap: 6px; margin: 8px 0; } .dir-editar .field { margin: 0; }
.horario-x { border: 0; background: none; color: var(--muted); cursor: pointer; padding: 4px; } .horario-x:hover { color: var(--red); }
</style>
<script>
document.querySelectorAll('[data-sugestao]').forEach(function (b) {
    b.addEventListener('click', function () {
        var i = document.getElementById('dir-nome');
        i.value = i.value.trim() ? i.value.replace(/,\s*$/, '') + ', ' + b.dataset.sugestao : b.dataset.sugestao;
        b.remove(); i.focus();
    });
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
