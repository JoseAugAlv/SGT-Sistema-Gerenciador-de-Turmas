<section class="hero-section">
    <div>
        <div class="eyebrow"><span class="status-dot"></span> Relatório geral</div>
        <h1>Feira de Ciências 2026</h1>
        <p class="hero-subtitle">123-3MOD-INFO-2026 — 22 aluno(s) × 4 critério(s)</p>
    </div>
    <div class="hero-actions">
        <a class="button button-ghost"><i class="fas fa-arrow-left"></i> Voltar</a>
    </div>
</section>

<nav class="proj-nav">
    <a><i class="fas fa-list-ol"></i> Etapas</a>
    <a><i class="fas fa-users"></i> Grupos</a>
    <a><i class="fas fa-book"></i> Atas</a>
    <a><i class="fas fa-clipboard-check"></i> Critérios</a>
    <a><i class="fas fa-star"></i> Avaliações</a>
    <strong><i class="fas fa-file-alt"></i> Relatórios</strong>
</nav>

<div class="relatorio-toolbar">
    <span class="toolbar-label"><i class="fas fa-file-export"></i> Exportar</span>
    <div class="toolbar-actions">
        <a class="button small button-ghost"><i class="fas fa-file-pdf"></i> PDF</a>
        <a class="button small button-ghost"><i class="fas fa-file-excel"></i> Excel</a>
    </div>
</div>

<div class="rel-legenda">
    <strong>Conceitos:</strong>
    <span class="legenda-item"><span class="conceito-badge mb">MB</span> Muito Bom</span>
    <span class="legenda-item"><span class="conceito-badge b">B</span> Bom</span>
    <span class="legenda-item"><span class="conceito-badge r">R</span> Regular</span>
    <span class="legenda-item"><span class="conceito-badge i">I</span> Insuficiente</span>
</div>

<section class="panel">
    <div class="panel-header">
        <div><h2>Matriz de notas</h2><p>Cada linha é um aluno, cada coluna um critério.</p></div>
    </div>

    <div class="rel-wrapper">
        <table class="rel-tabela">
            <thead>
                <tr>
                    <th>Aluno</th>
                    <th><div class="th-criterio"><span>Participação</span><small>peso 3,00</small></div></th>
                    <th><div class="th-criterio"><span>Pesquisa</span><small>peso 3,00</small></div></th>
                    <th><div class="th-criterio"><span>Colaboração</span><small>peso 2,00</small></div></th>
                    <th><div class="th-criterio"><span>Auto</span><small>peso 2,00</small></div></th>
                    <th style="text-align:right;">Média</th>
                    <th style="text-align:center;">Conc.</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $alunos = [
                    ['MO','Maria Oliveira','aluno', 90, 95, 92, 90, 92.10, 'MB'],
                    ['PS','Pedro Santos','representante', 85, 80, 90, 85, 84.50, 'B'],
                    ['JS','João Silva','aluno', 90, 85, 80, 82.5, 85.30, 'MB'],
                    ['AC','Ana Costa','aluno', 78, 75, 80, 82, 78.50, 'B'],
                    ['LC','Lucas Cardoso','aluno', 82, 70, 75, 80, 76.60, 'B'],
                    ['RM','Rafael Mendes','aluno', 88, 92, 85, 90, 88.90, 'MB'],
                ];
                foreach ($alunos as $a):
                    $classeConc = strtolower($a[9]);
                ?>
                    <tr>
                        <td>
                            <div class="td-aluno">
                                <span class="avatar avatar-blue"><?= $a[0] ?></span>
                                <div class="td-aluno-info">
                                    <strong><?= $a[1] ?></strong>
                                    <small><?= $a[2] ?></small>
                                </div>
                            </div>
                        </td>
                        <td class="td-num"><?= number_format($a[3], 2, ',', '.') ?></td>
                        <td class="td-num"><?= number_format($a[4], 2, ',', '.') ?></td>
                        <td class="td-num"><?= number_format($a[5], 2, ',', '.') ?></td>
                        <td class="td-num"><?= number_format($a[6], 2, ',', '.') ?></td>
                        <td class="td-num"><strong style="color:var(--primary); font-size:13.5px;"><?= number_format($a[7], 2, ',', '.') ?></strong></td>
                        <td class="td-center"><span class="conceito-badge <?= $classeConc ?>"><?= $a[9] ?></span></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<section class="panel">
    <div class="panel-header">
        <div><h2><i class="fas fa-trophy" style="color:#d48b1c;"></i> Ranking por média</h2><p>Ordenado da maior para a menor média.</p></div>
    </div>

    <div class="ranking-lista">
        <?php
        $ranking = [
            ['MO','Maria Oliveira','aluno', 92.10, 'MB'],
            ['RM','Rafael Mendes','aluno', 88.90, 'MB'],
            ['JS','João Silva','aluno', 85.30, 'MB'],
            ['PS','Pedro Santos','representante', 84.50, 'B'],
            ['AC','Ana Costa','aluno', 78.50, 'B'],
            ['LC','Lucas Cardoso','aluno', 76.60, 'B'],
        ];
        foreach ($ranking as $i => $r):
            $classeConc = strtolower($r[4]);
        ?>
            <div class="ranking-item">
                <span class="ranking-pos"><?= $i + 1 ?>º</span>
                <div class="ranking-info"><strong><?= $r[1] ?></strong><small><?= $r[2] ?></small></div>
                <span class="ranking-media"><?= number_format($r[3], 2, ',', '.') ?></span>
                <span class="conceito-badge <?= $classeConc ?>"><?= $r[4] ?></span>
            </div>
        <?php endforeach; ?>
    </div>
</section>