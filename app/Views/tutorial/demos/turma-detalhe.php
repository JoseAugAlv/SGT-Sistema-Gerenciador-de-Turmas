<section class="hero-section">
    <div>
        <div class="eyebrow"><span class="status-dot"></span> Turma</div>
        <h1>123-3MOD-INFO-2026</h1>
        <div class="turma-hero-meta">
            <span>Código: <code>INFO2026</code></span>
            <span><span class="badge badge-green"><i class="fas fa-check"></i> Ativa</span></span>
        </div>
    </div>
    <div class="hero-actions">
        <a class="button button-secondary"><i class="fas fa-diagram-project"></i> Ver projetos</a>
        <a class="button button-secondary"><i class="fas fa-pen"></i> Editar</a>
        <a class="button button-secondary"><i class="fas fa-user-tie"></i> Representantes</a>
    </div>
</section>

<section class="panel mb-16">
    <div class="panel-header">
        <div><h2>Representantes (2/2)</h2><p>Cada turma pode ter até 2 representantes ativos.</p></div>
        <a class="text-link">Gerenciar <span>&rarr;</span></a>
    </div>
    <div class="turma-membros-lista">
        <div class="turma-membro-item representante">
            <span class="avatar avatar-indigo">PS</span>
            <div class="membro-info"><strong>Pedro Santos</strong><small>pedro@aluno.edu</small></div>
            <span class="badge badge-yellow"><i class="fas fa-crown"></i> Representante</span>
        </div>
        <div class="turma-membro-item representante">
            <span class="avatar avatar-indigo">MO</span>
            <div class="membro-info"><strong>Maria Oliveira</strong><small>maria@aluno.edu</small></div>
            <span class="badge badge-yellow"><i class="fas fa-crown"></i> Representante</span>
        </div>
    </div>
</section>

<section class="panel">
    <div class="panel-header">
        <div><h2>Alunos (22)</h2><p>Membros ativos da turma.</p></div>
    </div>
    <div class="turma-membros-lista">
        <?php
        $alunos = [
            ['JS','João Silva','joao@aluno.edu','aluno'],
            ['AC','Ana Costa','ana@aluno.edu','aluno'],
            ['LC','Lucas Cardoso','lucas@aluno.edu','aluno'],
            ['RM','Rafael Mendes','rafael@aluno.edu','aluno'],
            ['BS','Beatriz Souza','beatriz@aluno.edu','aluno'],
            ['TL','Thiago Lima','thiago@aluno.edu','aluno'],
        ];
        foreach ($alunos as $a): ?>
            <div class="turma-membro-item">
                <span class="avatar avatar-blue"><?= $a[0] ?></span>
                <div class="membro-info">
                    <strong><?= $a[1] ?></strong>
                    <small><?= $a[2] ?> — entrou em 05/02/2026</small>
                </div>
                <span class="badge badge-gray">Aluno</span>
            </div>
        <?php endforeach; ?>
    </div>
</section>