<section class="hero-section">
    <div>
        <div class="eyebrow"><span class="status-dot"></span> Avaliação por pares</div>
        <h1>Feira de Ciências 2026</h1>
        <p class="hero-subtitle">123-3MOD-INFO-2026</p>
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
    <strong><i class="fas fa-users"></i> Pares</strong>
    <a><i class="fas fa-user-circle"></i> Autoavaliação</a>
    <a><i class="fas fa-graduation-cap"></i> Minhas Notas</a>
</nav>

<div class="aval-bloqueado" style="background:rgba(60,142,234,.1); color:var(--blue); border-left-color:var(--blue);">
    <i class="fas fa-circle-info"></i>
    <div>
        <strong>Justificativa é obrigatória</strong>
        <p class="mb-0">Sua justificativa é interna — só o representante e o diretor do grupo conseguem ver.</p>
    </div>
</div>

<div class="aval-grade-wrap">
    <table class="aval-grade">
        <thead>
            <tr>
                <th class="th-aluno">Colega</th>
                <th class="th-todos">Conceito</th>
                <th class="th-todos">Valor</th>
                <th class="th-todos" style="text-align:left;">Justificativa *</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $colegas = [
                ['MO','Maria Oliveira','Grupo Alpha','MB','90'],
                ['PS','Pedro Santos','Grupo Alpha','B','80'],
                ['AC','Ana Costa','Grupo Beta','R','60'],
                ['LC','Lucas Cardoso','Grupo Beta','MB','90'],
            ];
            foreach ($colegas as $c): ?>
                <tr>
                    <td class="td-aluno">
                        <span class="aval-aluno">
                            <span class="avatar avatar-blue"><?= $c[0] ?></span>
                            <span><?= $c[1] ?><small><i class="fas fa-users"></i> <?= $c[2] ?></small></span>
                        </span>
                    </td>
                    <td><div class="aval-celula"><span class="aval-select"><?= $c[3] ?></span></div></td>
                    <td><div class="aval-celula"><span class="aval-input"><?= $c[4] ?></span></div></td>
                    <td><div class="aval-just">Colaborou muito com o grupo nas tarefas de pesquisa...</div></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<p class="mt-16"><button class="button button-primary"><i class="fas fa-save"></i> Salvar avaliações</button></p>