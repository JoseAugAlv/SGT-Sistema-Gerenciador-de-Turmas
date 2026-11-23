<?php
$cssPagina = ['projetos.css', 'conceitos.css'];

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';

// Calcula larguras das faixas para o preview visual
$iMax  = (int) $cfg['limite_i_max'];
$rMin  = (int) $cfg['limite_r_min'];
$rMax  = (int) $cfg['limite_r_max'];
$bMin  = (int) $cfg['limite_b_min'];
$bMax  = (int) $cfg['limite_b_max'];
$mbMin = (int) $cfg['limite_mb_min'];

$wI  = max(0, $iMax - 0);
$wR  = max(0, $rMax - $rMin + 1);
$wB  = max(0, $bMax - $bMin + 1);
$wMB = max(0, 100 - $mbMin + 1);
$total = max(1, $wI + $wR + $wB + $wMB);

$pctI  = round($wI  / $total * 100, 2);
$pctR  = round($wR  / $total * 100, 2);
$pctB  = round($wB  / $total * 100, 2);
$pctMB = round($wMB / $total * 100, 2);
?>

<section class="hero-section">
    <div>
        <div class="eyebrow">
            <span class="status-dot"></span>
            Conceitos
        </div>
        <h1><?= h($projeto['nome']) ?></h1>
        <p class="hero-subtitle">
            <a href="<?= $basePath ?>/turmas/<?= (int) $projeto['turma_id'] ?>"><?= h($projeto['turma_nome']) ?></a>
            — Defina as faixas de nota e a visibilidade dos boletins.
        </p>
    </div>
    <div class="hero-actions">
        <a class="button button-ghost" href="<?= $basePath ?>/projetos/<?= (int) $projeto['id'] ?>/conceitos/preview">
            <i class="fas fa-eye"></i> Pré-visualizar
        </a>
    </div>
</section>

<?php require __DIR__ . '/../projetos/_nav.php'; ?>

<?php if ($projeto['encerrado']): ?>
    <div class="notice notice-blue mt-0 mb-16">
        <span>i</span>
        <div>
            <strong>Projeto encerrado</strong>
            <p class="mb-0">As configurações de conceito não podem mais ser alteradas.</p>
        </div>
    </div>
<?php endif; ?>

<!-- ============ ESQUEMA E FAIXAS ============ -->
<form method="POST" action="<?= $basePath ?>/projetos/<?= (int) $projeto['id'] ?>/conceitos/salvar">
    <?= ViewHelper::csrfField() ?>

    <section class="panel mb-16">
        <div class="panel-header"><div><h2>Esquema de notas</h2><p>Escolha como as notas são lançadas e lidas. Os pesos dos critérios continuam valendo em qualquer esquema.</p></div></div>
        <?php require __DIR__ . '/../projetos/_esquema_nota.php'; ?>
    </section>

    <section class="panel mb-16">
        <div class="panel-header"><div><h2>Avaliação dos representantes</h2><p>Vale para os critérios avaliados pelo representante (e a parte dele nos critérios mistos).</p></div></div>
        <?php $avaliacaoReps = $projeto['avaliacao_reps'] ?? 'compartilhada'; require __DIR__ . '/../projetos/_avaliacao_reps.php'; ?>
    </section>

    <!-- ============ VISIBILIDADE ============ -->
    <section class="panel mb-16">
        <div class="panel-header">
            <div>
                <h2>Visibilidade de boletins</h2>
                <p>Quem pode ver o boletim de quem dentro do projeto.</p>
            </div>
        </div>

        <div class="visibilidade-grid">
            <!-- Diretor -->
            <div class="visibilidade-card diretor">
                <span class="vis-label">Como diretor</span>
                <h3><i class="fas fa-crown"></i> Diretores podem ver</h3>

                <div class="radio-cards">
                    <?php
                    $opcoesVis = [
                        'turma'   => ['label' => 'Toda a turma',    'desc' => 'Diretor vê notas de todos os alunos da turma.'],
                        'grupo'   => ['label' => 'Apenas o grupo',  'desc' => 'Diretor vê apenas notas dos alunos do próprio grupo.'],
                        'proprio' => ['label' => 'Apenas o próprio','desc' => 'Diretor vê apenas o próprio boletim.'],
                    ];
                    foreach ($opcoesVis as $val => $info):
                    ?>
                        <label class="radio-card">
                            <input type="radio" name="visibilidade_diretor" value="<?= h($val) ?>"
                                   <?= $cfg['visibilidade_diretor'] === $val ? 'checked' : '' ?> required>
                            <div class="radio-card-info">
                                <strong><?= h($info['label']) ?></strong>
                                <small><?= h($info['desc']) ?></small>
                            </div>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Aluno -->
            <div class="visibilidade-card aluno">
                <span class="vis-label">Como aluno</span>
                <h3><i class="fas fa-user-graduate"></i> Alunos podem ver</h3>

                <div class="radio-cards">
                    <?php
                    foreach ($opcoesVis as $val => $info):
                    ?>
                        <label class="radio-card">
                            <input type="radio" name="visibilidade_aluno" value="<?= h($val) ?>"
                                   <?= $cfg['visibilidade_aluno'] === $val ? 'checked' : '' ?> required>
                            <div class="radio-card-info">
                                <strong><?= h($info['label']) ?></strong>
                                <small><?= h($info['desc']) ?></small>
                            </div>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="notice notice-blue mt-0">
            <span>i</span>
            <div>
                <strong>Representante e master sempre veem tudo</strong>
                <p class="mb-0">
                    Estas configurações se aplicam apenas a diretores e alunos comuns.
                    Representantes e master continuam com acesso total aos boletins do projeto.
                </p>
            </div>
        </div>
    </section>

    <!-- ============ AÇÕES ============ -->
    <div style="display:flex; flex-wrap:wrap; gap:10px; align-items:center;">
        <button type="submit" class="button button-primary">
            <i class="fas fa-check"></i> Salvar configuração
        </button>
        <a class="button button-ghost" href="<?= $basePath ?>/projetos/<?= (int) $projeto['id'] ?>">
            Cancelar
        </a>
    </div>
</form>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>