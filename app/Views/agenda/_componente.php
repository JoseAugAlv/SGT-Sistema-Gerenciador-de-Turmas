<?php
// app/Views/agenda/_componente.php
// Agenda interativa (public/js/agenda.js). Espera: $ctx (AgendaController::contexto()), opcional $modo ('completo'|'mini'),
// $soTurma (id da turma para filtrar e sugerir), $agId (id único do elemento).
require_once __DIR__ . '/../../Models/Agenda.php';
require_once __DIR__ . '/../../Middleware/CsrfMiddleware.php';
$modo    = $modo ?? 'completo';
$agId    = $agId ?? 'agenda';
$soTurma = $soTurma ?? null;
$turmasDia = [];
foreach ($ctx['turmasDia'] as $t) $turmasDia[$t['id']] = ['id' => $t['id'], 'nome' => $t['nome']];
$editaveis = array_map(fn($t) => ['id' => $t['id'], 'nome' => $t['nome']], $ctx['turmasEditaveis']);
$config = [
    'modo' => $modo, 'base' => $basePath, 'csrf' => CsrfMiddleware::generateToken(),
    'turmasDia' => (object) $turmasDia, 'turmasEditaveis' => $editaveis,
    'podeCriar' => $ctx['podePessoal'] || (bool) $editaveis, 'soTurma' => $soTurma,
];
if (!$ctx['podePessoal'] && !$ctx['turmasDia']):
    // Plano sem Dia a dia: mostra o que é e como liberar
    $principal = array_values(array_filter($ctx['turmas'], fn($t) => $t['principal'] && !$t['tem_dia']));
?>
    <div class="ag-bloqueio">
        <i class="fas fa-calendar-days" aria-hidden="true"></i>
        <div>
            <h3>Agenda da turma: provas, entregas, tarefas e reuniões num só lugar</h3>
            <p>Faz parte do <strong>Dia a dia</strong>, incluso a partir do plano Anual (ou como adicional nos planos menores). Lembretes na véspera, prioridades com cores e agenda pessoal para cada aluno.</p>
            <?php if ($principal): ?>
                <a class="button button-primary" href="<?= $basePath ?>/turmas/<?= (int) $principal[0]['id'] ?>/plano#dia-a-dia"><i class="fas fa-unlock"></i> Liberar o Dia a dia</a>
            <?php elseif ($ctx['turmas']): ?>
                <small class="txt-mudo">Peça ao representante da sua turma para liberar.</small>
            <?php endif; ?>
        </div>
    </div>
<?php return; endif; ?>

<link rel="stylesheet" href="<?= ViewHelper::asset('css/agenda.css') ?>">
<div class="ag" id="<?= h($agId) ?>" data-config="<?= h(json_encode($config, JSON_UNESCAPED_UNICODE)) ?>">
    <div class="ag-vazio"><i class="fas fa-spinner fa-spin"></i>Carregando agenda…</div>
</div>

<!-- Formulário de evento -->
<dialog class="ag-dialogo" id="<?= h($agId) ?>-form" aria-labelledby="<?= h($agId) ?>-form-tit">
    <form class="ag-form" novalidate data-sem-carregando>
        <input type="hidden" name="id">
        <div class="ag-dl-cab">
            <h2 id="<?= h($agId) ?>-form-tit">Novo evento</h2>
            <button type="button" class="ag-btn ag-btn-icone" data-fechar aria-label="Fechar"><i class="fas fa-xmark"></i></button>
        </div>
        <div class="ag-dl-corpo">
            <p class="ag-erro" hidden></p>
            <div class="ag-campo">
                <span>Tipo</span>
                <div class="ag-chips" role="radiogroup" aria-label="Tipo">
                    <?php foreach (Agenda::TIPOS as $k => [$rot, $ico]): if ($k === 'reuniao') continue; ?>
                        <label class="ag-chip-op"><input type="radio" name="tipo" value="<?= $k ?>"><span><i class="fas <?= $ico ?>"></i><?= $rot ?></span></label>
                    <?php endforeach; ?>
                </div>
            </div>
            <label class="ag-campo"><span>Título</span><input type="text" name="titulo" class="ag-titulo-input" maxlength="150" required placeholder="Ex.: Prova de matemática — capítulos 3 e 4"></label>
            <div class="ag-linha">
                <label class="ag-campo"><span>Data</span><input type="date" name="data" required></label>
                <label class="ag-campo" data-hora><span>Início</span><input type="time" name="inicio"></label>
                <label class="ag-campo" data-hora><span>Fim</span><input type="time" name="fim"></label>
            </div>
            <div class="ag-alternar">
                <span>Dia inteiro</span>
                <span class="ag-switch"><input type="checkbox" name="dia_inteiro" value="1" aria-label="Dia inteiro"><i></i></span>
            </div>
            <div class="ag-campo">
                <span>Prioridade</span>
                <div class="ag-chips ag-prio" role="radiogroup" aria-label="Prioridade">
                    <?php foreach (Agenda::PRIORIDADES as $k => [$rot]): ?>
                        <label class="ag-chip-op p-<?= $k ?>"><input type="radio" name="prioridade" value="<?= $k ?>"><span><?= $rot ?></span></label>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php if ($editaveis): ?>
                <div class="ag-campo">
                    <span>Quem vê</span>
                    <div class="ag-chips" role="radiogroup" aria-label="Quem vê">
                        <label class="ag-chip-op"><input type="radio" name="visibilidade" value="turma"><span><i class="fas fa-users"></i>Toda a turma</span></label>
                        <?php if ($ctx['podePessoal']): ?><label class="ag-chip-op"><input type="radio" name="visibilidade" value="pessoal"><span><i class="fas fa-lock"></i>Só eu</span></label><?php endif; ?>
                    </div>
                </div>
            <?php else: ?>
                <input type="hidden" name="visibilidade" value="pessoal">
                <p class="ag-sub" style="margin:0;font-size:13px;color:var(--muted)"><i class="fas fa-lock"></i> Este evento fica só na sua agenda.</p>
            <?php endif; ?>
            <label class="ag-campo"><span data-turma-rot>Turma</span><select name="turma_id"></select></label>
            <p data-aviso-turma hidden style="margin:-6px 0 0;font-size:12px;color:var(--muted)"><i class="fas fa-bell"></i> A turma recebe uma notificação.</p>
            <label class="ag-campo"><span>Local</span><input type="text" name="local" maxlength="120" placeholder="Opcional: sala, laboratório, link…"></label>
            <label class="ag-campo"><span>Detalhes</span><textarea name="descricao" rows="3" maxlength="3000" placeholder="Opcional: conteúdo, materiais, o que levar…"></textarea></label>
            <div class="ag-alternar">
                <span>Lembrete na véspera<small>Notificação (e email, conforme sua preferência) um dia antes</small></span>
                <span class="ag-switch"><input type="checkbox" name="lembrete" value="1" checked aria-label="Lembrete na véspera"><i></i></span>
            </div>
            <div class="ag-dl-acoes">
                <button type="button" class="ag-btn" data-fechar>Cancelar</button>
                <button type="submit" class="ag-btn ag-btn-primario"><i class="fas fa-check"></i> Salvar</button>
            </div>
        </div>
    </form>
</dialog>

<!-- Detalhe do evento -->
<dialog class="ag-dialogo ag-det" id="<?= h($agId) ?>-det" aria-label="Detalhes do evento"></dialog>

<script src="<?= ViewHelper::asset('js/agenda.js') ?>"></script>
