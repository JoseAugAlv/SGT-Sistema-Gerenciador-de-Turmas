<?php
$cssPagina = ['projetos.css', 'grupos.css'];

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';
?>

<section class="hero-section">
    <div>
        <div class="eyebrow">
            <span class="status-dot"></span>
            Trocar diretor
        </div>
        <h1>Trocar Diretor</h1>
        <p class="hero-subtitle">
            <?= h($grupo['nome']) ?> — projeto <?= h($projeto['nome']) ?>
        </p>
    </div>
    <div class="hero-actions">
        <a class="button button-ghost" href="<?= $basePath ?>/grupos/<?= (int) $grupo['id'] ?>/diretores">
            <i class="fas fa-arrow-left"></i> Voltar
        </a>
    </div>
</section>

<?php require __DIR__ . '/../projetos/_nav.php'; ?>

<section class="panel">
    <div class="notice notice-blue mt-0">
        <span>i</span>
        <div>
            <strong>Como funciona a troca</strong>
            <p>
                O diretor que sai é <strong>desativado</strong> — avaliações antigas dele continuam
                existindo. As <strong>atas pendentes</strong> dele são reatribuídas automaticamente
                ao novo diretor. O novo diretor pode editar as avaliações feitas pelo anterior.
            </p>
        </div>
    </div>
</section>

<section class="panel mt-16">
    <div class="panel-header">
        <div>
            <h2>Diretores ativos (<?= count($diretores) ?>)</h2>
        </div>
    </div>

    <?php if (empty($diretores)): ?>
        <p class="text-muted">
            Nenhum diretor ativo para trocar.
            <a href="<?= $basePath ?>/grupos/<?= (int) $grupo['id'] ?>/diretores/promover">Nomeie o primeiro</a>.
        </p>
    <?php else: ?>
        <form method="POST" action="<?= $basePath ?>/grupos/<?= (int) $grupo['id'] ?>/diretores/trocar">
            <?= ViewHelper::csrfField() ?>

            <div class="field-row">
                <div>
                    <label class="field-label">Diretor que sai *</label>
                    <select name="sai_id" class="field" required>
                        <option value="">Selecione</option>
                        <?php foreach ($diretores as $d): ?>
                            <option value="<?= (int) $d['usuario_id'] ?>">
                                <?= h($d['nome']) ?> — <?= h($d['email']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <?php
                $ativosIds  = array_map(fn($d) => (int) $d['usuario_id'], $diretores);
                $candidatos = array_filter($membros, fn($m) => !in_array((int) $m['usuario_id'], $ativosIds, true));
                ?>

                <div>
                    <label class="field-label">Novo diretor (que entra) *</label>
                    <select name="entra_id" class="field" required>
                        <option value="">Selecione</option>
                        <?php foreach ($candidatos as $c): ?>
                            <option value="<?= (int) $c['usuario_id'] ?>">
                                <?= h($c['nome']) ?> — <?= h($c['email']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (empty($candidatos)): ?>
                        <small class="field-help-danger">
                            <i class="fas fa-exclamation-triangle"></i>
                            Nenhum membro disponível. Adicione alguém ao grupo primeiro.
                        </small>
                    <?php endif; ?>
                </div>
            </div>

            <p class="mt-20">
                <button type="submit" class="button button-primary"
                        <?= empty($candidatos) ? 'disabled' : '' ?>
                        onclick="return confirm('Confirmar troca de diretor? Atas pendentes serão reatribuídas.')">
                    <i class="fas fa-right-left"></i> Trocar diretor
                </button>
            </p>
        </form>
    <?php endif; ?>
</section>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>