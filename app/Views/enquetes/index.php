<?php
// app/Views/enquetes/index.php
// Enquetes da turma/grêmio: criar (representante/vice) e lista (abertas primeiro).
$cssPagina = ['projetos.css', 'enquetes.css'];
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';
$tid = (int) $turma['id'];
$ehGremio = ($turma['tipo'] ?? 'turma') === 'gremio';
?>

<section class="hero-section">
    <div>
        <div class="eyebrow"><span class="status-dot"></span> Enquetes</div>
        <h1>Enquetes e votações</h1>
        <p class="hero-subtitle"><?= $ehGremio ? 'Grêmio' : 'Turma' ?> <strong><?= h($turma['nome']) ?></strong> · decida junto com a turma.</p>
    </div>
    <div class="hero-actions">
        <a class="button button-ghost" href="<?= $basePath ?>/turmas/<?= $tid ?>"><i class="fas fa-arrow-left"></i> Voltar</a>
    </div>
</section>

<?php if ($podeCriar): ?>
    <details class="panel mb-16 enq-nova"<?= !$enquetes ? ' open' : '' ?>>
        <summary><strong><i class="fas fa-plus"></i> Nova enquete</strong></summary>
        <form method="POST" action="<?= $basePath ?>/turmas/<?= $tid ?>/enquetes" class="enq-form">
            <?= ViewHelper::csrfField() ?>
            <label class="enq-larga">Pergunta *<input type="text" name="pergunta" class="field" maxlength="200" required placeholder="Ex.: Qual dia fica melhor para a reunião do projeto?"></label>
            <label class="enq-larga">Detalhes<textarea name="descricao" class="field" rows="2" maxlength="2000" placeholder="Opcional: contexto para a turma decidir"></textarea></label>

            <div class="enq-larga">
                <span class="enq-rotulo">Opções * <small>(de 2 a 12)</small></span>
                <div class="enq-opcoes" id="enq-opcoes">
                    <div class="enq-opcao"><input type="text" name="opcoes[]" class="field" maxlength="150" required placeholder="Opção 1"><button type="button" class="button button-ghost enq-tirar" aria-label="Tirar opção" hidden><i class="fas fa-xmark"></i></button></div>
                    <div class="enq-opcao"><input type="text" name="opcoes[]" class="field" maxlength="150" required placeholder="Opção 2"><button type="button" class="button button-ghost enq-tirar" aria-label="Tirar opção" hidden><i class="fas fa-xmark"></i></button></div>
                </div>
                <button type="button" class="button button-ghost" id="enq-mais"><i class="fas fa-plus"></i> Mais uma opção</button>
            </div>

            <label>Resultado
                <select name="resultado" class="field">
                    <?php foreach (Enquete::RESULTADO as $k => $v): ?><option value="<?= $k ?>"<?= $k === 'apos_votar' ? ' selected' : '' ?>><?= $v ?></option><?php endforeach; ?>
                </select>
            </label>
            <label>Encerra em <small>(opcional)</small><input type="datetime-local" name="encerra_em" class="field" min="<?= date('Y-m-d\TH:i') ?>"></label>
            <label class="enq-check"><input type="checkbox" name="multipla" value="1"> Pode marcar mais de uma opção</label>
            <label class="enq-check"><input type="checkbox" name="anonima" value="1" checked> Votos anônimos (ninguém vê quem votou em quê)</label>
            <p class="enq-larga mb-0"><button type="submit" class="button button-primary" data-carregando="Criando…"><i class="fas fa-square-poll-vertical"></i> Criar e avisar a turma</button></p>
        </form>
    </details>
<?php endif; ?>

<?php if (!$enquetes): ?>
    <section class="panel empty-state">
        <i class="fas fa-square-poll-vertical"></i>
        <p><?= $podeCriar ? 'Nenhuma enquete ainda. Crie a primeira acima.' : 'Nenhuma enquete por aqui ainda. Quando o representante criar, ela aparece aqui e você recebe uma notificação.' ?></p>
    </section>
<?php else: ?>
    <div class="enq-lista" id="enq-lista" data-atualizar="20">
        <?php foreach ($enquetes as $e):
            $pct = $membros ? min(100, round($e['votantes'] / $membros * 100)) : 0; ?>
            <a class="panel enq-card<?= $e['aberta'] ? '' : ' encerrada' ?>" href="<?= $basePath ?>/enquetes/<?= (int) $e['id'] ?>">
                <div class="enq-card-topo">
                    <span class="enq-status <?= $e['aberta'] ? 'aberta' : 'fechada' ?>"><?= $e['aberta'] ? 'Aberta' : 'Encerrada' ?></span>
                    <?php if ($e['aberta'] && $e['encerra_em']): ?><small><i class="far fa-clock"></i> até <?= date('d/m H:i', strtotime($e['encerra_em'])) ?></small><?php endif; ?>
                    <?php if ($e['votei']): ?><small class="enq-votei"><i class="fas fa-check"></i> Você votou</small><?php endif; ?>
                </div>
                <h3><?= h($e['pergunta']) ?></h3>
                <div class="enq-participacao">
                    <div class="enq-barra"><i style="width: <?= $pct ?>%"></i></div>
                    <small><?= (int) $e['votantes'] ?> de <?= $membros ?> votaram · por <?= h($e['autor']) ?></small>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<script>
(function () {
    var caixa = document.getElementById('enq-opcoes'), mais = document.getElementById('enq-mais');
    if (!caixa || !mais) return;
    function atualizar() {
        var linhas = caixa.querySelectorAll('.enq-opcao');
        linhas.forEach(function (l, i) {
            l.querySelector('input').placeholder = 'Opção ' + (i + 1);
            l.querySelector('.enq-tirar').hidden = linhas.length <= 2;
        });
        mais.hidden = linhas.length >= 12;
    }
    mais.addEventListener('click', function () {
        var nova = caixa.querySelector('.enq-opcao').cloneNode(true);
        nova.querySelector('input').value = ''; nova.querySelector('input').required = false;
        caixa.appendChild(nova); atualizar(); nova.querySelector('input').focus();
    });
    caixa.addEventListener('click', function (ev) {
        var b = ev.target.closest('.enq-tirar'); if (!b) return;
        b.closest('.enq-opcao').remove(); atualizar();
    });
    atualizar();
})();
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
