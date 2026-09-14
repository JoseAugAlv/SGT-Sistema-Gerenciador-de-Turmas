<?php
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';
?>

<h1><?= h($projeto['nome']) ?></h1>
<p>Turma: <a href="<?= $basePath ?>/turmas/<?= (int) $projeto['turma_id'] ?>"><?= h($projeto['turma_nome']) ?></a></p>

<?php require __DIR__ . '/../projetos/_nav.php'; ?>

<h2>Editar Ata</h2>

<p>
    Grupo atual: <strong><?= h($ata['grupo_nome']) ?></strong>
    — Status: <strong><?= h($ata['status']) ?></strong>
</p>

<?php if (!empty($temConteudo) && ($ata['status'] ?? '') === 'preenchida'): ?>
    <p style="background:#fef3c7;padding:8px 12px;border-radius:4px;border-left:4px solid #c80;">
        <i class="fas fa-exclamation-triangle"></i>
        Esta ata já possui atividades/relatórios registrados.
        Replicá-la para outros grupos <strong>não copia</strong> o conteúdo — só o cabeçalho (título, descrição, datas).
    </p>
<?php endif; ?>

<form method="POST" action="<?= $basePath ?>/atas/<?= (int) $ata['id'] ?>/atualizar">
    <?= ViewHelper::csrfField() ?>

    <p><label>Título *<br>
        <input type="text" name="titulo" maxlength="200"
               value="<?= h($ata['titulo']) ?>" required autofocus></label></p>

    <p><label>Descrição<br>
        <textarea name="descricao" rows="3" maxlength="2000"><?= h($ata['descricao'] ?? '') ?></textarea></label></p>

    <p><label>Data da ata *<br>
        <input type="date" name="data_ata" value="<?= h($ata['data_ata']) ?>" required></label></p>

    <p><label>Prazo de preenchimento<br>
        <input type="date" name="prazo_preenchimento"
               value="<?= h($ata['prazo_preenchimento'] ?? '') ?>"></label></p>

    <p><label>Horário início<br>
        <input type="time" name="horario_inicio"
               value="<?= h($ata['horario_inicio'] ?? '') ?>"></label></p>

    <p><label>Horário fim<br>
        <input type="time" name="horario_fim"
               value="<?= h($ata['horario_fim'] ?? '') ?>"></label></p>

    <fieldset>
        <legend>Replicar para outros grupos (opcional)</legend>

        <p><small>
            Marque os grupos que devem receber <strong>uma cópia desta mesma ata</strong>
            (mesmo título, descrição e datas). Cada cópia fica vinculada ao diretor ativo do grupo.
        </small></p>

        <?php if (empty($grupos)): ?>
            <p>Nenhum grupo disponível.</p>
        <?php else: ?>
            <table border="1" cellpadding="6">
                <thead>
                    <tr>
                        <th style="width:30px;"></th>
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
                                    <i class="fas fa-lock" title="Grupo atual"></i>
                                <?php else: ?>
                                    <input type="checkbox"
                                           name="grupos_extra[]"
                                           value="<?= (int) $g['id'] ?>"
                                           <?= $temDiretor ? '' : 'disabled' ?>>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?= h($g['nome']) ?>
                                <?php if ($ehAtual): ?>
                                    <small>(grupo atual — não precisa replicar)</small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($temDiretor): ?>
                                    <i class="fas fa-check-circle" style="color:#0a0"></i>
                                    <?= (int) $g['total_diretores'] ?>
                                <?php else: ?>
                                    <i class="fas fa-ban" style="color:#b00"></i>
                                    <small>Sem diretor — não pode receber</small>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </fieldset>

    <p>
        <button type="submit"><i class="fas fa-check"></i> Salvar</button>
        <a href="<?= $basePath ?>/atas/<?= (int) $ata['id'] ?>">Cancelar</a>
    </p>
</form>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>