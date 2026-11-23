<?php
// app/Views/projetos/criar.php
// Novo projeto: o representante configura tudo antes de criar (dados, grupos e diretores, esquema de notas, grupos iniciais).
$cssPagina = 'projetos.css';

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';

$a = $antigo ?? [];
$regras = [
    'alunos_pedem_grupo'   => (int) ($a['alunos_pedem_grupo'] ?? 0),
    'alunos_pedem_diretor' => (int) ($a['alunos_pedem_diretor'] ?? 0),
    'diretores_por_grupo'  => (int) ($a['diretores_por_grupo'] ?? 1),
];
$cfg = null;
if ($a) {
    require_once __DIR__ . '/../../Helpers/EscalaNota.php';
    [$cfg] = EscalaNota::lerDoPost($a);
}
?>

<section class="hero-section">
    <div>
        <div class="eyebrow"><span class="status-dot"></span> Novo projeto</div>
        <h1>Novo projeto</h1>
        <p class="hero-subtitle">Turma: <strong><?= h($turma['nome']) ?></strong>. Configure como o projeto vai funcionar antes de criar.</p>
    </div>
    <div class="hero-actions">
        <a class="button button-ghost" href="<?= $basePath ?>/turmas/<?= (int) $turma['id'] ?>"><i class="fas fa-arrow-left"></i> Voltar</a>
    </div>
</section>

<form method="POST" action="<?= $basePath ?>/projetos/salvar" class="projeto-wizard">
    <?= ViewHelper::csrfField() ?>
    <input type="hidden" name="turma_id" value="<?= (int) $turma['id'] ?>">

    <!-- 1. DADOS -->
    <section class="panel pw-passo">
        <h2 class="pw-titulo"><span class="pw-num">1</span> Sobre o projeto</h2>
        <label class="field-label" for="pj-nome">Nome *</label>
        <input id="pj-nome" type="text" name="nome" class="field" maxlength="150" required autofocus value="<?= h($a['nome'] ?? '') ?>" placeholder="Ex.: Feira de Ciências">

        <label class="field-label" for="pj-desc">Descrição</label>
        <textarea id="pj-desc" name="descricao" class="field" rows="3" maxlength="2000"><?= h($a['descricao'] ?? '') ?></textarea>

        <div class="pw-linha">
            <div>
                <label class="field-label" for="pj-prazo">Prazo</label>
                <input id="pj-prazo" type="date" name="prazo" class="field" value="<?= h($a['prazo'] ?? '') ?>">
            </div>
            <div>
                <label class="field-label" for="pj-modo">Modo de avaliação *</label>
                <select id="pj-modo" name="modo_avaliacao" class="field" required>
                    <?php foreach ($modos as $val => $label): ?>
                        <option value="<?= h($val) ?>"<?= ($a['modo_avaliacao'] ?? '') === $val ? ' selected' : '' ?>><?= h($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </section>

    <!-- 2. GRUPOS E DIRETORES -->
    <section class="panel pw-passo">
        <h2 class="pw-titulo"><span class="pw-num">2</span> Grupos e diretores</h2>
        <?php require __DIR__ . '/_config_grupos.php'; ?>
    </section>

    <!-- 3. NOTAS -->
    <section class="panel pw-passo">
        <h2 class="pw-titulo"><span class="pw-num">3</span> Notas</h2>
        <?php require __DIR__ . '/_esquema_nota.php'; ?>
        <div class="mt-16"><?php $avaliacaoReps = $a['avaliacao_reps'] ?? 'compartilhada'; require __DIR__ . '/_avaliacao_reps.php'; ?></div>
        <p class="field-help mb-0">Os critérios e os pesos você cadastra depois de criar, na aba Critérios.</p>
    </section>

    <!-- 4. GRUPOS INICIAIS (OPCIONAL) -->
    <section class="panel pw-passo">
        <h2 class="pw-titulo"><span class="pw-num">4</span> Criar grupos agora <small>(opcional)</small></h2>
        <p class="field-help">Pode deixar em branco e criar depois. Um nome por linha.</p>
        <div class="pw-grupos-rapido">
            <label for="pj-qtd">Gerar</label>
            <input id="pj-qtd" type="number" min="1" max="30" value="4" class="field">
            <button type="button" class="button button-secondary" id="pj-gerar">grupos numerados</button>
        </div>
        <textarea id="pj-grupos" name="grupos" class="field" rows="5" maxlength="3000" placeholder="Grupo 1&#10;Grupo 2&#10;Grupo 3"><?= h($a['grupos'] ?? '') ?></textarea>
    </section>

    <div class="pw-acoes">
        <button type="submit" class="button button-primary"><i class="fas fa-check"></i> Criar projeto</button>
        <a class="button button-ghost" href="<?= $basePath ?>/turmas/<?= (int) $turma['id'] ?>">Cancelar</a>
    </div>
</form>

<style>
.projeto-wizard { display: grid; gap: 16px; max-width: 900px; }
.pw-passo { margin: 0; }
.pw-titulo { display: flex; align-items: center; gap: 10px; margin: 0 0 14px; font-size: 18px; }
.pw-titulo small { font-weight: 400; color: var(--muted); font-size: 13px; }
.pw-num { display: inline-grid; place-items: center; width: 28px; height: 28px; border-radius: 50%; background: var(--primary); color: #fff; font-size: 14px; font-weight: 700; flex: none; }
.pw-linha { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
.pw-grupos-rapido { display: flex; align-items: center; gap: 8px; margin-bottom: 8px; flex-wrap: wrap; }
.pw-grupos-rapido .field { width: 80px; margin: 0; }
.pw-acoes { display: flex; gap: 8px; flex-wrap: wrap; }
@media (max-width: 560px) { .pw-linha { grid-template-columns: 1fr; } }
</style>
<script>
document.getElementById('pj-gerar').addEventListener('click', function () {
    var n = Math.min(30, Math.max(1, +document.getElementById('pj-qtd').value || 1)), linhas = [];
    for (var i = 1; i <= n; i++) linhas.push('Grupo ' + i);
    document.getElementById('pj-grupos').value = linhas.join('\n');
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
