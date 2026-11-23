<?php
$cssPagina = 'planos.css';

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';

$divisor = 40;
$sel = fn($campo, $valor) => (string) ($antigo[$campo] ?? '') === (string) $valor ? ' selected' : '';
$ehGremio = ($tipo ?? 'turma') === 'gremio';
if ($ehGremio) $divisor = 20;

ob_start(); ?>
<?php if ($ehGremio): ?>
    <input type="hidden" name="tipo" value="gremio">
    <fieldset class="nova-turma panel">
        <legend class="bloco-titulo-legend">1. Dados do grêmio</legend>
        <p class="field-help">Você entra como <strong>presidente</strong>: cria as diretorias, escolhe o vice e cuida do plano.</p>
        <label class="field-label" for="ng-nome">Nome do grêmio *</label>
        <input id="ng-nome" type="text" name="nome_gremio" class="field" maxlength="150" required value="<?= h($antigo['nome_gremio'] ?? '') ?>" placeholder="Ex.: Grêmio Estudantil ETEC Morato">
    </fieldset>
    <p class="bloco-titulo-legend nova-passo">2. Plano</p>
<?php else: ?>
    <fieldset class="nova-turma panel">
        <legend class="bloco-titulo-legend">1. Dados da turma</legend>
        <p class="field-help">O nome da turma é montado assim: <code>escola-ano-curso-período</code>. Você entra como representante.</p>
        <div class="nova-turma-campos">
            <div>
                <label class="field-label" for="nt-escola">Código da escola *</label>
                <input id="nt-escola" type="text" name="codigo_escola" class="field" inputmode="numeric" pattern="\d+" maxlength="20" required value="<?= h($antigo['codigo_escola'] ?? '') ?>" placeholder="Ex.: 231">
            </div>
            <div>
                <label class="field-label" for="nt-ano">Ano / módulo *</label>
                <select id="nt-ano" name="ano_modulo" class="field" required>
                    <option value="">Escolha</option>
                    <?php foreach ($anosModulo as $v => $l): ?><option value="<?= h($v) ?>"<?= $sel('ano_modulo', $v) ?>><?= h($l) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="field-label" for="nt-curso">Curso *</label>
                <select id="nt-curso" name="curso_id" class="field" required>
                    <option value="">Escolha</option>
                    <?php foreach ($cursos as $c): ?><option value="<?= (int) $c['id'] ?>" data-sigla="<?= h($c['sigla']) ?>"<?= $sel('curso_id', $c['id']) ?>><?= h($c['nome']) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="field-label" for="nt-periodo">Período *</label>
                <select id="nt-periodo" name="periodo_id" class="field" required>
                    <option value="">Escolha</option>
                    <?php foreach ($periodos as $p): ?><option value="<?= (int) $p['id'] ?>" data-sigla="<?= h($p['sigla']) ?>"<?= $sel('periodo_id', $p['id']) ?>><?= h($p['nome']) ?></option><?php endforeach; ?>
                </select>
            </div>
        </div>
        <p class="nova-turma-nome">Nome da turma: <strong id="nt-nome">—</strong></p>
    </fieldset>
    <p class="bloco-titulo-legend nova-passo">2. Plano</p>
<?php endif; ?>
<?php $camposExtras = ob_get_clean(); ?>

<section class="hero-section">
    <div>
        <div class="eyebrow"><span class="status-dot"></span> <?= $ehGremio ? 'Criar grêmio' : 'Criar minha turma' ?></div>
        <h1><?= $ehGremio ? 'Seu grêmio no Prumo' : 'Sua turma no Prumo' ?></h1>
        <p class="hero-subtitle"><?= $ehGremio
            ? 'Reuniões com ata, diretorias, calendário, avisos e projetos do grêmio. Escolha o plano e pague por Pix.'
            : 'Cadastre a turma, escolha o plano e pague por Pix. A turma é liberada assim que a 1ª parcela for paga.' ?></p>
    </div>
    <div class="hero-actions">
        <a class="button button-ghost" href="<?= $basePath ?>/precos"><i class="fas fa-tags"></i> Comparar planos</a>
    </div>
</section>

<?php if (!empty($turmasSemPlano)):
    $rotSit = ['sem_plano' => 'sem plano', 'vencido' => 'plano vencido', 'aguardando' => 'pedido aguardando pagamento']; ?>
    <!-- representante de turma que já existe: contrata o plano para ela, sem criar outra -->
    <section class="panel ja-tenho mb-16">
        <h2><i class="fas fa-circle-check"></i> Contratar para <?= $ehGremio ? 'um grêmio' : 'uma turma' ?> que você já tem</h2>
        <p>Você é representante <?= count($turmasSemPlano) > 1 ? 'destas turmas, que precisam' : 'desta turma, que precisa' ?> de plano. Escolha e siga para o pagamento, sem criar outra.</p>
        <div class="ja-tenho-lista">
            <?php foreach ($turmasSemPlano as $t): ?>
                <a class="ja-tenho-item" href="<?= $basePath ?>/turmas/<?= (int) $t['id'] ?>/plano?plano=<?= h($planoInicial) ?>">
                    <span><strong><?= h($t['nome']) ?></strong><small><?= $rotSit[$t['situacao']['codigo']] ?? '' ?></small></span>
                    <i class="fas fa-arrow-right"></i>
                </a>
            <?php endforeach; ?>
        </div>
        <p class="ja-tenho-ou">Ou cadastre <?= $ehGremio ? 'um grêmio novo' : 'uma turma nova' ?> abaixo.</p>
    </section>
<?php endif; ?>

<?php if (!empty($cruzado)): ?>
    <div class="notice notice-blue">
        <span><i class="fas fa-gift"></i></span>
        <div><strong>Você ganha <?= rtrim(rtrim(number_format((float) $cruzado['percentual'], 2, ',', ''), '0'), ',') ?>% de desconto</strong>
            <p class="mb-0">Porque já tem um plano ativo <?= $ehGremio ? 'de turma' : 'de grêmio' ?>. Ele já está nos preços abaixo.</p></div>
    </div>
<?php endif; ?>

<div class="notice notice-blue">
    <span><i class="fas fa-circle-info"></i></span>
    <div>
        <strong><?= $ehGremio ? 'O grêmio da sua escola já existe no Prumo?' : 'Sua turma já existe no Prumo?' ?></strong>
        <p>Então não precisa comprar: peça o código ao representante e <a href="<?= $basePath ?>/turmas/entrar">entre com o código</a>.</p>
    </div>
</div>

<div class="notice notice-warning plano-teste">
    <span><i class="fas fa-flask"></i></span>
    <div><strong>Ambiente de teste</strong><p>Nenhum valor é cobrado. O pagamento é simulado.</p></div>
</div>

<?php
$urlCupom    = $basePath . ($ehGremio ? '/gremios/novo' : '/turmas/nova');
$manterNaUrl = ['plano' => $planoInicial];
$acaoForm    = $urlCupom;
$textoBotao  = $ehGremio ? 'Criar grêmio e gerar Pix' : 'Criar turma e gerar Pix';
require __DIR__ . '/_escolher.php';
?>

<script>
(function () {
    if (!document.getElementById('nt-escola')) return; // grêmio: nome livre
    var campos = ['nt-escola', 'nt-ano', 'nt-curso', 'nt-periodo'].map(function (id) { return document.getElementById(id); });
    function sigla(el) { var o = el.options ? el.options[el.selectedIndex] : null; return o && o.value ? (o.dataset.sigla || o.value) : ''; }
    function nome() {
        var partes = [campos[0].value.trim(), campos[1].value, sigla(campos[2]), sigla(campos[3])];
        document.getElementById('nt-nome').textContent = partes.every(Boolean) ? partes.join('-').toUpperCase() : '—';
    }
    campos.forEach(function (c) { c.addEventListener('input', nome); c.addEventListener('change', nome); });
    nome();
})();
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
