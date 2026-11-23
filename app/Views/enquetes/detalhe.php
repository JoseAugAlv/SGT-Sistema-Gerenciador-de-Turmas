<?php
// app/Views/enquetes/detalhe.php
// Uma enquete: votar (enquanto aberta) e resultado com barras (conforme a regra de visibilidade).
$cssPagina = ['projetos.css', 'enquetes.css'];
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';
$e = $enquete;
$total = array_sum(array_map(fn($o) => (int) $o['votos'], $opcoes));
$maior = $opcoes ? max(array_map(fn($o) => (int) $o['votos'], $opcoes)) : 0;
$tipoInput = $e['multipla'] ? 'checkbox' : 'radio';
?>

<section class="hero-section">
    <div>
        <div class="eyebrow"><span class="status-dot"></span> Enquete · <?= h($turma['nome'] ?? '') ?></div>
        <h1><?= h($e['pergunta']) ?></h1>
        <p class="hero-subtitle">
            Por <?= h($e['autor']) ?> · <?= $e['aberta'] ? 'aberta' : 'encerrada' ?>
            <?php if ($e['aberta'] && $e['encerra_em']): ?> até <?= date('d/m/Y H:i', strtotime($e['encerra_em'])) ?><?php endif; ?>
            · <?= $e['anonima'] ? 'votos anônimos' : 'votos com nome' ?><?= $e['multipla'] ? ' · mais de uma opção' : '' ?>
        </p>
    </div>
    <div class="hero-actions">
        <a class="button button-ghost" href="<?= $basePath ?>/turmas/<?= (int) $e['turma_id'] ?>/enquetes"><i class="fas fa-arrow-left"></i> Enquetes</a>
    </div>
</section>

<?php if ($e['descricao']): ?><section class="panel mb-16"><p class="mb-0"><?= nl2br(h($e['descricao'])) ?></p></section><?php endif; ?>

<section class="panel enq-detalhe" id="enq-detalhe"<?= $e['aberta'] ? ' data-atualizar="10"' : '' ?>>
    <?php if ($e['aberta']): ?>
        <form method="POST" action="<?= $basePath ?>/enquetes/<?= (int) $e['id'] ?>/votar" data-parcial="#enq-detalhe">
            <?= ViewHelper::csrfField() ?>
            <p class="enq-dica"><?= $e['multipla'] ? 'Marque uma ou mais opções.' : 'Escolha uma opção.' ?><?= $meus ? ' Você já votou: pode mudar até a enquete encerrar.' : '' ?></p>
    <?php endif; ?>

    <ul class="enq-opcoes-voto">
        <?php foreach ($opcoes as $o):
            $v = (int) $o['votos'];
            $pct = $total ? round($v / $total * 100) : 0;
            $minha = in_array((int) $o['id'], $meus, true);
            $vence = !$e['aberta'] && $v > 0 && $v === $maior; ?>
            <li class="enq-op<?= $minha ? ' minha' : '' ?><?= $vence ? ' vence' : '' ?>">
                <label>
                    <?php if ($e['aberta']): ?><input type="<?= $tipoInput ?>" name="opcao[]" value="<?= (int) $o['id'] ?>"<?= $minha ? ' checked' : '' ?>><?php endif; ?>
                    <span class="enq-op-texto"><?= h($o['texto']) ?><?= $minha ? ' <small class="enq-meu"><i class="fas fa-check"></i> seu voto</small>' : '' ?><?= $vence ? ' <small class="enq-vence"><i class="fas fa-trophy"></i> mais votada</small>' : '' ?></span>
                    <?php if ($veResultado): ?><span class="enq-op-num"><?= $pct ?>% <small>(<?= $v ?>)</small></span><?php endif; ?>
                </label>
                <?php if ($veResultado): ?>
                    <div class="enq-op-barra"><i style="--p: <?= $pct ?>%"></i></div>
                    <?php if (!$e['anonima'] && !empty($o['nomes'])): ?><small class="enq-nomes"><?= h($o['nomes']) ?></small><?php endif; ?>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ul>

    <?php if ($e['aberta']): ?>
            <button type="submit" class="button button-primary"><i class="fas fa-check-to-slot"></i> <?= $meus ? 'Mudar voto' : 'Votar' ?></button>
        </form>
    <?php endif; ?>

    <p class="enq-rodape">
        <?= $votantes ?> de <?= $membros ?> pessoas votaram.
        <?php if (!$veResultado): ?>
            <?= $e['resultado'] === 'apos_encerrar' ? 'O resultado aparece quando a enquete encerrar.' : 'Vote para ver o resultado parcial.' ?>
        <?php endif; ?>
    </p>
</section>

<?php if ($gestor): ?>
    <div class="enq-acoes" id="enq-acoes">
        <?php if ($e['aberta']): ?>
            <form method="POST" action="<?= $basePath ?>/enquetes/<?= (int) $e['id'] ?>/encerrar" class="d-inline" data-parcial="#enq-detalhe,#enq-acoes">
                <?= ViewHelper::csrfField() ?><button type="submit" class="button button-secondary" onclick="return confirm('Encerrar a votação agora?')"><i class="fas fa-lock"></i> Encerrar votação</button>
            </form>
        <?php endif; ?>
        <form method="POST" action="<?= $basePath ?>/enquetes/<?= (int) $e['id'] ?>/excluir" class="d-inline">
            <?= ViewHelper::csrfField() ?><button type="submit" class="button button-ghost" onclick="return confirm('Excluir a enquete e todos os votos?')"><i class="fas fa-trash"></i> Excluir</button>
        </form>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
