<?php
// app/Views/projetos/_como_funciona.php
// "Como funciona este projeto": regras visíveis para todos (alunos inclusive) na página inicial do projeto.
// Espera: $projeto, $cfg (configuração de conceito), $representantes (TurmaUsuario::listarRepresentantes).
require_once __DIR__ . '/../../Helpers/EscalaNota.php';
$esq   = EscalaNota::esquema($cfg);
$temVice = (bool) array_filter($representantes, fn($r) => !empty($r['vice']));
$nomes = array_map(fn($r) => h($r['nome']) . (!empty($r['vice']) ? ' <small>(vice)</small>' : ''), $representantes);
?>
<section class="panel mb-16 como-funciona">
    <div class="panel-header"><div><h2><i class="fas fa-circle-info"></i> Como funciona este projeto</h2></div></div>
    <dl class="cf-lista">
        <div>
            <dt><i class="fas fa-award"></i> Notas</dt>
            <dd>
                <?php if ($esq === 'mencao'): ?>
                    Menções I, R, B e MB.
                    <span class="cf-faixas">
                        <span class="cf-i">I até <?= (int) $cfg['limite_i_max'] ?></span>
                        <span class="cf-r">R <?= (int) $cfg['limite_r_min'] ?>–<?= (int) $cfg['limite_r_max'] ?></span>
                        <span class="cf-b">B <?= (int) $cfg['limite_b_min'] ?>–<?= (int) $cfg['limite_b_max'] ?></span>
                        <span class="cf-mb">MB <?= (int) $cfg['limite_mb_min'] ?>+</span>
                    </span>
                    <small>A média dos critérios vai de 0 a 100 e vira menção por essas faixas.</small>
                <?php else: ?>
                    Nota de 0 a <?= EscalaNota::maximo($cfg) ?>. A nota final é a média ponderada pelos pesos dos critérios.
                <?php endif; ?>
            </dd>
        </div>
        <div>
            <dt><i class="fas fa-user-tie"></i> Quem avalia pelos representantes</dt>
            <dd>
                <?= $nomes ? implode(', ', $nomes) : 'Ainda sem representante.' ?>
                <?php if ($temVice || count($representantes) > 1): ?>
                    <small><?= ($projeto['avaliacao_reps'] ?? 'media') === 'compartilhada'
                        ? 'Os dois dividem as mesmas notas: vale a última lançada por qualquer um deles.'
                        : 'Cada um dá a sua nota e a nota final é a média das duas.' ?></small>
                <?php endif; ?>
            </dd>
        </div>
        <div>
            <dt><i class="fas fa-users"></i> Grupos</dt>
            <dd><?= !empty($projeto['alunos_pedem_grupo']) ? 'Você pede para entrar num grupo e o representante aprova.' : 'O representante monta os grupos.' ?></dd>
        </div>
        <div>
            <dt><i class="fas fa-crown"></i> Diretores</dt>
            <dd><?= !empty($projeto['alunos_pedem_diretor']) ? 'Membros podem pedir para ser diretor.' : 'O representante escolhe os diretores.' ?>
                Até <?= (int) ($projeto['diretores_por_grupo'] ?? 1) ?> por grupo.</dd>
        </div>
    </dl>
</section>
<style>
.cf-lista { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px 24px; margin: 0; }
.cf-lista dt { font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; color: var(--muted); margin-bottom: 4px; }
.cf-lista dt i { color: var(--primary); margin-right: 4px; }
.cf-lista dd { margin: 0; line-height: 1.5; }
.cf-lista dd small { display: block; color: var(--muted); font-size: 13px; }
.cf-faixas { display: flex; flex-wrap: wrap; gap: 6px; margin: 6px 0 4px; }
.cf-faixas span { padding: 2px 8px; border-radius: 999px; font-size: 12px; font-weight: 700; color: #fff; }
.cf-i { background: #d63548; } .cf-r { background: #d48b1c; } .cf-b { background: #1c6fd6; } .cf-mb { background: #29946e; }
.como-funciona h2 .fa-circle-info { color: var(--primary); }
@media (max-width: 640px) { .cf-lista { grid-template-columns: 1fr; } }
</style>
