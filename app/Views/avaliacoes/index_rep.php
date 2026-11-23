<?php
$cssPagina = ['projetos.css', 'avaliacoes.css'];

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';

// Valores já salvos por este avaliador: [cid][alunoId]
$mapa = [];
foreach ($minhasAvaliacoes as $cid => $linhas) {
    foreach ($linhas as $l) {
        $mapa[(int) $cid][(int) $l['aluno_id']] = $l;
    }
}

// Critérios abertos, na ordem: todos → apenas diretores → apenas representantes
$abertos = fn($lista) => array_values(array_filter($lista, fn($c) => !$c['bloqueado']));
$criterios = array_merge($abertos($critTodos), $abertos($critDiretores), $abertos($critRepresent));
// Os que esta pessoa avalia vêm primeiro; os de leitura ficam no fim da lista
usort($criterios, fn($a, $b) => (int) empty($editaveis[(int) $a['id']]) <=> (int) empty($editaveis[(int) $b['id']]));

$tipoLabel = [
    'representante' => 'Representante', 'misto' => 'Misto (diretores + representantes)',
    'diretor' => 'Diretores', 'pares' => 'Pares', 'autoavaliacao' => 'Autoavaliação',
];
$aplicavelLabel = ['apenas_diretores' => 'só diretores', 'apenas_representantes' => 'só representantes'];

require_once __DIR__ . '/../../Helpers/EscalaNota.php';
$fmt = EscalaNota::fmt($cfg); // valor no esquema do projeto (menção/0–100 ou 0–10)
$conceitoDe = fn($v) => $v === null ? '' : NotaService::conceitoPorValor((float) $v, $cfg);
?>

<section class="hero-section">
    <div>
        <h1><?= h($projeto['nome']) ?></h1>
        <p class="hero-subtitle">
            <a href="<?= $basePath ?>/turmas/<?= (int) $projeto['turma_id'] ?>"><?= h($projeto['turma_nome']) ?></a>
            · <?= count($alunos) ?> alunos · <?= count($criterios) ?> critério(s) aberto(s)
        </p>
    </div>
</section>

<?php require __DIR__ . '/../projetos/_nav.php'; ?>
<?php require __DIR__ . '/_nav.php'; ?>

<?php if ($projeto['encerrado']): ?>
    <div class="flash flash-aviso"><i class="fas fa-lock"></i><div class="flash-texto"><strong>Projeto encerrado</strong><span>Não é possível salvar novas avaliações.</span></div></div>
<?php endif; ?>

<?php if (empty($criterios)): ?>
    <div class="lista-vazia">
        <i class="fas fa-clipboard-check"></i>
        <span>Nenhum critério aberto para avaliar agora. Critérios com prazo vencido ficam bloqueados.</span>
    </div>
<?php else: ?>

<form method="POST" id="form-avaliacao" class="aval-app"
      action="<?= $basePath ?>/projetos/<?= (int) $projeto['id'] ?>/avaliacoes/representante/salvar"
      data-esquema="<?= EscalaNota::esquema($cfg) ?>" data-max="<?= EscalaNota::maximo($cfg) ?>" data-i-max="<?= (float) $cfg['limite_i_max'] ?>" data-r-max="<?= (float) $cfg['limite_r_max'] ?>"
      data-b-max="<?= (float) $cfg['limite_b_max'] ?>" data-chave="aval-rep-<?= (int) $projeto['id'] ?>">
    <?= ViewHelper::csrfField() ?>

    <!-- ============ CRITÉRIOS ============ -->
    <aside class="aval-criterios" aria-label="Critérios">
        <h2>Critérios</h2>
        <div class="aval-criterios-lista" role="tablist">
            <?php foreach ($criterios as $i => $c): $cid = (int) $c['id']; $edita = !empty($editaveis[$cid]); ?>
                <button type="button" role="tab" class="aval-crit-btn" id="tab-<?= $cid ?>"
                        data-alvo="<?= $cid ?>" aria-controls="painel-<?= $cid ?>" aria-selected="<?= $i === 0 ? 'true' : 'false' ?>">
                    <span class="aval-crit-nome"><?= h($c['nome']) ?></span>
                    <span class="aval-crit-meta">
                        <?php if ($edita): ?>
                            <span class="aval-progresso" data-progresso="<?= $cid ?>">0/<?= count($alunos) ?></span>
                        <?php else: ?>
                            <span class="aval-leitura"><i class="fas fa-eye"></i> leitura</span>
                        <?php endif; ?>
                        <span>peso <?= $fmt($c['peso']) ?></span>
                    </span>
                    <?php if ($edita): ?><span class="aval-barra"><i data-barra="<?= $cid ?>"></i></span><?php endif; ?>
                </button>
            <?php endforeach; ?>
        </div>
    </aside>

    <!-- ============ ALUNOS DO CRITÉRIO ============ -->
    <div class="aval-area">
        <?php foreach ($criterios as $i => $c):
            $cid = (int) $c['id'];
            $edita = !empty($editaveis[$cid]);
        ?>
            <section class="aval-painel" id="painel-<?= $cid ?>" role="tabpanel" aria-labelledby="tab-<?= $cid ?>"
                     data-criterio="<?= $cid ?>" <?= $i === 0 ? '' : 'hidden' ?>>

                <header class="aval-painel-topo">
                    <div>
                        <h2><?= h($c['nome']) ?></h2>
                        <p>
                            Avaliado por: <?= h($tipoLabel[$c['tipo_avaliacao']] ?? $c['tipo_avaliacao']) ?>
                            <?php if (isset($aplicavelLabel[$c['aplicavel_a']])): ?> · <?= $aplicavelLabel[$c['aplicavel_a']] ?><?php endif; ?>
                            <?php if (!empty($c['prazo_avaliacao'])): ?> · prazo <?= h(date('d/m H:i', strtotime($c['prazo_avaliacao']))) ?><?php endif; ?>
                        </p>
                    </div>
                    <?php if (!empty($c['descricao'])): ?>
                        <details class="aval-descricao"><summary>Ver descrição</summary><p><?= nl2br(h($c['descricao'])) ?></p></details>
                    <?php endif; ?>
                </header>

                <?php if ($edita): ?>
                    <div class="aval-ferramentas">
                        <label class="aval-busca">
                            <i class="fas fa-magnifying-glass" aria-hidden="true"></i>
                            <input type="search" placeholder="Buscar aluno" data-busca-aluno aria-label="Buscar aluno">
                        </label>
                        <div class="aval-preencher" role="group" aria-label="Preencher quem está sem nota">
                            <span>Sem nota recebem:</span>
                            <?php foreach (['MB', 'B', 'R', 'I'] as $op): ?>
                                <button type="button" class="conceito-chip c-<?= strtolower($op) ?>" data-preencher="<?= $op ?>"><?= $op ?></button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <p class="aval-aviso-leitura">
                        <i class="fas fa-eye"></i>
                        Este critério é avaliado por <?= h(mb_strtolower($tipoLabel[$c['tipo_avaliacao']] ?? '')) ?>. Você vê aqui a nota calculada, sem editar.
                    </p>
                <?php endif; ?>

                <ul class="aval-lista">
                    <?php foreach ($alunos as $a):
                        $aid  = (int) $a['id'];
                        $ini  = mb_strtoupper(mb_substr($a['nome'], 0, 1));
                    ?>
                        <li class="aval-linha" data-nome="<?= h(mb_strtolower($a['nome'])) ?>">
                            <span class="aval-aluno">
                                <span class="avatar avatar-blue" aria-hidden="true"><?= h($ini) ?></span>
                                <span><strong><?= h($a['nome']) ?></strong><?php if ($a['papel'] === 'representante'): ?><small>representante</small><?php endif; ?></span>
                            </span>

                            <?php if ($edita):
                                $reg  = $mapa[$cid][$aid] ?? null;
                                $conc = $reg['conceito'] ?? '';
                                $val  = $reg ? $fmt($reg['valor_numerico']) : '';
                                $nome = "aval[{$cid}][{$aid}]";
                            ?>
                                <span class="aval-nota" data-celula>
                                    <span class="conceito-grupo" role="radiogroup" aria-label="Conceito de <?= h($a['nome']) ?>">
                                        <?php foreach (['I', 'R', 'B', 'MB'] as $op): ?>
                                            <label class="conceito-op c-<?= strtolower($op) ?>">
                                                <input type="radio" name="<?= $nome ?>[conceito]" value="<?= $op ?>" <?= $conc === $op ? 'checked' : '' ?>>
                                                <span><?= $op ?></span>
                                            </label>
                                        <?php endforeach; ?>
                                    </span>
                                    <input type="text" inputmode="decimal" class="aval-valor" name="<?= $nome ?>[valor]"
                                           value="<?= h($val) ?>" placeholder="—" maxlength="6" aria-label="Valor de 0 a <?= EscalaNota::maximo($cfg) ?> de <?= h($a['nome']) ?>">
                                    <button type="button" class="aval-limpar" data-limpar title="Tirar nota" aria-label="Tirar nota de <?= h($a['nome']) ?>"><i class="fas fa-xmark"></i></button>
                                </span>
                            <?php else:
                                $n = $notasLeitura[$cid][$aid] ?? null;
                                $cc = $conceitoDe($n);
                            ?>
                                <span class="aval-nota-leitura">
                                    <?php if ($n === null): ?>
                                        <span class="txt-mudo">sem nota</span>
                                    <?php else: ?>
                                        <span class="conceito-chip c-<?= strtolower($cc) ?>"><?= $cc ?></span>
                                        <span class="num"><?= $fmt(round($n, 2)) ?></span>
                                    <?php endif; ?>
                                </span>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <p class="aval-sem-resultado" hidden>Nenhum aluno com esse nome.</p>
            </section>
        <?php endforeach; ?>
    </div>

    <!-- ============ BARRA DE SALVAR ============ -->
    <div class="aval-barra-salvar">
        <span class="aval-status" data-status aria-live="polite">Nenhuma alteração</span>
        <details class="aval-perigo">
            <summary>Mais opções</summary>
            <label class="check-line">
                <input type="checkbox" name="limpar_antes" value="1" data-limpar-antes>
                <span><strong>Limpar e salvar:</strong> apaga as suas notas dos critérios abertos antes de salvar o que está na tela.</span>
            </label>
        </details>
        <button type="submit" class="button button-primary" <?= $projeto['encerrado'] ? 'disabled' : '' ?>>
            <i class="fas fa-check"></i> Salvar avaliações
        </button>
    </div>
</form>

<script src="<?= ViewHelper::asset('js/avaliacao.js') ?>"></script>
<?php endif; ?>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
