<?php
// app/Views/gremio/pagina_config.php
// Configuração da página pública do grêmio (presidente/vice/master).
$cssPagina = ['projetos.css', 'caixa.css'];
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';
$tid = (int) $turma['id'];
$origem = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . $basePath . '/g/';
$link = $origem . $p['slug'];
?>

<section class="hero-section">
    <div>
        <div class="eyebrow"><span class="status-dot"></span> Página pública</div>
        <h1>Página do grêmio</h1>
        <p class="hero-subtitle">Uma página aberta, sem login, para divulgar o <strong><?= h($turma['nome']) ?></strong> na escola e nas redes.</p>
    </div>
    <div class="hero-actions">
        <a class="button button-ghost" href="<?= $basePath ?>/turmas/<?= $tid ?>"><i class="fas fa-arrow-left"></i> Voltar</a>
        <?php if ($p['ativa']): ?><a class="button button-primary" href="<?= h($link) ?>" target="_blank" rel="noopener"><i class="fas fa-arrow-up-right-from-square"></i> Ver a página</a><?php endif; ?>
    </div>
</section>

<?php if ($p['ativa']): ?>
    <section class="panel mb-16 gp-link">
        <i class="fas fa-link"></i>
        <code id="gp-url"><?= h($link) ?></code>
        <button type="button" class="button button-secondary" onclick="navigator.clipboard.writeText(document.getElementById('gp-url').textContent).then(() => { this.textContent = 'Copiado!'; })">Copiar link</button>
    </section>
<?php endif; ?>

<form method="POST" action="<?= $basePath ?>/turmas/<?= $tid ?>/pagina" class="panel cx2-form">
    <?= ViewHelper::csrfField() ?>
    <label class="cx2-larga gp-liga"><input type="checkbox" name="ativa" value="1"<?= $p['ativa'] ? ' checked' : '' ?>> <strong>Página no ar</strong> <small>(desligada, ninguém consegue abrir)</small></label>
    <label class="cx2-larga">Endereço *
        <span class="gp-slug"><span><?= h($origem) ?></span><input type="text" name="slug" class="field" maxlength="60" required value="<?= h($p['slug']) ?>" pattern="[A-Za-z0-9\-]{3,60}"></span>
    </label>
    <label class="cx2-larga">Apresentação<textarea name="apresentacao" class="field" rows="4" maxlength="1500" placeholder="Quem somos, o que fazemos e como participar"><?= h($p['apresentacao'] ?? '') ?></textarea></label>
    <label>Instagram <small>(sem o @)</small><input type="text" name="instagram" class="field" maxlength="31" value="<?= h($p['instagram'] ?? '') ?>" placeholder="gremio.escola"></label>
    <div class="cx2-larga">
        <strong>O que aparece</strong>
        <div class="gp-opcoes">
            <label><input type="checkbox" name="mostrar_diretorias" value="1"<?= $p['mostrar_diretorias'] ? ' checked' : '' ?>> Diretorias e membros <small>(nomes abreviados, ex.: "Davi S.")</small></label>
            <label><input type="checkbox" name="mostrar_eventos" value="1"<?= $p['mostrar_eventos'] ? ' checked' : '' ?>> Próximos eventos e reuniões gerais</label>
            <label><input type="checkbox" name="mostrar_conquistas" value="1"<?= $p['mostrar_conquistas'] ? ' checked' : '' ?>> Conquistas do mural <small>(sem fotos de pessoas)</small></label>
            <label><input type="checkbox" name="mostrar_contas" value="1"<?= $p['mostrar_contas'] ? ' checked' : '' ?>> Transparência: saldo, entradas e saídas do caixa</label>
        </div>
    </div>
    <p class="cx2-larga mb-0"><button type="submit" class="button button-primary">Salvar</button></p>
</form>

<style>
.gp-link { display: flex; gap: 12px; align-items: center; flex-wrap: wrap; }
.gp-link i { color: var(--primary); } .gp-link code { flex: 1; min-width: 200px; word-break: break-all; }
.gp-liga { display: flex !important; gap: 8px; align-items: center; }
.gp-slug { display: flex; align-items: center; gap: 0; border: 1px solid var(--border); border-radius: 10px; overflow: hidden; }
.gp-slug span { padding: 0 10px; color: var(--muted); white-space: nowrap; font-size: 13px; background: var(--surface-2, var(--surface)); align-self: stretch; display: flex; align-items: center; }
.gp-slug .field { border: 0; border-radius: 0; }
.gp-opcoes { display: grid; gap: 8px; margin-top: 8px; }
.gp-opcoes label { display: flex; gap: 8px; align-items: center; font-weight: 400; }
@media (max-width: 640px) { .gp-slug span { display: none; } }
</style>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
