<?php
$cssPagina = ['projetos.css', 'avaliacoes.css'];

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';

require_once __DIR__ . '/../../Helpers/EscalaNota.php';
$fmt = EscalaNota::fmt($cfg); // valor no esquema do projeto (menção/0–100 ou 0–10)
$blocos = array_values(array_filter($dados, fn($b) => !empty($b['colegas'])));
?>

<section class="hero-section">
    <div>
        <h1>Avaliar colegas</h1>
        <p class="hero-subtitle">
            <?= h($projeto['nome']) ?> ·
            <a href="<?= $basePath ?>/turmas/<?= (int) $projeto['turma_id'] ?>"><?= h($projeto['turma_nome']) ?></a>
        </p>
    </div>
</section>

<?php require __DIR__ . '/../projetos/_nav.php'; ?>
<?php require __DIR__ . '/_nav.php'; ?>

<?php if (empty($blocos)): ?>
    <div class="lista-vazia">
        <i class="fas fa-users"></i>
        <span>
            <?= empty($dados)
                ? 'Não há critérios de avaliação por pares abertos agora.'
                : 'Você ainda não está em um grupo deste projeto, então não há colegas para avaliar.' ?>
        </span>
    </div>
<?php else: ?>

<p class="aval-explica">
    <i class="fas fa-circle-info" aria-hidden="true"></i>
    <span>Você avalia só os colegas do seu grupo. Em <b class="c-r">R</b> ou <b class="c-i">I</b> a justificativa é obrigatória.
    Ela é interna: só o representante e o diretor do grupo veem. Seus colegas não sabem quem deu cada nota.</span>
</p>

<form method="POST" id="form-avaliacao" class="aval-app"
      action="<?= $basePath ?>/projetos/<?= (int) $projeto['id'] ?>/avaliacoes/pares/salvar"
      data-autosave="1"
      data-esquema="<?= EscalaNota::esquema($cfg) ?>" data-max="<?= EscalaNota::maximo($cfg) ?>" data-i-max="<?= (float) $cfg['limite_i_max'] ?>" data-r-max="<?= (float) $cfg['limite_r_max'] ?>"
      data-b-max="<?= (float) $cfg['limite_b_max'] ?>" data-chave="aval-pares-<?= (int) $projeto['id'] ?>">
    <?= ViewHelper::csrfField() ?>

    <aside class="aval-criterios" aria-label="Critérios">
        <h2>Critérios</h2>
        <div class="aval-criterios-lista" role="tablist">
            <?php foreach ($blocos as $i => $b): $c = $b['criterio']; $cid = (int) $c['id']; ?>
                <button type="button" role="tab" class="aval-crit-btn" id="tab-<?= $cid ?>"
                        data-alvo="<?= $cid ?>" aria-controls="painel-<?= $cid ?>" aria-selected="<?= $i === 0 ? 'true' : 'false' ?>">
                    <span class="aval-crit-nome"><?= h($c['nome']) ?></span>
                    <span class="aval-crit-meta">
                        <span class="aval-progresso" data-progresso="<?= $cid ?>">0/<?= count($b['colegas']) ?></span>
                        <span>peso <?= $fmt($c['peso']) ?></span>
                    </span>
                    <span class="aval-barra"><i data-barra="<?= $cid ?>"></i></span>
                </button>
            <?php endforeach; ?>
        </div>
    </aside>

    <div class="aval-area">
        <?php foreach ($blocos as $i => $b): $c = $b['criterio']; $cid = (int) $c['id']; ?>
            <section class="aval-painel" id="painel-<?= $cid ?>" role="tabpanel" aria-labelledby="tab-<?= $cid ?>"
                     data-criterio="<?= $cid ?>" <?= $i === 0 ? '' : 'hidden' ?>>
                <header class="aval-painel-topo">
                    <h2><?= h($c['nome']) ?></h2>
                    <p>
                        <?= count($b['colegas']) ?> colega(s)
                        <?php if (!empty($c['prazo_avaliacao'])): ?> · prazo <?= h(date('d/m H:i', strtotime($c['prazo_avaliacao']))) ?><?php endif; ?>
                    </p>
                    <?php if (!empty($c['descricao'])): ?>
                        <details class="aval-descricao"><summary>Ver descrição</summary><p><?= nl2br(h($c['descricao'])) ?></p></details>
                    <?php endif; ?>
                </header>

                <ul class="aval-lista">
                    <?php foreach ($b['colegas'] as $col):
                        $aid  = (int) $col['id'];
                        $reg  = $b['minhas'][$aid] ?? null;
                        $conc = $reg['conceito'] ?? '';
                        $val  = $reg ? $fmt($reg['valor_numerico']) : '';
                        $just = $reg['justificativa'] ?? '';
                        $nome = "aval[{$cid}][{$aid}]";
                    ?>
                        <li class="aval-linha com-justificativa" data-nome="<?= h(mb_strtolower($col['nome'])) ?>">
                            <span class="aval-aluno">
                                <span class="avatar avatar-blue" aria-hidden="true"><?= h(mb_strtoupper(mb_substr($col['nome'], 0, 1))) ?></span>
                                <span>
                                    <strong><?= h($col['nome']) ?></strong>
                                    <?php if (!empty($col['grupos'])): ?><small><?= h($col['grupos']) ?></small><?php endif; ?>
                                </span>
                            </span>
                            <span class="aval-nota" data-celula>
                                <span class="conceito-grupo" role="radiogroup" aria-label="Conceito de <?= h($col['nome']) ?>">
                                    <?php foreach (['I', 'R', 'B', 'MB'] as $op): ?>
                                        <label class="conceito-op c-<?= strtolower($op) ?>">
                                            <input type="radio" name="<?= $nome ?>[conceito]" value="<?= $op ?>" <?= $conc === $op ? 'checked' : '' ?>>
                                            <span><?= $op ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                </span>
                                <input type="text" inputmode="decimal" class="aval-valor" name="<?= $nome ?>[valor]"
                                       value="<?= h($val) ?>" placeholder="—" maxlength="6" aria-label="Valor de 0 a <?= EscalaNota::maximo($cfg) ?> de <?= h($col['nome']) ?>">
                                <button type="button" class="aval-limpar" data-limpar title="Tirar nota" aria-label="Tirar nota de <?= h($col['nome']) ?>"><i class="fas fa-xmark"></i></button>
                            </span>
                            <button type="button" class="aval-comentar" data-comentar><i class="fas fa-comment"></i> Comentar (opcional)</button>
                            <label class="aval-just" data-just>
                                <span class="aval-just-rotulo">Justificativa <em class="just-obrig">(obrigatória em R e I)</em><em class="just-opc">(opcional em B e MB)</em></span>
                                <textarea name="<?= $nome ?>[justificativa]" rows="2" maxlength="1000"
                                          placeholder="O que essa pessoa fez (ou deixou de fazer) neste critério?"><?= h($just) ?></textarea>
                            </label>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </section>
        <?php endforeach; ?>
    </div>

    <div class="aval-barra-salvar">
        <span class="aval-status" data-status aria-live="polite">Nenhuma alteração</span>
        <button type="submit" class="button button-primary">
            <i class="fas fa-check"></i> Salvar avaliações
        </button>
    </div>
</form>

<script src="<?= ViewHelper::asset('js/avaliacao.js') ?>"></script>
<?php endif; ?>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
