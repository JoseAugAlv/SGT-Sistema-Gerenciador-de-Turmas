<?php
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';
require_once __DIR__ . '/../../Models/Ata.php';

$u = $_SESSION['usuario'];
?>

<h1><?= h($ata['titulo']) ?></h1>
<p>Projeto: <a href="<?= $basePath ?>/projetos/<?= (int) $ata['projeto_id'] ?>"><?= h($ata['projeto_nome']) ?></a>
   — Grupo: <a href="<?= $basePath ?>/grupos/<?= (int) $ata['grupo_id'] ?>"><?= h($ata['grupo_nome']) ?></a>
</p>

<?php require __DIR__ . '/../projetos/_nav.php'; ?>

<p>
    Data: <strong><?= h($ata['data_ata']) ?></strong>
    <?= $ata['horario_inicio'] ? ' — ' . h($ata['horario_inicio']) : '' ?>
    <?= $ata['horario_fim'] ? ' a ' . h($ata['horario_fim']) : '' ?>
    — Prazo: <?= h($ata['prazo_preenchimento'] ?? '—') ?>
    — Status:
    <strong>
        <?php
        $badge = [
            'pendente'   => '<i class="fas fa-hourglass-half" style="color:#c80"></i> Pendente',
            'preenchida' => '<i class="fas fa-pen" style="color:#06f"></i> Preenchida',
            'revisada'   => '<i class="fas fa-check-circle" style="color:#0a0"></i> Revisada',
        ];
        echo $badge[$ata['status']] ?? h($ata['status']);
        ?>
    </strong>
</p>

<p>
    Diretor designado: <?= h($ata['diretor_nome'] ?? '—') ?>
    — Representante: <?= h($ata['representante_nome'] ?? '—') ?>
</p>

<?php if (!empty($ata['descricao'])): ?>
    <p><?= nl2br(h($ata['descricao'])) ?></p>
<?php endif; ?>

<?php if ($podeValidar && $ata['status'] === 'preenchida'): ?>
    <form method="POST" action="<?= $basePath ?>/atas/<?= (int) $ata['id'] ?>/validar" style="display:inline">
        <?= ViewHelper::csrfField() ?>
        <button type="submit"><i class="fas fa-check"></i> Validar ata</button>
    </form>
<?php endif; ?>

<?php if ($podeExcluir): ?>
    <form method="POST" action="<?= $basePath ?>/atas/<?= (int) $ata['id'] ?>/excluir" style="display:inline">
        <?= ViewHelper::csrfField() ?>
        <button type="submit" style="background:#b00;color:#fff"
                onclick="return confirm('Excluir esta ata?')">
            <i class="fas fa-trash"></i> Excluir
        </button>
    </form>
<?php endif; ?>

<hr>

<h2>Participantes (<?= count($participantes) ?>)</h2>
<?php if (empty($participantes)): ?>
    <p>Nenhum participante registrado.</p>
<?php else: ?>
    <table border="1" cellpadding="6">
        <thead><tr><th>Nome</th><th>Presença</th><th>Justificativa</th></tr></thead>
        <tbody>
            <?php foreach ($participantes as $p): ?>
                <tr>
                    <td><?= h($p['usuario_nome']) ?></td>
                    <td><?= h($p['presente']) ?></td>
                    <td><?= h($p['justificativa'] ?? '—') ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<hr>

<h2>Atividades (<?= count($atividades) ?>)</h2>
<?php if (empty($atividades)): ?>
    <p>Nenhuma atividade registrada.</p>
<?php else: ?>
    <ul>
        <?php foreach ($atividades as $a): ?>
            <li>
                <strong><?= h($a['nome']) ?></strong>
                <?php if (!empty($a['descricao'])): ?>
                    — <?= h($a['descricao']) ?>
                <?php endif; ?>

                <?php if (!empty($a['participantes'])): ?>
                    <br><small>Participantes:
                        <?php foreach ($a['participantes'] as $p): ?>
                            <code><?= h($p['usuario_nome']) ?></code>
                        <?php endforeach; ?>
                    </small>
                <?php endif; ?>

                <?php if ($podePreencher): ?>
                    <form method="POST" action="<?= $basePath ?>/atividades/<?= (int) $a['id'] ?>/excluir" style="display:inline">
                        <?= ViewHelper::csrfField() ?>
                        <button type="submit" onclick="return confirm('Excluir atividade?')">
                            <i class="fas fa-trash"></i>
                        </button>
                    </form>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<?php if ($podePreencher): ?>
    <h3>Adicionar atividade</h3>
    <form method="POST" action="<?= $basePath ?>/atas/<?= (int) $ata['id'] ?>/atividade">
        <?= ViewHelper::csrfField() ?>

        <p><label>Nome *<br>
            <input type="text" name="nome" maxlength="200" required></label></p>

        <p><label>Descrição<br>
            <textarea name="descricao" rows="2" maxlength="2000"></textarea></label></p>

        <fieldset>
            <legend>Participantes da atividade</legend>

            <?php if (!empty($alunosDoGrupo)): ?>
                <p><strong>Alunos do grupo</strong></p>
                <div class="lista-participantes">
                    <?php foreach ($alunosDoGrupo as $a): ?>
                        <label class="participante participante-grupo"
                               data-origem="grupo"
                               style="display:block;padding:10px;border:1px solid #ccc;margin:4px 0;border-radius:6px;cursor:pointer;">
                            <input type="checkbox"
                                   name="participantes[]"
                                   value="<?= (int) $a['id'] ?>"
                                   data-origem="grupo">
                            <?= h($a['nome']) ?>
                            <small>(<?= h($a['email']) ?>)</small>
                        </label>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($alunosOutros)): ?>
                <p><strong>Outros alunos da turma</strong></p>
                <div class="lista-participantes">
                    <?php foreach ($alunosOutros as $a): ?>
                        <label class="participante participante-turma"
                               data-origem="turma"
                               style="display:block;padding:10px;border:1px solid #eee;margin:4px 0;border-radius:6px;cursor:pointer;">
                            <input type="checkbox"
                                   name="participantes[]"
                                   value="<?= (int) $a['id'] ?>"
                                   data-origem="turma">
                            <?= h($a['nome']) ?>
                            <small>(<?= h($a['email']) ?>)</small>
                        </label>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </fieldset>

        <p><button type="submit"><i class="fas fa-plus"></i> Adicionar atividade</button></p>
    </form>
<?php endif; ?>

<hr>

<h2>Relatórios internos (<?= count($relatorios) ?>)</h2>
<?php if (empty($relatorios)): ?>
    <p>Nenhum relatório registrado.</p>
<?php else: ?>
    <ul>
        <?php foreach ($relatorios as $r): ?>
            <li>
                <strong><?= h($r['titulo'] ?? '(sem título)') ?></strong>
                — <small><?= h($r['tipo_relatorio']) ?><?= $r['tema'] ? ' / ' . h(Ata::TEMAS_RELATORIO[$r['tema']] ?? $r['tema']) : '' ?></small>
                <br>
                <em><?= nl2br(h($r['conteudo'])) ?></em>
                <br>
                <small>Por: <?= h($r['autor_nome']) ?></small>
                <?php if (!empty($r['participantes'])): ?>
                    <br><small>Mencionados:
                        <?php foreach ($r['participantes'] as $p): ?>
                            <code><?= h($p['usuario_nome']) ?></code>
                        <?php endforeach; ?>
                    </small>
                <?php endif; ?>

                <?php if ($podePreencher): ?>
                    <form method="POST" action="<?= $basePath ?>/relatorios/<?= (int) $r['id'] ?>/excluir" style="display:inline">
                        <?= ViewHelper::csrfField() ?>
                        <button type="submit" onclick="return confirm('Excluir relatório?')">
                            <i class="fas fa-trash"></i>
                        </button>
                    </form>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<?php if ($podePreencher): ?>
    <h3>Adicionar relatório interno</h3>
    <form method="POST" action="<?= $basePath ?>/atas/<?= (int) $ata['id'] ?>/relatorio">
        <?= ViewHelper::csrfField() ?>

        <p><label>Título<br>
            <input type="text" name="titulo" maxlength="200"></label></p>

        <p><label>Tipo *<br>
            <select name="tipo_relatorio" required>
                <option value="ocorrencia">Ocorrência</option>
                <option value="decisao">Decisão</option>
                <option value="encaminhamento">Encaminhamento</option>
                <option value="observacao">Observação</option>
            </select></label></p>

        <p><label>Tema<br>
            <select name="tema">
                <option value="">(sem tema)</option>
                <?php foreach (Ata::TEMAS_RELATORIO as $chave => $label): ?>
                    <option value="<?= h($chave) ?>"><?= h($label) ?></option>
                <?php endforeach; ?>
            </select></label></p>

        <p><label>Conteúdo *<br>
            <textarea name="conteudo" rows="3" maxlength="3000" required></textarea></label></p>

        <fieldset>
            <legend>Participantes mencionados</legend>

            <?php if (!empty($alunosDoGrupo)): ?>
                <p><strong>Alunos do grupo</strong></p>
                <div class="lista-participantes">
                    <?php foreach ($alunosDoGrupo as $a): ?>
                        <label class="participante participante-grupo"
                               data-origem="grupo"
                               style="display:block;padding:10px;border:1px solid #ccc;margin:4px 0;border-radius:6px;cursor:pointer;">
                            <input type="checkbox"
                                   name="participantes[]"
                                   value="<?= (int) $a['id'] ?>"
                                   data-origem="grupo">
                            <?= h($a['nome']) ?>
                            <small>(<?= h($a['email']) ?>)</small>
                        </label>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($alunosOutros)): ?>
                <p><strong>Outros alunos da turma</strong></p>
                <div class="lista-participantes">
                    <?php foreach ($alunosOutros as $a): ?>
                        <label class="participante participante-turma"
                               data-origem="turma"
                               style="display:block;padding:10px;border:1px solid #eee;margin:4px 0;border-radius:6px;cursor:pointer;">
                            <input type="checkbox"
                                   name="participantes[]"
                                   value="<?= (int) $a['id'] ?>"
                                   data-origem="turma">
                            <?= h($a['nome']) ?>
                            <small>(<?= h($a['email']) ?>)</small>
                        </label>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </fieldset>

        <p><button type="submit"><i class="fas fa-plus"></i> Adicionar relatório</button></p>
    </form>

    <hr>

    <form method="POST" action="<?= $basePath ?>/atas/<?= (int) $ata['id'] ?>/finalizar">
        <?= ViewHelper::csrfField() ?>
        <button type="submit" onclick="return confirm('Finalizar ata? Após finalizada, você não poderá editar até o representante reabrir.')">
            <i class="fas fa-flag-checkered"></i> Finalizar ata
        </button>
    </form>
<?php endif; ?>

<p><a href="<?= $basePath ?>/projetos/<?= (int) $ata['projeto_id'] ?>/atas">
    <i class="fas fa-arrow-left"></i> Voltar às atas
</a></p>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?> 