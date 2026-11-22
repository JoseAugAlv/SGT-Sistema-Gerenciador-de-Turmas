<section class="hero-section">
    <div>
        <div class="eyebrow"><span class="status-dot"></span> Avaliação em massa</div>
        <h1>Feira de Ciências 2026</h1>
        <p class="hero-subtitle">22 aluno(s) — 4 critério(s) aberto(s)</p>
    </div>
</section>

<nav class="proj-nav">
    <a><i class="fas fa-list-ol"></i> Etapas</a>
    <a><i class="fas fa-users"></i> Grupos</a>
    <a><i class="fas fa-book"></i> Atas</a>
    <a><i class="fas fa-clipboard-check"></i> Critérios</a>
    <strong><i class="fas fa-star"></i> Avaliações</strong>
    <a><i class="fas fa-file-alt"></i> Relatórios</a>
</nav>

<nav class="aval-nav">
    <strong><i class="fas fa-user-tie"></i> Representante</strong>
    <a><i class="fas fa-users-rectangle"></i> Coletiva</a>
    <a><i class="fas fa-graduation-cap"></i> Minhas Notas</a>
</nav>

<div class="aval-quick">
    <div class="aval-quick-linha">
        <strong>Conceito</strong>
        <button type="button" class="aval-quick-btn">MB</button>
        <button type="button" class="aval-quick-btn">B</button>
        <button type="button" class="aval-quick-btn">R</button>
        <button type="button" class="aval-quick-btn">I</button>
        <button type="button" class="aval-quick-btn limpar"><i class="fas fa-eraser"></i> Limpar</button>
    </div>
    <div class="aval-quick-linha">
        <strong>Valor</strong>
        <button type="button" class="aval-quick-btn">100</button>
        <button type="button" class="aval-quick-btn">95</button>
        <button type="button" class="aval-quick-btn">90</button>
        <button type="button" class="aval-quick-btn">85</button>
        <button type="button" class="aval-quick-btn">80</button>
        <button type="button" class="aval-quick-btn">70</button>
        <button type="button" class="aval-quick-btn">60</button>
        <button type="button" class="aval-quick-btn">50</button>
        <button type="button" class="aval-quick-btn">25</button>
        <button type="button" class="aval-quick-btn">0</button>
    </div>
</div>

<div class="aval-grade-wrap">
    <table class="aval-grade">
        <thead>
            <tr>
                <th class="th-aluno">Aluno</th>
                <th class="th-todos">Participação<small>peso 3,00</small></th>
                <th class="th-diretores">Pesquisa<small>apenas diretores — peso 3,00</small></th>
                <th class="th-reps">Colaboração<small>apenas representantes — peso 2,00</small></th>
                <th class="th-todos">Autoavaliação<small>peso 2,00</small></th>
            </tr>
        </thead>
        <tbody>
            <?php
            $alunos = [
                ['JS','João Silva','aluno','MB','B','MB','B'],
                ['AC','Ana Costa','aluno','MB','MB','B','B'],
                ['PS','Pedro Santos','representante','B','B','MB','MB'],
                ['MO','Maria Oliveira','aluno','MB','B','MB','B'],
                ['LC','Lucas Cardoso','aluno','B','R','B','MB'],
                ['RM','Rafael Mendes','aluno','MB','MB','B','B'],
            ];
            foreach ($alunos as $a): ?>
                <tr>
                    <td class="td-aluno">
                        <span class="aval-aluno">
                            <span class="avatar avatar-blue"><?= $a[0] ?></span>
                            <span><?= $a[1] ?><small><?= $a[2] ?></small></span>
                        </span>
                    </td>
                    <td><div class="aval-celula"><span class="aval-select"><?= $a[3] ?></span></div></td>
                    <td><div class="aval-celula"><span class="aval-select"><?= $a[4] ?></span></div></td>
                    <td><div class="aval-celula"><span class="aval-select"><?= $a[5] ?></span></div></td>
                    <td><div class="aval-celula"><span class="aval-select"><?= $a[6] ?></span></div></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<p class="mt-16"><button class="button button-primary"><i class="fas fa-save"></i> Salvar avaliações</button></p>