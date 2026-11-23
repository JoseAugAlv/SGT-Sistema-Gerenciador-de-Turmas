<?php
// app/Views/calendario/index.php
// Calendário do mês, próximas datas e horário semanal das aulas da turma/grêmio.
$cssPagina = 'projetos.css';
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';
require_once __DIR__ . '/../../Models/Calendario.php';

$tid   = (int) $turma['id'];
$base  = $basePath . '/turmas/' . $tid . '/calendario';
$ini   = strtotime($mes . '-01');
$nomesMes = ['', 'Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
$diasNoMes = (int) date('t', $ini);
$primeiroDia = (int) date('N', $ini); // 1 = segunda
$hoje = date('Y-m-d');
$diaHoje = (int) date('N');
$ehGremio = ($turma['tipo'] ?? 'turma') === 'gremio';
$hora = fn($t) => $t ? substr($t, 0, 5) : '';
?>

<section class="hero-section">
    <div>
        <div class="eyebrow"><span class="status-dot"></span> Calendário</div>
        <h1>Calendário</h1>
        <p class="hero-subtitle"><?= $ehGremio ? 'Grêmio' : 'Turma' ?> <strong><?= h($turma['nome']) ?></strong> · entregas, provas, eventos, reuniões e o horário das aulas.</p>
    </div>
    <div class="hero-actions">
        <a class="button button-ghost" href="<?= $basePath ?>/turmas/<?= $tid ?>"><i class="fas fa-arrow-left"></i> Voltar</a>
        
    </div>
</section>

<?php if (!$temDia): ?>
    <div class="ag-bloqueio" style="display:grid;grid-template-columns:auto 1fr;gap:16px;align-items:center;padding:18px;border-radius:12px;border:1px dashed var(--border)">
        <i class="fas fa-lock" style="font-size:28px;color:var(--primary)"></i>
        <div><h3 style="margin:0 0 4px">Agenda e horário fazem parte do Dia a dia</h3>
            <p style="margin:0 0 10px;color:var(--muted)">Incluso a partir do plano Anual. Nos planos Trimestral e Semestral dá para liberar como adicional.</p>
            <?php if ($souPrincipal): ?><a class="button button-primary" href="<?= $basePath ?>/turmas/<?= $tid ?>/plano#dia-a-dia"><i class="fas fa-unlock"></i> Liberar o Dia a dia</a>
            <?php else: ?><small class="txt-mudo">Peça ao representante para liberar.</small><?php endif; ?></div>
    </div>
<?php else: ?>
    <?php $modo = 'completo'; $agId = 'agenda-turma'; $soTurma = $tid; require __DIR__ . '/../agenda/_componente.php'; ?>
<?php endif; ?>

<!-- ============ HORÁRIO DAS AULAS ============ -->
<?php if ($temDia): ?>
<section class="panel mt-16" id="horario">
    <div class="cal-topo cal-topo-esq"><h2><i class="fas fa-clock"></i> Horário das aulas</h2></div>
    <?php
    $diasComAula = array_keys($horarios);
    $diasMostrar = array_values(array_unique(array_merge([1, 2, 3, 4, 5], $diasComAula)));
    sort($diasMostrar);
    ?>
    <div class="horario-grade" style="--colunas: <?= count($diasMostrar) ?>">
        <?php foreach ($diasMostrar as $dia): ?>
            <div class="horario-dia<?= $dia === $diaHoje ? ' hoje' : '' ?>">
                <h3><?= Calendario::DIAS[$dia] ?><?= $dia === $diaHoje ? ' <small>hoje</small>' : '' ?></h3>
                <?php if (empty($horarios[$dia])): ?><p class="txt-mudo">Sem aulas</p><?php endif; ?>
                <?php foreach ($horarios[$dia] ?? [] as $h): ?>
                    <div class="horario-aula">
                        <span class="num"><?= $hora($h['inicio']) ?>–<?= $hora($h['fim']) ?></span>
                        <strong><?= h($h['disciplina']) ?></strong>
                        <small><?= h(implode(' · ', array_filter([$h['professor'], $h['sala']]))) ?></small>
                        <?php if ($podeEditar): ?>
                            <form method="POST" action="<?= $basePath ?>/calendario/horarios/<?= (int) $h['id'] ?>/excluir">
                                <?= ViewHelper::csrfField() ?>
                                <button type="submit" class="horario-x" aria-label="Remover aula" onclick="return confirm('Remover esta aula do horário?')"><i class="fas fa-xmark"></i></button>
                            </form>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
    </div>
    <?php if ($podeEditar): ?>
        <details class="mt-16">
            <summary class="button button-secondary"><i class="fas fa-plus"></i> Adicionar aula</summary>
            <form method="POST" action="<?= $base ?>/horarios" class="cal-form cal-form-larga">
                <?= ViewHelper::csrfField() ?>
                <fieldset class="horario-dias-escolha"><legend>Dias *</legend>
                    <?php foreach (Calendario::DIAS as $n => $nome): ?><label><input type="checkbox" name="dias[]" value="<?= $n ?>"> <?= mb_substr($nome, 0, 3) ?></label><?php endforeach; ?>
                </fieldset>
                <label>Disciplina *<input type="text" name="disciplina" class="field" maxlength="100" required placeholder="Ex.: Matemática"></label>
                <div class="cal-form-linha">
                    <label>Início *<input type="time" name="inicio" class="field" required></label>
                    <label>Fim *<input type="time" name="fim" class="field" required></label>
                </div>
                <label>Professor<input type="text" name="professor" class="field" maxlength="100"></label>
                <label>Sala<input type="text" name="sala" class="field" maxlength="50"></label>
                <button type="submit" class="button button-primary">Adicionar ao horário</button>
            </form>
        </details>
    <?php endif; ?>
</section>

<?php endif; ?>

<style>
.cal-grade { display: grid; grid-template-columns: minmax(0, 1.6fr) minmax(0, 1fr); gap: 16px; align-items: start; }
.cal-grade .panel { margin: 0; }
.an-h2 { margin: 0 0 12px; font-size: 16px; }
.cal-topo { display: flex; align-items: center; justify-content: space-between; gap: 8px; margin-bottom: 10px; }
.cal-topo h2 { margin: 0; font-size: 18px; } .cal-topo-esq { justify-content: flex-start; } .cal-topo h2 i { color: var(--primary); }
.cal-semana, .cal-dias { display: grid; grid-template-columns: repeat(7, minmax(0, 1fr)); gap: 4px; }
.cal-semana span { font-size: 11px; font-weight: 700; color: var(--muted); text-transform: uppercase; text-align: center; padding: 4px 0; }
.cal-dia { min-height: 86px; padding: 4px; border: 1px solid var(--border); border-radius: 8px; display: flex; flex-direction: column; gap: 2px; min-width: 0; background: var(--surface); }
.cal-dia.vazio { border: 0; background: none; }
.cal-dia.hoje { border-color: var(--primary); box-shadow: inset 0 0 0 1px var(--primary); }
.cal-num { font-size: 12px; font-weight: 700; color: var(--muted); } .cal-dia.hoje .cal-num { color: var(--primary); }
.cal-item { font-size: 11px; line-height: 1.3; padding: 1px 5px; border-radius: 4px; color: #fff; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.cal-mais { font-size: 11px; color: var(--muted); }
.t-entrega { background: #1c6fd6; } .t-prova { background: #d63548; } .t-evento { background: #29946e; }
.t-reuniao { background: #7a4fd6; } .t-feriado { background: #8a8f98; } .t-outro { background: #d48b1c; }
.cal-legenda { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 10px; font-size: 12px; color: var(--muted); }
.cal-legenda i { display: inline-block; width: 10px; height: 10px; border-radius: 3px; vertical-align: middle; }
.cal-lista { list-style: none; margin: 0; padding: 0; display: grid; gap: 10px; }
.cal-lista li { display: grid; grid-template-columns: auto 1fr auto; gap: 10px; align-items: start; }
.cal-lista strong { display: block; } .cal-lista small { display: block; color: var(--muted); font-size: 12px; }
.cal-desc { margin-top: 2px; }
.cal-data { display: grid; justify-items: center; width: 44px; padding: 4px 0; border-radius: 8px; color: #fff; line-height: 1.1; }
.cal-data b { font-size: 18px; } .cal-data small { color: #fff !important; text-transform: uppercase; font-size: 10px !important; }
.cal-form { display: grid; gap: 10px; margin-top: 12px; }
.cal-form label { display: grid; gap: 4px; font-size: 13px; font-weight: 600; }
.cal-form .field { margin: 0; }
.cal-form-linha { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
.cal-form-larga { max-width: 560px; }
.horario-dias-escolha { display: flex; flex-wrap: wrap; gap: 6px 14px; border: 0; padding: 0; margin: 0; }
.horario-dias-escolha legend { font-size: 13px; font-weight: 600; margin-bottom: 4px; padding: 0; }
.horario-dias-escolha label { display: inline-flex; gap: 4px; font-weight: 400; }
.horario-grade { display: grid; grid-template-columns: repeat(var(--colunas), minmax(140px, 1fr)); gap: 8px; overflow-x: auto; }
.horario-dia { display: grid; align-content: start; gap: 6px; padding: 8px; border-radius: 10px; background: var(--surface-2); min-width: 140px; }
.horario-dia.hoje { box-shadow: inset 0 0 0 2px var(--primary); }
.horario-dia h3 { margin: 0 0 2px; font-size: 14px; } .horario-dia h3 small { color: var(--primary); font-size: 11px; text-transform: uppercase; }
.horario-dia .txt-mudo { margin: 0; font-size: 12px; }
.horario-aula { position: relative; display: grid; gap: 1px; padding: 8px 26px 8px 10px; border-radius: 8px; background: var(--surface); border-left: 3px solid var(--primary); }
.horario-aula .num { font-size: 12px; color: var(--muted); } .horario-aula small { color: var(--muted); font-size: 12px; }
.horario-x { position: absolute; top: 4px; right: 4px; border: 0; background: none; color: var(--muted); cursor: pointer; padding: 4px; }
.horario-x:hover { color: var(--red); }
details > summary.button { list-style: none; display: inline-flex; } details > summary.button::-webkit-details-marker { display: none; }
@media (max-width: 900px) { .cal-grade { grid-template-columns: 1fr; } }
@media (max-width: 560px) { .cal-dia { min-height: 54px; } .cal-item { font-size: 0; height: 6px; padding: 0; } .cal-mais { font-size: 10px; } }
</style>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
