<?php
$cssPagina = ['projetos.css', 'avaliacoes.css'];

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';

// Minhas notas já salvas: [cid][alunoId]
$mapa = [];
foreach ($minhasAvaliacoes as $cid => $linhas) {
    foreach ($linhas as $l) $mapa[(int) $cid][(int) $l['aluno_id']] = $l;
}
// Notas dos outros diretores do grupo (transparência): [cid][alunoId][]
$outrosMapa = [];
foreach ($avaliacoesOutros as $cid => $porDiretor) {
    foreach ($porDiretor as $linhas) {
        foreach ($linhas as $l) $outrosMapa[(int) $cid][(int) $l['aluno_id']][] = $l;
    }
}

require_once __DIR__ . '/../../Helpers/EscalaNota.php';
$fmt = EscalaNota::fmt($cfg); // valor no esquema do projeto (menção/0–100 ou 0–10)
$grupoNome = '';
foreach ($meusGrupos as $g) if ((int) $g['id'] === (int) $grupoAtual) $grupoNome = $g['nome'];
$avaliaveis = count(array_filter($membros, fn($m) => (int) $m['usuario_id'] !== $meuId));
?>

<section class="hero-section">
    <div>
        <h1>Avaliar meu grupo</h1>
        <p class="hero-subtitle">
            <?= h($projeto['nome']) ?><?= $grupoNome ? ' · Grupo ' . h($grupoNome) : '' ?>
        </p>
    </div>
</section>

<?php require __DIR__ . '/../projetos/_nav.php'; ?>
<?php require __DIR__ . '/_nav.php'; ?>

<?php if (count($meusGrupos) > 1): ?>
    <nav class="aval-grupos" aria-label="Escolher grupo">
        <span>Você dirige:</span>
        <?php foreach ($meusGrupos as $g): $atual = (int) $g['id'] === (int) $grupoAtual; ?>
            <a href="<?= $basePath ?>/projetos/<?= (int) $projeto['id'] ?>/avaliacoes/diretor?grupo_id=<?= (int) $g['id'] ?>"
               class="<?= $atual ? 'ativo' : '' ?>"<?= $atual ? ' aria-current="page"' : '' ?>><i class="fas fa-users"></i> <?= h($g['nome']) ?></a>
        <?php endforeach; ?>
    </nav>
<?php endif; ?>

<?php if (empty($criterios)): ?>
    <div class="lista-vazia">
        <i class="fas fa-crown"></i>
        <span>Não há critérios abertos para os diretores avaliarem agora.</span>
    </div>
<?php else: ?>

<form method="POST" id="form-avaliacao" class="aval-app"
      action="<?= $basePath ?>/projetos/<?= (int) $projeto['id'] ?>/avaliacoes/diretor/salvar"
      data-esquema="<?= EscalaNota::esquema($cfg) ?>" data-max="<?= EscalaNota::maximo($cfg) ?>" data-i-max="<?= (float) $cfg['limite_i_max'] ?>" data-r-max="<?= (float) $cfg['limite_r_max'] ?>"
      data-b-max="<?= (float) $cfg['limite_b_max'] ?>" data-chave="aval-dir-<?= (int) $projeto['id'] ?>-<?= (int) $grupoAtual ?>">
    <?= ViewHelper::csrfField() ?>
    <input type="hidden" name="grupo_id" value="<?= (int) $grupoAtual ?>">

    <aside class="aval-criterios" aria-label="Critérios">
        <h2>Critérios</h2>
        <div class="aval-criterios-lista" role="tablist">
            <?php foreach ($criterios as $i => $c): $cid = (int) $c['id']; ?>
                <button type="button" role="tab" class="aval-crit-btn" id="tab-<?= $cid ?>"
                        data-alvo="<?= $cid ?>" aria-controls="painel-<?= $cid ?>" aria-selected="<?= $i === 0 ? 'true' : 'false' ?>">
                    <span class="aval-crit-nome"><?= h($c['nome']) ?></span>
                    <span class="aval-crit-meta">
                        <span class="aval-progresso" data-progresso="<?= $cid ?>">0/<?= $avaliaveis ?></span>
                        <span><?= $c['tipo_avaliacao'] === 'misto' ? 'misto · ' : '' ?>peso <?= $fmt($c['peso']) ?></span>
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
                        <?= $c['tipo_avaliacao'] === 'misto' ? 'Misto: sua nota vale metade, a outra metade é do representante' : 'Avaliado pelos diretores do grupo' ?>
                        <?php if (!empty($c['prazo_avaliacao'])): ?> · prazo <?= h(date('d/m H:i', strtotime($c['prazo_avaliacao']))) ?><?php endif; ?>
                    </p>
                    <?php if (!empty($c['descricao'])): ?>
                        <details class="aval-descricao"><summary>Ver descrição</summary><p><?= nl2br(h($c['descricao'])) ?></p></details>
                    <?php endif; ?>
                </header>

                <div class="aval-ferramentas">
                    <span class="aval-dica">Atalhos: 1 a 4 escolhem o conceito, Enter vai para o próximo.</span>
                    <div class="aval-preencher" role="group" aria-label="Preencher quem está sem nota">
                        <span>Sem nota recebem:</span>
                        <?php foreach (['MB', 'B', 'R', 'I'] as $op): ?>
                            <button type="button" class="conceito-chip c-<?= strtolower($op) ?>" data-preencher="<?= $op ?>"><?= $op ?></button>
                        <?php endforeach; ?>
                    </div>
                </div>

                <ul class="aval-lista">
                    <?php foreach ($membros as $m):
                        $aid   = (int) $m['usuario_id'];
                        $souEu = $aid === $meuId;
                        $reg   = $mapa[$cid][$aid] ?? null;
                        $conc  = $reg['conceito'] ?? '';
                        $val   = $reg ? $fmt($reg['valor_numerico']) : '';
                        $nome  = "aval[{$cid}][{$aid}]";
                        $outros = $outrosMapa[$cid][$aid] ?? [];
                    ?>
                        <li class="aval-linha<?= $souEu ? ' sou-eu' : '' ?>" data-nome="<?= h(mb_strtolower($m['nome'])) ?>">
                            <span class="aval-aluno">
                                <span class="avatar avatar-blue" aria-hidden="true"><?= h(mb_strtoupper(mb_substr($m['nome'], 0, 1))) ?></span>
                                <span>
                                    <strong><?= h($m['nome']) ?><?= $souEu ? ' (você)' : '' ?></strong>
                                    <?php if ($outros): ?>
                                        <small class="aval-outros-dir">
                                            Outro diretor:
                                            <?php foreach ($outros as $o): ?>
                                                <span class="conceito-chip mini c-<?= strtolower($o['conceito']) ?>"><?= h($o['conceito']) ?> <?= $fmt($o['valor_numerico']) ?></span>
                                            <?php endforeach; ?>
                                        </small>
                                    <?php endif; ?>
                                </span>
                            </span>

                            <?php if ($souEu): ?>
                                <span class="txt-mudo">Você não avalia a si mesmo</span>
                            <?php else: ?>
                                <span class="aval-nota" data-celula>
                                    <span class="conceito-grupo" role="radiogroup" aria-label="Conceito de <?= h($m['nome']) ?>">
                                        <?php foreach (['I', 'R', 'B', 'MB'] as $op): ?>
                                            <label class="conceito-op c-<?= strtolower($op) ?>">
                                                <input type="radio" name="<?= $nome ?>[conceito]" value="<?= $op ?>" <?= $conc === $op ? 'checked' : '' ?>>
                                                <span><?= $op ?></span>
                                            </label>
                                        <?php endforeach; ?>
                                    </span>
                                    <input type="text" inputmode="decimal" class="aval-valor" name="<?= $nome ?>[valor]"
                                           value="<?= h($val) ?>" placeholder="—" maxlength="6" aria-label="Valor de 0 a <?= EscalaNota::maximo($cfg) ?> de <?= h($m['nome']) ?>">
                                    <button type="button" class="aval-limpar" data-limpar title="Tirar nota" aria-label="Tirar nota de <?= h($m['nome']) ?>"><i class="fas fa-xmark"></i></button>
                                </span>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </section>
        <?php endforeach; ?>
    </div>

    <div class="aval-barra-salvar">
        <span class="aval-status" data-status aria-live="polite">Nenhuma alteração</span>
        <button type="submit" class="button button-primary" <?= $projeto['encerrado'] ? 'disabled' : '' ?>>
            <i class="fas fa-check"></i> Salvar avaliações
        </button>
    </div>
</form>

<script src="<?= ViewHelper::asset('js/avaliacao.js') ?>"></script>
<?php endif; ?>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
