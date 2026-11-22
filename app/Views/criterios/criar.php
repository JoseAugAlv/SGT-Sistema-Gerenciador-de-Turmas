<?php
$cssPagina = ['projetos.css', 'criterios.css'];

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';
?>

<section class="hero-section">
    <div>
        <div class="eyebrow">
            <span class="status-dot"></span>
            Novo critério
        </div>
        <h1><?= h($projeto['nome']) ?></h1>
        <p class="hero-subtitle">
            Defina como este critério será avaliado.
        </p>
    </div>
    <div class="hero-actions">
        <a class="button button-ghost" href="<?= $basePath ?>/projetos/<?= (int) $projeto['id'] ?>/criterios">
            <i class="fas fa-arrow-left"></i> Voltar
        </a>
    </div>
</section>

<?php require __DIR__ . '/../projetos/_nav.php'; ?>

<form method="POST" action="<?= $basePath ?>/projetos/<?= (int) $projeto['id'] ?>/criterios/salvar" class="panel">
    <?= ViewHelper::csrfField() ?>

    <label class="field-label">Nome *</label>
    <input type="text" name="nome" class="field" maxlength="200" required autofocus>

    <label class="field-label">Descrição</label>
    <textarea name="descricao" class="field" rows="3" maxlength="2000"></textarea>

    <div class="field-row">
        <div>
            <label class="field-label">Peso *</label>
            <input type="number" name="peso" class="field" min="0.01" max="99.99" step="0.01" value="<?php $faltaPeso = 10 - $somaPesos; echo number_format($faltaPeso > 0 && $faltaPeso < 1 ? $faltaPeso : 1, 2, '.', ''); ?>" required>
            <small class="field-help">Os outros critérios somam <?= number_format($somaPesos, 2, ',', '.') ?>. <span class="crit-peso-dica" data-outros="<?= $somaPesos ?>"></span></small>
        </div>
        <div>
            <label class="field-label">Prazo de avaliação</label>
            <input type="datetime-local" name="prazo_avaliacao" class="field" min="<?= date('Y-m-d\TH:i') ?>">
            <small class="field-help">Depois do prazo, ninguém mais lança nota. Deixe vazio para não ter prazo.</small>
        </div>
    </div>

    <label class="field-label">Tipo de avaliação *</label>
    <select name="tipo_avaliacao" class="field" required>
        <?php foreach ($tipos as $val => $label): ?>
            <option value="<?= h($val) ?>"><?= h($label) ?></option>
        <?php endforeach; ?>
    </select>

    <details class="mt-12">
        <summary><i class="fas fa-circle-info"></i> Como funciona cada tipo de avaliação</summary>

        <ul class="tipos-lista mt-12">
            <li class="tipo-item t-diretor">
                <div><strong>Diretor</strong> — os <em>diretores ativos do grupo</em> avaliam o aluno. Se houver mais de um diretor, a nota final é a média entre eles. Se o próprio aluno avaliado for o único diretor do grupo, a avaliação dele passa automaticamente para o representante da turma.</div>
            </li>
            <li class="tipo-item t-representante">
                <div><strong>Representante</strong> — o(s) <em>representante(s) ativo(s) da turma</em> avaliam. Se houver 2 representantes, ambos avaliam e a nota final é a média entre eles.</div>
            </li>
            <li class="tipo-item t-pares">
                <div><strong>Pares</strong> — cada aluno do grupo avalia cada um dos colegas (menos ele mesmo). <strong>Justificativa é obrigatória</strong> e é de uso interno. A nota final é a média das notas dadas pelos pares.</div>
            </li>
            <li class="tipo-item t-autoavaliacao">
                <div><strong>Autoavaliação</strong> — o próprio aluno avalia a si mesmo.</div>
            </li>
            <li class="tipo-item t-coletiva">
                <div><strong>Coletiva</strong> — uma nota única para <em>todo o grupo</em>. Todos os membros recebem essa mesma nota no critério.</div>
            </li>
            <li class="tipo-item t-misto">
                <div><strong>Misto</strong> — combina diretor e representante: <code>nota = média(diretores) × 0.5 + média(representantes) × 0.5</code>.</div>
            </li>
        </ul>
    </details>

    <label class="identidade-opcao crit-obrigatorio mt-16" data-tipos="pares autoavaliacao">
        <input type="checkbox" name="obrigatorio" value="1">
        <span>
            <strong>Preenchimento obrigatório</strong>
            <small>Os alunos precisam preencher até o prazo. Eles veem o critério marcado como obrigatório, e você vê aqui quem ainda não preencheu. Vale para "Pares" e "Autoavaliação" e exige prazo.</small>
        </span>
    </label>

    <label class="field-label mt-16">Aplicável a *</label>
    <select name="aplicavel_a" class="field" required>
        <?php foreach ($aplicaveis as $val => $label): ?>
            <option value="<?= h($val) ?>"><?= h($label) ?></option>
        <?php endforeach; ?>
    </select>

    <?php if (!empty($etapas)): ?>
        <label class="field-label">Etapa vinculada (opcional)</label>
        <select name="etapa_id" class="field">
            <option value="">Nenhuma</option>
            <?php foreach ($etapas as $e): ?>
                <option value="<?= (int) $e['id'] ?>"><?= h($e['nome']) ?></option>
            <?php endforeach; ?>
        </select>
    <?php endif; ?>

    <p class="mt-20">
        <button type="submit" class="button button-primary">
            <i class="fas fa-check"></i> Criar critério
        </button>
        <a class="button button-ghost" href="<?= $basePath ?>/projetos/<?= (int) $projeto['id'] ?>/criterios">
            Cancelar
        </a>
    </p>
</form>


<script>
(function () {
    var campo = document.querySelector('input[name="peso"]');
    var dica  = document.querySelector('.crit-peso-dica');
    if (!campo || !dica) return;
    var outros = parseFloat(dica.dataset.outros) || 0;
    function atualizar() {
        var total = outros + (parseFloat(campo.value.replace(',', '.')) || 0);
        var falta = Math.round((10 - total) * 100) / 100;
        var fmt = function (n) { return n.toFixed(2).replace('.', ','); };
        dica.className = 'crit-peso-dica ' + (falta === 0 ? 'ok' : (falta < 0 ? 'alerta' : ''));
        dica.textContent = falta === 0 ? 'Com este, a soma fecha em 10.'
            : falta > 0 ? 'Com este, ainda faltam ' + fmt(falta) + ' para 10.'
            : 'Com este, a soma passa de 10 em ' + fmt(-falta) + '.';
    }
    campo.addEventListener('input', atualizar);
    atualizar();
})();
</script>

<script>
(function () {
    var tipo  = document.querySelector('[name="tipo_avaliacao"]:not([type=hidden])');
    var bloco = document.querySelector('.crit-obrigatorio');
    var prazo = document.querySelector('[name="prazo_avaliacao"]');
    if (!tipo || !bloco) return;
    var caixa = bloco.querySelector('input');
    function atualizar() {
        var vale = bloco.dataset.tipos.split(' ').indexOf(tipo.value) !== -1;
        bloco.hidden = !vale;
        if (!vale) caixa.checked = false;
        if (prazo) prazo.required = vale && caixa.checked;
    }
    tipo.addEventListener('change', atualizar);
    caixa.addEventListener('change', atualizar);
    atualizar();
})();
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>