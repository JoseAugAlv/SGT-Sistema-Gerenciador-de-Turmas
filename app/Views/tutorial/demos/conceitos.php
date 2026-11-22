<section class="hero-section">
    <div>
        <div class="eyebrow"><span class="status-dot"></span> Conceitos</div>
        <h1>Feira de Ciências 2026</h1>
        <p class="hero-subtitle">Defina as faixas de nota e a visibilidade dos boletins.</p>
    </div>
    <div class="hero-actions">
        <a class="button button-ghost"><i class="fas fa-eye"></i> Pré-visualizar</a>
    </div>
</section>

<nav class="proj-nav">
    <a><i class="fas fa-list-ol"></i> Etapas</a>
    <a><i class="fas fa-users"></i> Grupos</a>
    <a><i class="fas fa-book"></i> Atas</a>
    <a><i class="fas fa-clipboard-check"></i> Critérios</a>
    <a><i class="fas fa-star"></i> Avaliações</a>
    <strong><i class="fas fa-sliders"></i> Conceitos</strong>
    <a><i class="fas fa-file-alt"></i> Relatórios</a>
</nav>

<section class="panel mb-16">
    <div class="panel-header">
        <div><h2>Como as faixas estão distribuídas</h2><p>Pré-visualização da divisão de notas de 0 a 100.</p></div>
    </div>

    <div class="faixa-visual">
        <div class="faixa i" style="width: 50%;">I<small>49</small></div>
        <div class="faixa r" style="width: 20%;">R<small>50–69</small></div>
        <div class="faixa b" style="width: 15%;">B<small>70–84</small></div>
        <div class="faixa mb" style="width: 15%;">MB<small>85+</small></div>
    </div>
    <div class="faixa-legenda">
        <span>0</span><span>25</span><span>50</span><span>75</span><span>100</span>
    </div>
</section>

<section class="panel mb-16">
    <div class="panel-header">
        <div><h2>Faixas de nota</h2><p>Cada faixa define o conceito final de uma média ponderada.</p></div>
    </div>

    <div class="faixas-grid">
        <div class="faixa-card i">
            <div class="faixa-card-header">
                <strong>I — Insuficiente</strong>
                <span class="faixa-range">0 → <strong>49</strong></span>
            </div>
            <div class="faixa-inputs"><div style="flex:1;"><label class="field-label">Nota máxima</label><input type="number" value="49" class="field"></div></div>
        </div>
        <div class="faixa-card r">
            <div class="faixa-card-header"><strong>R — Regular</strong><span class="faixa-range"><strong>50</strong> → <strong>69</strong></span></div>
            <div class="faixa-inputs">
                <div style="flex:1;"><label class="field-label">Mínimo</label><input type="number" value="50" class="field"></div>
                <div style="flex:1;"><label class="field-label">Máximo</label><input type="number" value="69" class="field"></div>
            </div>
        </div>
        <div class="faixa-card b">
            <div class="faixa-card-header"><strong>B — Bom</strong><span class="faixa-range"><strong>70</strong> → <strong>84</strong></span></div>
            <div class="faixa-inputs">
                <div style="flex:1;"><label class="field-label">Mínimo</label><input type="number" value="70" class="field"></div>
                <div style="flex:1;"><label class="field-label">Máximo</label><input type="number" value="84" class="field"></div>
            </div>
        </div>
        <div class="faixa-card mb">
            <div class="faixa-card-header"><strong>MB — Muito Bom</strong><span class="faixa-range"><strong>85</strong> → 100</span></div>
            <div class="faixa-inputs"><div style="flex:1;"><label class="field-label">Nota mínima</label><input type="number" value="85" class="field"></div></div>
        </div>
    </div>
</section>

<section class="panel">
    <div class="panel-header">
        <div><h2>Visibilidade de boletins</h2><p>Quem pode ver o boletim de quem dentro do projeto.</p></div>
    </div>

    <div class="visibilidade-grid">
        <div class="visibilidade-card diretor">
            <span class="vis-label">Como diretor</span>
            <h3><i class="fas fa-crown"></i> Diretores podem ver</h3>
            <div class="radio-cards">
                <label class="radio-card"><input type="radio" name="vd" checked><div class="radio-card-info"><strong>Toda a turma</strong><small>Diretor vê notas de todos os alunos.</small></div></label>
                <label class="radio-card"><input type="radio" name="vd"><div class="radio-card-info"><strong>Apenas o grupo</strong><small>Só alunos do próprio grupo.</small></div></label>
                <label class="radio-card"><input type="radio" name="vd"><div class="radio-card-info"><strong>Apenas o próprio</strong><small>Só o próprio boletim.</small></div></label>
            </div>
        </div>

        <div class="visibilidade-card aluno">
            <span class="vis-label">Como aluno</span>
            <h3><i class="fas fa-user-graduate"></i> Alunos podem ver</h3>
            <div class="radio-cards">
                <label class="radio-card"><input type="radio" name="va"><div class="radio-card-info"><strong>Toda a turma</strong><small>Aluno vê notas de todos.</small></div></label>
                <label class="radio-card"><input type="radio" name="va"><div class="radio-card-info"><strong>Apenas o grupo</strong><small>Só alunos do próprio grupo.</small></div></label>
                <label class="radio-card"><input type="radio" name="va" checked><div class="radio-card-info"><strong>Apenas o próprio</strong><small>Só o próprio boletim.</small></div></label>
            </div>
        </div>
    </div>
</section>