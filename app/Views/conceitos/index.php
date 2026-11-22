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

<!-- ============ PREVIEW VISUAL ============ -->
<section class="panel mb-16">
    <div class="panel-header">
        <div>
            <h2>Como as faixas estão distribuídas</h2>
            <p>Pré-visualização da divisão de notas de 0 a 100.</p>
        </div>
    </div>

    <div class="faixa-visual">
        <?php if ($wI > 0): ?>
            <div class="faixa i" style="width: <?= $pctI ?>%;">
                I
                <small><?= $iMax ?></small>
            </div>
        <?php endif; ?>
        <?php if ($wR > 0): ?>
            <div class="faixa r" style="width: <?= $pctR ?>%;">
                R
                <small><?= $rMin ?>–<?= $rMax ?></small>
            </div>
        <?php endif; ?>
        <?php if ($wB > 0): ?>
            <div class="faixa b" style="width: <?= $pctB ?>%;">
                B
                <small><?= $bMin ?>–<?= $bMax ?></small>
            </div>
        <?php endif; ?>
        <?php if ($wMB > 0): ?>
            <div class="faixa mb" style="width: <?= $pctMB ?>%;">
                MB
                <small><?= $mbMin ?>+</small>
            </div>
        <?php endif; ?>
    </div>

    <div class="faixa-legenda">
        <span>0</span>
        <span>25</span>
        <span>50</span>
        <span>75</span>
        <span>100</span>
    </div>

    <div class="vis-legenda">
        <div class="legenda-linha">
            <span class="legenda-icone"><i class="fas fa-circle" style="color:#d63548;"></i></span>
            <span><strong>I — Insuficiente</strong>: de 0 até <?= $iMax ?>.</span>
        </div>
        <div class="legenda-linha">
            <span class="legenda-icone"><i class="fas fa-circle" style="color:#d48b1c;"></i></span>
            <span><strong>R — Regular</strong>: de <?= $rMin ?> até <?= $rMax ?>.</span>
        </div>
        <div class="legenda-linha">
            <span class="legenda-icone"><i class="fas fa-circle" style="color:#1c6fd6;"></i></span>
            <span><strong>B — Bom</strong>: de <?= $bMin ?> até <?= $bMax ?>.</span>
        </div>
        <div class="legenda-linha">
            <span class="legenda-icone"><i class="fas fa-circle" style="color:#29946e;"></i></span>
            <span><strong>MB — Muito Bom</strong>: de <?= $mbMin ?> até 100.</span>
        </div>
    </div>
</section>

<!-- ============ FORMULÁRIO ============ -->
<form method="POST" action="<?= $basePath ?>/projetos/<?= (int) $projeto['id'] ?>/conceitos/salvar">
    <?= ViewHelper::csrfField() ?>

    <!-- ============ FAIXAS DE NOTA ============ -->
    <section class="panel mb-16">
        <div class="panel-header">
            <div>
                <h2>Faixas de nota</h2>
                <p>Cada faixa define o conceito final de uma média ponderada. Os limites devem estar em ordem crescente e não podem se sobrepor.</p>
            </div>
        </div>

        <div class="faixas-grid">
            <!-- I -->
            <div class="faixa-card i">
                <div class="faixa-card-header">
                    <strong>I — Insuficiente</strong>
                    <span class="faixa-range">0 → <strong><?= $iMax ?></strong></span>
                </div>
                <div class="faixa-inputs">
                    <div style="flex:1;">
                        <label class="field-label">Nota máxima</label>
                        <input type="number" name="limite_i_max" class="field"
                               min="0" max="100" value="<?= $iMax ?>" required>
                    </div>
                </div>
                <p class="faixa-desc">Qualquer média até este valor será <strong>Insuficiente</strong>.</p>
            </div>

            <!-- R -->
            <div class="faixa-card r">
                <div class="faixa-card-header">
                    <strong>R — Regular</strong>
                    <span class="faixa-range">
                        <strong><?= $rMin ?></strong> → <strong><?= $rMax ?></strong>
                    </span>
                </div>
                <div class="faixa-inputs">
                    <div style="flex:1;">
                        <label class="field-label">Mínimo</label>
                        <input type="number" name="limite_r_min" class="field"
                               min="0" max="100" value="<?= $rMin ?>" required>
                    </div>
                    <div style="flex:1;">
                        <label class="field-label">Máximo</label>
                        <input type="number" name="limite_r_max" class="field"
                               min="0" max="100" value="<?= $rMax ?>" required>
                    </div>
                </div>
                <p class="faixa-desc">Deve ser maior que o máximo de I.</p>
            </div>

            <!-- B -->
            <div class="faixa-card b">
                <div class="faixa-card-header">
                    <strong>B — Bom</strong>
                    <span class="faixa-range">
                        <strong><?= $bMin ?></strong> → <strong><?= $bMax ?></strong>
                    </span>
                </div>
                <div class="faixa-inputs">
                    <div style="flex:1;">
                        <label class="field-label">Mínimo</label>
                        <input type="number" name="limite_b_min" class="field"
                               min="0" max="100" value="<?= $bMin ?>" required>
                    </div>
                    <div style="flex:1;">
                        <label class="field-label">Máximo</label>
                        <input type="number" name="limite_b_max" class="field"
                               min="0" max="100" value="<?= $bMax ?>" required>
                    </div>
                </div>
                <p class="faixa-desc">Deve ser maior que o máximo de R.</p>
            </div>

            <!-- MB -->
            <div class="faixa-card mb">
                <div class="faixa-card-header">
                    <strong>MB — Muito Bom</strong>
                    <span class="faixa-range"><strong><?= $mbMin ?></strong> → 100</span>
                </div>
                <div class="faixa-inputs">
                    <div style="flex:1;">
                        <label class="field-label">Nota mínima</label>
                        <input type="number" name="limite_mb_min" class="field"
                               min="0" max="100" value="<?= $mbMin ?>" required>
                    </div>
                </div>
                <p class="faixa-desc">Deve ser maior que o máximo de B.</p>
            </div>
        </div>
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