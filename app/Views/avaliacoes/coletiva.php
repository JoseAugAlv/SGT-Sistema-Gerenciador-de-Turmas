<?php
$cssPagina = ['projetos.css', 'avaliacoes.css'];

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';

require_once __DIR__ . '/../../Helpers/EscalaNota.php';
$fmt = EscalaNota::fmt($cfg); // valor no esquema do projeto (menção/0–100 ou 0–10)
?>

<section class="hero-section">
    <div>
        <h1>Nota coletiva</h1>
        <p class="hero-subtitle">
            <?= h($projeto['nome']) ?> · uma nota por grupo, que vale para todos os membros
        </p>
    </div>
</section>

<?php require __DIR__ . '/../projetos/_nav.php'; ?>
<?php require __DIR__ . '/_nav.php'; ?>

<?php if (empty($criterios) || empty($grupos)): ?>
    <div class="lista-vazia">
        <i class="fas fa-users-rectangle"></i>
        <span><?= empty($criterios) ? 'Não há critérios de nota coletiva abertos neste projeto.' : 'Este projeto ainda não tem grupos.' ?></span>
    </div>
<?php else: ?>

<form method="POST" id="form-avaliacao" class="aval-app"
      action="<?= $basePath ?>/projetos/<?= (int) $projeto['id'] ?>/avaliacoes/coletiva/salvar"
      data-esquema="<?= EscalaNota::esquema($cfg) ?>" data-max="<?= EscalaNota::maximo($cfg) ?>" data-i-max="<?= (float) $cfg['limite_i_max'] ?>" data-r-max="<?= (float) $cfg['limite_r_max'] ?>"
      data-b-max="<?= (float) $cfg['limite_b_max'] ?>" data-chave="aval-col-<?= (int) $projeto['id'] ?>">
    <?= ViewHelper::csrfField() ?>

    <aside class="aval-criterios" aria-label="Critérios">
        <h2>Critérios</h2>
        <div class="aval-criterios-lista" role="tablist">
            <?php foreach ($criterios as $i => $c): $cid = (int) $c['id']; ?>
                <button type="button" role="tab" class="aval-crit-btn" id="tab-<?= $cid ?>"
                        data-alvo="<?= $cid ?>" aria-controls="painel-<?= $cid ?>" aria-selected="<?= $i === 0 ? 'true' : 'false' ?>">
                    <span class="aval-crit-nome"><?= h($c['nome']) ?></span>
                    <span class="aval-crit-meta">
                        <span class="aval-progresso" data-progresso="<?= $cid ?>">0/<?= count($grupos) ?></span>
                        <span>peso <?= $fmt($c['peso']) ?></span>
                    </span>
                    <span class="aval-barra"><i data-barra="<?= $cid ?>"></i></span>
                </button>
            <?php endforeach; ?>
        </div>
    </aside>

    <div class="aval-area">
        <?php foreach ($criterios as $i => $c): $cid = (int) $c['id']; ?>
            <section class="aval-painel" id="painel-<?= $cid ?>" role="tabpanel" aria-labelledby="tab-<?= $cid ?>"
                     data-criterio="<?= $cid ?>" <?= $i === 0 ? '' : 'hidden' ?>>
                <header class="aval-painel-topo">
                    <h2><?= h($c['nome']) ?></h2>
                    <p>
                        <?= count($grupos) ?> grupo(s)
                        <?php if (!empty($c['prazo_avaliacao'])): ?> · prazo <?= h(date('d/m H:i', strtotime($c['prazo_avaliacao']))) ?><?php endif; ?>
                    </p>
                    <?php if (!empty($c['descricao'])): ?>
                        <details class="aval-descricao"><summary>Ver descrição</summary><p><?= nl2br(h($c['descricao'])) ?></p></details>
                    <?php endif; ?>
                </header>

                <ul class="aval-lista">
                    <?php foreach ($grupos as $g):
                        $gid  = (int) $g['id'];
                        $reg  = $existentes[$cid][$gid] ?? null;
                        $conc = $reg['conceito'] ?? '';
                        $val  = $reg ? $fmt($reg['valor_numerico']) : '';
                        $just = $reg['justificativa'] ?? '';
                        $nome = "aval[{$cid}][{$gid}]";
                    ?>
                        <li class="aval-linha com-justificativa" data-nome="<?= h(mb_strtolower($g['nome'])) ?>">
                            <span class="aval-aluno">
                                <span class="avatar avatar-blue" aria-hidden="true"><i class="fas fa-users"></i></span>
                                <span>
                                    <strong><?= h($g['nome']) ?></strong>
                                    <small><?= (int) ($g['total_membros'] ?? 0) ?> membro(s)</small>
                                </span>
                            </span>
                            <span class="aval-nota" data-celula>
                                <span class="conceito-grupo" role="radiogroup" aria-label="Conceito do grupo <?= h($g['nome']) ?>">
                                    <?php foreach (['I', 'R', 'B', 'MB'] as $op): ?>
                                        <label class="conceito-op c-<?= strtolower($op) ?>">
                                            <input type="radio" name="<?= $nome ?>[conceito]" value="<?= $op ?>" <?= $conc === $op ? 'checked' : '' ?>>
                                            <span><?= $op ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                </span>
                                <input type="text" inputmode="decimal" class="aval-valor" name="<?= $nome ?>[valor]"
                                       value="<?= h($val) ?>" placeholder="—" maxlength="6" aria-label="Valor de 0 a <?= EscalaNota::maximo($cfg) ?> do grupo <?= h($g['nome']) ?>">
                            </span>
                            <button type="button" class="aval-comentar" data-comentar><i class="fas fa-comment"></i> Comentar (opcional)</button>
                            <label class="aval-just">
                                <span class="aval-just-rotulo">Comentário <em>(opcional, visível para a coordenação)</em></span>
                                <textarea name="<?= $nome ?>[justificativa]" rows="2" maxlength="1000" placeholder="Observação sobre o trabalho do grupo"><?= h($just) ?></textarea>
                            </label>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </section>
        <?php endforeach; ?>
    </div>

    <div class="aval-barra-salvar">
        <span class="aval-status" data-status aria-live="polite">Nenhuma alteração</span>
        <button type="submit" class="button button-primary" <?= $projeto['encerrado'] ? 'disabled' : '' ?>>
            <i class="fas fa-check"></i> Salvar notas coletivas
        </button>
    </div>
</form>

<script src="<?= ViewHelper::asset('js/avaliacao.js') ?>"></script>
<?php endif; ?>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
