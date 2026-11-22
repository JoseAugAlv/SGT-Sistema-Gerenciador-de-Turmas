<?php
$cssPagina = 'turmas.css';

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';

$cores = [
    '#1d4ed8' => 'Azul',
    '#7857ff' => 'Roxo',
    '#0e7a52' => 'Verde',
    '#c8372d' => 'Vermelho',
    '#e08600' => 'Laranja',
    '#0891b2' => 'Ciano',
    '#be185d' => 'Rosa',
    '#111827' => 'Preto',
];
$corAtual = strtolower($turma['cor_primaria'] ?: '#1d4ed8');
$logoUrl  = !empty($turma['logo']) ? ViewHelper::asset($turma['logo']) : '';
$sigla    = ViewHelper::siglaTurma($turma['nome']);
?>

<section class="hero-section">
    <div>
        <h1>Identidade da turma</h1>
        <p class="hero-subtitle">Escolha a cor e o logo que aparecem no topo da turma <?= h($turma['nome']) ?>.</p>
    </div>
    <div class="hero-actions">
        <a class="button button-ghost" href="<?= $basePath ?>/turmas/<?= (int) $turma['id'] ?>">
            <i class="fas fa-arrow-left"></i> Voltar
        </a>
    </div>
</section>

<!-- Prévia ao vivo -->
<div class="turma-faixa previa" id="previa" style="<?= h(ViewHelper::estiloTurma($turma)) ?>">
    <div class="turma-logo" id="previa-logo">
        <?php if ($logoUrl): ?>
            <img src="<?= h($logoUrl) ?>" alt="Logo da turma">
        <?php else: ?>
            <i class="fas fa-shield-halved" aria-hidden="true"></i>
        <?php endif; ?>
    </div>
    <div class="turma-sigla num"><?= h($sigla) ?></div>
    <div class="turma-meta">
        <strong><?= h($turma['nome']) ?></strong>
        <span>Prévia do topo da turma</span>
    </div>
</div>

<form method="POST" enctype="multipart/form-data"
      action="<?= $basePath ?>/turmas/<?= (int) $turma['id'] ?>/identidade/salvar" class="panel identidade-form">
    <?= ViewHelper::csrfField() ?>

    <fieldset class="identidade-bloco">
        <legend>Cor</legend>

        <label class="identidade-opcao">
            <input type="radio" name="cor_personalizada" value="0" <?= empty($turma['cor_personalizada']) ? 'checked' : '' ?>>
            <span><strong>Usar a cor do tema</strong><small>A faixa acompanha o tema que cada pessoa escolheu.</small></span>
        </label>
        <label class="identidade-opcao">
            <input type="radio" name="cor_personalizada" value="1" <?= !empty($turma['cor_personalizada']) ? 'checked' : '' ?>>
            <span><strong>Usar a cor da turma</strong><small>Todos veem a mesma cor no topo da turma.</small></span>
        </label>

        <div class="paleta" role="radiogroup" aria-label="Cor da turma">
            <?php foreach ($cores as $hex => $nome): ?>
                <button type="button" class="paleta-cor <?= $hex === $corAtual ? 'ativa' : '' ?>"
                        data-cor="<?= $hex ?>" style="--c:<?= $hex ?>"
                        role="radio" aria-checked="<?= $hex === $corAtual ? 'true' : 'false' ?>"
                        aria-label="<?= h($nome) ?>" title="<?= h($nome) ?>"></button>
            <?php endforeach; ?>
            <label class="paleta-outra" title="Outra cor">
                <input type="color" name="cor_primaria" id="cor-input" value="<?= h($corAtual) ?>" aria-label="Outra cor">
                <span>Outra</span>
            </label>
        </div>
        <p class="identidade-dica">O texto do topo muda sozinho entre branco e escuro para continuar legível.</p>
    </fieldset>

    <fieldset class="identidade-bloco">
        <legend>Logo</legend>
        <input type="file" name="logo" id="logo-input" accept="image/png,image/jpeg,image/webp" class="field">
        <p class="identidade-dica">PNG, JPG ou WEBP de até 1 MB. Imagens quadradas ficam melhores.</p>
        <?php if ($logoUrl): ?>
            <label class="identidade-check">
                <input type="checkbox" name="remover_logo" value="1"> Remover o logo atual
            </label>
        <?php endif; ?>
    </fieldset>

    <p class="mt-20">
        <button type="submit" class="button button-primary"><i class="fas fa-check"></i> Salvar identidade</button>
        <a class="button button-ghost" href="<?= $basePath ?>/turmas/<?= (int) $turma['id'] ?>">Cancelar</a>
    </p>
</form>

<script>
(function () {
    var previa = document.getElementById('previa');
    var input  = document.getElementById('cor-input');
    var radios = document.querySelectorAll('input[name="cor_personalizada"]');

    function corTexto(hex) {
        var c = [1, 3, 5].map(function (i) {
            var v = parseInt(hex.substr(i, 2), 16) / 255;
            return v <= 0.03928 ? v / 12.92 : Math.pow((v + 0.055) / 1.055, 2.4);
        });
        var l = 0.2126 * c[0] + 0.7152 * c[1] + 0.0722 * c[2];
        return (1.05 / (l + 0.05)) >= ((l + 0.05) / 0.0562) ? '#ffffff' : '#0b1220';
    }

    function atualizar() {
        var personalizada = document.querySelector('input[name="cor_personalizada"]:checked').value === '1';
        if (!personalizada) { previa.removeAttribute('style'); return; }
        var t = corTexto(input.value);
        previa.style.setProperty('--shell-bg', input.value);
        previa.style.setProperty('--shell-text', t);
        previa.style.setProperty('--shell-muted', t === '#ffffff' ? 'rgba(255,255,255,.78)' : 'rgba(11,18,32,.72)');
        previa.style.setProperty('--accent-on-dark', t);
    }

    document.querySelectorAll('.paleta-cor').forEach(function (b) {
        b.addEventListener('click', function () {
            input.value = b.dataset.cor;
            document.querySelectorAll('.paleta-cor').forEach(function (o) {
                o.classList.toggle('ativa', o === b);
                o.setAttribute('aria-checked', o === b ? 'true' : 'false');
            });
            radios[1].checked = true;
            atualizar();
        });
    });
    input.addEventListener('input', function () {
        document.querySelectorAll('.paleta-cor').forEach(function (o) { o.classList.remove('ativa'); o.setAttribute('aria-checked', 'false'); });
        radios[1].checked = true;
        atualizar();
    });
    radios.forEach(function (r) { r.addEventListener('change', atualizar); });

    document.getElementById('logo-input').addEventListener('change', function () {
        var f = this.files[0];
        if (!f) return;
        var leitor = new FileReader();
        leitor.onload = function (e) {
            document.getElementById('previa-logo').innerHTML = '<img src="' + e.target.result + '" alt="Prévia do logo">';
        };
        leitor.readAsDataURL(f);
    });
})();
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
