<?php
$cssPagina = ['projetos.css', 'atas.css'];

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';
?>

<section class="hero-section">
    <div>
        <div class="eyebrow">
            <span class="status-dot"></span>
            Editar ata
        </div>
        <h1><?= h($ata['titulo']) ?></h1>
        <p class="hero-subtitle">
            Grupo atual: <strong><?= h($ata['grupo_nome']) ?></strong>
            — Status: <strong><?= h($ata['status']) ?></strong>
        </p>
    </div>
    <div class="hero-actions">
        <a class="button button-ghost" href="<?= $basePath ?>/atas/<?= (int) $ata['id'] ?>">
            <i class="fas fa-arrow-left"></i> Voltar
        </a>
    </div>
</section>

<?php require __DIR__ . '/../projetos/_nav.php'; ?>

<?php if (!empty($temConteudo) && ($ata['status'] ?? '') === 'preenchida'): ?>
    <div class="ata-replicar-info">
        <i class="fas fa-exclamation-triangle"></i>
        <div>
            <strong>Esta ata já possui atividades/relatórios</strong>
            <p class="mb-0">
                Replicá-la para outros grupos <strong>não copia</strong> o conteúdo —
                só o cabeçalho (título, descrição, datas).
            </p>
        </div>
    </div>
<?php endif; ?>

<form method="POST" action="<?= $basePath ?>/atas/<?= (int) $ata['id'] ?>/atualizar" class="panel">
    <?= ViewHelper::csrfField() ?>

    <label class="field-label">Título *</label>
    <input type="text" name="titulo" class="field" maxlength="200"
           value="<?= h($ata['titulo']) ?>" required autofocus>

    <label class="field-label">Descrição</label>
    <textarea name="descricao" class="field" rows="3" maxlength="2000"><?= h($ata['descricao'] ?? '') ?></textarea>

    <div class="field-row">
        <div>
            <label class="field-label">Data da ata *</label>
            <input type="date" name="data_ata" class="field" value="<?= h($ata['data_ata']) ?>" required>
        </div>
        <div>
            <label class="field-label">Prazo de preenchimento</label>
            <input type="date" name="prazo_preenchimento" class="field"
                   value="<?= h($ata['prazo_preenchimento'] ?? '') ?>">
        </div>
    </div>

    <div class="field-row">
        <div>
            <label class="field-label">Horário início</label>
            <input type="time" name="horario_inicio" class="field"
                   value="<?= h($ata['horario_inicio'] ?? '') ?>">
        </div>
        <div>
            <label class="field-label">Horário fim</label>
            <input type="time" name="horario_fim" class="field"
                   value="<?= h($ata['horario_fim'] ?? '') ?>">
        </div>
    </div>

    <fieldset class="mt-16">
        <legend>Replicar para outros grupos (opcional)</legend>

        <p class="text-muted mb-12" style="font-size:12px;">
            <i class="fas fa-info-circle"></i>
            Marque os grupos que devem receber <strong>uma cópia desta mesma ata</strong>
            (mesmo título, descrição e datas). Cada cópia fica vinculada ao diretor ativo do grupo.
        </p>

        <?php if (empty($grupos)): ?>
            <p class="text-muted">Nenhum grupo disponível.</p>
        <?php else: ?>
            <table class="ata-replicar-tabela">
                <thead>
                    <tr>
                        <th style="width:36px;"></th>
                        <th>Grupo</th>
                        <th>Diretores ativos</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($grupos as $g): ?>
                        <?php
                        $ehAtual    = (int) $g['id'] === (int) $ata['grupo_id'];
                        $temDiretor = (int) $g['total_diretores'] > 0;
                        ?>
                        <tr>
                            <td>
                                <?php if ($ehAtual): ?>
                                    <i class="fas fa-lock text-muted" title="Grupo atual"></i>
                                <?php else: ?>
                                    <input type="checkbox"
                                           name="grupos_extra[]"
                                           value="<?= (int) $g['id'] ?>"
                                           <?= $temDiretor ? '' : 'disabled' ?>>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong><?= h($g['nome']) ?></strong>
                                <?php if ($ehAtual): ?>
                                    <small class="text-muted"> (grupo atual)</small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($temDiretor): ?>
                                    <span class="badge badge-green">
                                        <i class="fas fa-check"></i> <?= (int) $g['total_diretores'] ?>
                                    </span>
                                <?php else: ?>
                                    <span class="badge badge-red">
                                        <i class="fas fa-ban"></i> Sem diretor
                                    </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </fieldset>

    <?php $modoPreenchimento = $ata['preenchimento'] ?? 'diretor'; require __DIR__ . '/_preenchimento.php'; ?>

    <p class="mt-20">
        <button type="submit" class="button button-primary">
            <i class="fas fa-check"></i> Salvar
        </button>
        <a class="button button-ghost" href="<?= $basePath ?>/atas/<?= (int) $ata['id'] ?>">
            Cancelar
        </a>
    </p>
</form>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>