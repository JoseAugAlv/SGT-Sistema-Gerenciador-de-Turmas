<section class="hero-section">
    <div>
        <div class="eyebrow"><span class="status-dot"></span> Avaliação por diretor</div>
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
    <strong><i class="fas fa-crown"></i> Diretor</strong>
    <a><i class="fas fa-users"></i> Pares</a>
    <a><i class="fas fa-user-circle"></i> Autoavaliação</a>
    <a><i class="fas fa-graduation-cap"></i> Minhas Notas</a>
</nav>

<div class="aval-grade-wrap">
    <table class="aval-grade">
        <thead>
            <tr>
                <th class="th-aluno">Membro do grupo</th>
                <th class="th-diretores">Participação<small>diretor — peso 3,00</small></th>
                <th class="th-diretores">Pesquisa<small>misto — peso 3,00</small></th>
                <th class="th-diretores">Colaboração<small>diretor — peso 2,00</small></th>
            </tr>
        </thead>
        <tbody>
            <?php
            $membros = [
                ['MO','Maria Oliveira','você','—','—','—'],
                ['JS','João Silva','','MB','B','MB'],
                ['PS','Pedro Santos','','B','B','MB'],
                ['AC','Ana Costa','','MB','R','B'],
                ['LC','Lucas Cardoso','','B','MB','B'],
                ['RM','Rafael Mendes','','MB','B','MB'],
            ];
            foreach ($membros as $m): ?>
                <tr>
                    <td class="td-aluno">
                        <span class="aval-aluno">
                            <span class="avatar avatar-blue"><?= $m[0] ?></span>
                            <span><?= $m[1] ?><?php if ($m[2] === 'você'): ?><span class="aval-sou-eu">você</span><?php endif; ?></span>
                        </span>
                    </td>
                    <?php if ($m[2] === 'você'): ?>
                        <td colspan="3"><span class="aval-bloqueado-cel">Não pode se autoavaliar</span></td>
                    <?php else: ?>
                        <td><div class="aval-celula"><span class="aval-select"><?= $m[3] ?></span></div></td>
                        <td><div class="aval-celula"><span class="aval-select"><?= $m[4] ?></span></div></td>
                        <td><div class="aval-celula"><span class="aval-select"><?= $m[5] ?></span></div></td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<p class="mt-16"><button class="button button-primary"><i class="fas fa-save"></i> Salvar avaliações</button></p>