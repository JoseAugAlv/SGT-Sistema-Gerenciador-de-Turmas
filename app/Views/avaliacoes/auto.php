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
        <h1>Autoavaliação</h1>
        <p class="hero-subtitle">
            <?= h($projeto['nome']) ?> ·
            <a href="<?= $basePath ?>/turmas/<?= (int) $projeto['turma_id'] ?>"><?= h($projeto['turma_nome']) ?></a>
        </p>
    </div>
</section>

<?php require __DIR__ . '/../projetos/_nav.php'; ?>
<?php require __DIR__ . '/_nav.php'; ?>

<?php if (empty($criterios)): ?>
    <div class="lista-vazia">
        <i class="fas fa-user-check"></i>
        <span>Não há critérios de autoavaliação abertos agora.</span>
    </div>
<?php else: ?>

<p class="aval-explica">
    <i class="fas fa-circle-info" aria-hidden="true"></i>
    <span>Avalie com sinceridade o seu próprio trabalho em cada critério. Escolha um conceito ou digite um valor de 0 a 100.</span>
</p>

<form method="POST" id="form-avaliacao" class="aval-app aval-app-simples"
      action="<?= $basePath ?>/projetos/<?= (int) $projeto['id'] ?>/avaliacoes/auto/salvar"
      data-esquema="<?= EscalaNota::esquema($cfg) ?>" data-max="<?= EscalaNota::maximo($cfg) ?>" data-i-max="<?= (float) $cfg['limite_i_max'] ?>" data-r-max="<?= (float) $cfg['limite_r_max'] ?>"
      data-b-max="<?= (float) $cfg['limite_b_max'] ?>" data-chave="aval-auto-<?= (int) $projeto['id'] ?>">
    <?= ViewHelper::csrfField() ?>

    <section class="aval-painel" data-criterio="auto">
        <ul class="aval-lista">
            <?php foreach ($criterios as $c):
                $cid  = (int) $c['id'];
                $reg  = $minhas[$cid] ?? null;
                $conc = $reg['conceito'] ?? '';
                $val  = $reg ? $fmt($reg['valor_numerico']) : '';
            ?>
                <li class="aval-linha aval-linha-criterio" data-nome="<?= h(mb_strtolower($c['nome'])) ?>">
                    <span class="aval-crit-texto">
                        <strong><?= h($c['nome']) ?></strong>
                        <small>
                            peso <?= $fmt($c['peso']) ?>
                            <?php if (!empty($c['prazo_avaliacao'])): ?> · prazo <?= h(date('d/m H:i', strtotime($c['prazo_avaliacao']))) ?><?php endif; ?>
                        </small>
                        <?php if (!empty($c['descricao'])): ?>
                            <details class="aval-descricao"><summary>Ver descrição</summary><p><?= nl2br(h($c['descricao'])) ?></p></details>
                        <?php endif; ?>
                    </span>
                    <span class="aval-nota" data-celula>
                        <span class="conceito-grupo" role="radiogroup" aria-label="Conceito em <?= h($c['nome']) ?>">
                            <?php foreach (['I', 'R', 'B', 'MB'] as $op): ?>
                                <label class="conceito-op c-<?= strtolower($op) ?>">
                                    <input type="radio" name="aval[<?= $cid ?>][conceito]" value="<?= $op ?>" <?= $conc === $op ? 'checked' : '' ?>>
                                    <span><?= $op ?></span>
                                </label>
                            <?php endforeach; ?>
                        </span>
                        <input type="text" inputmode="decimal" class="aval-valor" name="aval[<?= $cid ?>][valor]"
                               value="<?= h($val) ?>" placeholder="—" maxlength="6" aria-label="Valor de 0 a <?= EscalaNota::maximo($cfg) ?> em <?= h($c['nome']) ?>">
                        <button type="button" class="aval-limpar" data-limpar title="Tirar nota" aria-label="Tirar nota de <?= h($c['nome']) ?>"><i class="fas fa-xmark"></i></button>
                    </span>
                </li>
            <?php endforeach; ?>
        </ul>
    </section>

    <div class="aval-barra-salvar">
        <span class="aval-status" data-status aria-live="polite">Nenhuma alteração</span>
        <button type="submit" class="button button-primary"><i class="fas fa-check"></i> Salvar autoavaliação</button>
    </div>
</form>

<script src="<?= ViewHelper::asset('js/avaliacao.js') ?>"></script>
<?php endif; ?>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
