<?php
$cssPagina = ['projetos.css', 'atas.css'];

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';
require_once __DIR__ . '/../../Models/Ata.php';

// Presença atual de cada membro do grupo (quem ainda não tem registro conta como presente)
$registro = [];
foreach ($participantes as $p) $registro[(int) $p['aluno_id']] = $p;
$tiposRelatorio = ['ocorrencia' => 'Ocorrência', 'decisao' => 'Decisão', 'encaminhamento' => 'Encaminhamento', 'observacao' => 'Observação'];
$idAta = (int) $ata['id'];

// Escolha de pessoas (atividades e relatórios): grupo em destaque, resto da turma recolhido
$seletorPessoas = function (string $prefixo) use ($alunos): void { ?>
    <fieldset class="pessoas">
        <legend>Quem participou</legend>
        <?php if (!empty($alunos['grupo'])): ?>
            <div class="pessoas-lista">
                <?php foreach ($alunos['grupo'] as $a): ?>
                    <label class="pessoa-chip"><input type="checkbox" name="participantes[]" value="<?= (int) $a['id'] ?>"><span><?= h($a['nome']) ?></span></label>
                <?php endforeach; ?>
                <button type="button" class="pessoas-todos" data-marcar-todos>Marcar todo o grupo</button>
            </div>
        <?php endif; ?>
        <?php if (!empty($alunos['turma'])): ?>
            <details class="pessoas-outros">
                <summary>Pessoas de outros grupos (<?= count($alunos['turma']) ?>)</summary>
                <div class="pessoas-lista">
                    <?php foreach ($alunos['turma'] as $a): ?>
                        <label class="pessoa-chip"><input type="checkbox" name="participantes[]" value="<?= (int) $a['id'] ?>"><span><?= h($a['nome']) ?></span></label>
                    <?php endforeach; ?>
                </div>
            </details>
        <?php endif; ?>
    </fieldset>
<?php };
?>

<section class="hero-section">
    <div>
        <h1><?= h($ata['titulo']) ?></h1>
        <p class="hero-subtitle">
            Preencher ata · Grupo <?= h($ata['grupo_nome']) ?> · reunião de <?= ViewHelper::data($ata['data_ata']) ?>
            <?php if (!empty($ata['prazo_preenchimento'])): ?> · enviar até <?= ViewHelper::data($ata['prazo_preenchimento']) ?><?php endif; ?>
        </p>
    </div>
    <div class="hero-actions">
        <a class="button button-ghost" href="<?= $basePath ?>/atas/<?= $idAta ?>"><i class="fas fa-eye"></i> Ver ata</a>
    </div>
</section>

<?php require __DIR__ . '/../projetos/_nav.php'; ?>

<ol class="passos-ata">

    <?php if (!$souDiretorAta): ?>
        <li class="passo passo-aviso">
            <p><i class="fas fa-users" aria-hidden="true"></i> Esta ata está aberta para o grupo: registre o que <b>você</b> fez e, se quiser, escreva relatórios. O diretor confere as presenças e envia a ata.</p>
        </li>
    <?php endif; ?>

    <!-- ============ 1. PRESENÇAS ============ -->
    <?php if ($souDiretorAta): ?>
    <li class="passo">
        <header class="passo-topo">
            <span class="passo-num num">1</span>
            <div><h2>Presenças</h2><p>Marque como cada membro do grupo participou. Falta justificada precisa do motivo.</p></div>
        </header>
        <?php if (empty($membros)): ?>
            <div class="lista-vazia"><i class="fas fa-users"></i><span>O grupo não tem membros ativos.</span></div>
        <?php else: ?>
            <form method="POST" action="<?= $basePath ?>/atas/<?= $idAta ?>/participantes" class="presencas">
                <?= ViewHelper::csrfField() ?>
                <ul class="presenca-lista">
                    <?php foreach ($membros as $m):
                        $aid  = (int) $m['usuario_id'];
                        $pres = $registro[$aid]['presente'] ?? 'sim';
                        $just = $registro[$aid]['justificativa'] ?? '';
                    ?>
                        <li class="presenca-linha" data-presenca="<?= h($pres) ?>">
                            <span class="aval-aluno">
                                <span class="avatar avatar-blue" aria-hidden="true"><?= h(mb_strtoupper(mb_substr($m['nome'], 0, 1))) ?></span>
                                <strong><?= h($m['nome']) ?></strong>
                            </span>
                            <span class="presenca-opcoes" role="radiogroup" aria-label="Presença de <?= h($m['nome']) ?>">
                                <?php foreach (['sim' => 'Presente', 'justificado' => 'Justificou', 'nao' => 'Faltou'] as $val => $rot): ?>
                                    <label class="presenca-op p-<?= $val ?>">
                                        <input type="radio" name="presenca[<?= $aid ?>]" value="<?= $val ?>" <?= $pres === $val ? 'checked' : '' ?>>
                                        <span><?= $rot ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </span>
                            <input type="text" class="field presenca-motivo" name="justificativa[<?= $aid ?>]" value="<?= h($just) ?>"
                                   maxlength="500" placeholder="Motivo da falta" aria-label="Motivo da falta de <?= h($m['nome']) ?>">
                        </li>
                    <?php endforeach; ?>
                </ul>
                <p class="passo-acoes"><button type="submit" class="button button-secondary"><i class="fas fa-check"></i> Salvar presenças</button></p>
            </form>
        <?php endif; ?>
    </li>
    <?php endif; ?>

    <!-- ============ 2. ATIVIDADES ============ -->
    <li class="passo">
        <header class="passo-topo">
            <span class="passo-num num">2</span>
            <div><h2>Atividades <span class="passo-conta"><?= count($atividades) ?></span></h2><p>O que foi feito na reunião. É preciso pelo menos uma para enviar.</p></div>
        </header>

        <?php foreach ($atividades as $a): ?>
            <article class="registro">
                <div class="registro-topo">
                    <strong><?= h($a['nome']) ?></strong>
                    <?php if ($souDiretorAta || (int) ($a['autor_id'] ?? 0) === $meuId): ?>
                    <form method="POST" action="<?= $basePath ?>/atividades/<?= (int) $a['id'] ?>/excluir">
                        <?= ViewHelper::csrfField() ?>
                        <button type="submit" class="registro-remover" onclick="return confirm('Remover esta atividade?')" aria-label="Remover atividade <?= h($a['nome']) ?>"><i class="fas fa-trash"></i></button>
                    </form>
                    <?php endif; ?>
                </div>
                <?php if (!empty($a['descricao'])): ?><p><?= nl2br(h($a['descricao'])) ?></p><?php endif; ?>
                <?php if (!empty($a['autor_nome'])): ?><small class="registro-autor">registrada por <?= (int) ($a['autor_id'] ?? 0) === $meuId ? 'você' : h($a['autor_nome']) ?></small><?php endif; ?>
                <?php if (!empty($a['participantes'])): ?>
                    <div class="registro-pessoas"><?php foreach ($a['participantes'] as $p): ?><span><?= h($p['usuario_nome']) ?></span><?php endforeach; ?></div>
                <?php endif; ?>
            </article>
        <?php endforeach; ?>

        <details class="registro-novo" <?= empty($atividades) ? 'open' : '' ?>>
            <summary><i class="fas fa-plus"></i> Adicionar atividade</summary>
            <form method="POST" action="<?= $basePath ?>/atas/<?= $idAta ?>/atividade">
                <?= ViewHelper::csrfField() ?>
                <label class="field-label" for="ativ-nome">Nome da atividade *</label>
                <input id="ativ-nome" type="text" name="nome" class="field" maxlength="200" required placeholder="Ex.: Ensaio da cena 2">
                <label class="field-label" for="ativ-desc">O que foi feito</label>
                <textarea id="ativ-desc" name="descricao" class="field" rows="3" maxlength="2000"></textarea>
                <?php $seletorPessoas('ativ'); ?>
                <p class="passo-acoes"><button type="submit" class="button button-primary"><i class="fas fa-plus"></i> Adicionar atividade</button></p>
            </form>
        </details>
    </li>

    <!-- ============ 3. RELATÓRIOS ============ -->
    <li class="passo">
        <header class="passo-topo">
            <span class="passo-num num">3</span>
            <div><h2>Relatórios internos <span class="passo-conta"><?= count($relatorios) ?></span></h2><p>Opcional. Ocorrências, decisões e encaminhamentos; só a coordenação vê.</p></div>
        </header>

        <?php foreach ($relatorios as $r): ?>
            <article class="registro">
                <div class="registro-topo">
                    <strong><?= h($r['titulo'] ?: ($tiposRelatorio[$r['tipo_relatorio']] ?? 'Relatório')) ?></strong>
                    <span class="registro-tipo"><?= h($tiposRelatorio[$r['tipo_relatorio']] ?? $r['tipo_relatorio']) ?><?= !empty($r['tema']) ? ' · ' . h(Ata::TEMAS_RELATORIO[$r['tema']] ?? $r['tema']) : '' ?></span>
                    <?php if ($souDiretorAta || (int) ($r['usuario_id'] ?? 0) === $meuId): ?>
                    <form method="POST" action="<?= $basePath ?>/relatorios/<?= (int) $r['id'] ?>/excluir">
                        <?= ViewHelper::csrfField() ?>
                        <button type="submit" class="registro-remover" onclick="return confirm('Remover este relatório?')" aria-label="Remover relatório"><i class="fas fa-trash"></i></button>
                    </form>
                    <?php endif; ?>
                </div>
                <p><?= nl2br(h($r['conteudo'])) ?></p>
            </article>
        <?php endforeach; ?>

        <details class="registro-novo">
            <summary><i class="fas fa-plus"></i> Adicionar relatório</summary>
            <form method="POST" action="<?= $basePath ?>/atas/<?= $idAta ?>/relatorio">
                <?= ViewHelper::csrfField() ?>
                <div class="field-row">
                    <div>
                        <label class="field-label" for="rel-tipo">Tipo *</label>
                        <select id="rel-tipo" name="tipo_relatorio" class="field" required>
                            <?php foreach ($tiposRelatorio as $v => $l): ?><option value="<?= $v ?>"><?= $l ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="field-label" for="rel-tema">Tema</label>
                        <select id="rel-tema" name="tema" class="field">
                            <option value="">Sem tema</option>
                            <?php foreach (Ata::TEMAS_RELATORIO as $v => $l): ?><option value="<?= h($v) ?>"><?= h($l) ?></option><?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <label class="field-label" for="rel-titulo">Título</label>
                <input id="rel-titulo" type="text" name="titulo" class="field" maxlength="200">
                <label class="field-label" for="rel-conteudo">Relato *</label>
                <textarea id="rel-conteudo" name="conteudo" class="field" rows="3" maxlength="3000" required></textarea>
                <?php $seletorPessoas('rel'); ?>
                <p class="passo-acoes"><button type="submit" class="button button-primary"><i class="fas fa-plus"></i> Adicionar relatório</button></p>
            </form>
        </details>
    </li>

    <!-- ============ 4. ENVIAR ============ -->
    <?php if ($souDiretorAta): ?>
    <li class="passo passo-final">
        <header class="passo-topo">
            <span class="passo-num num">4</span>
            <div>
                <h2>Enviar para validação</h2>
                <p><?= empty($atividades) ? 'Adicione pelo menos uma atividade para poder enviar.' : 'Depois de enviada, a ata não pode mais ser editada; o representante revisa e valida.' ?></p>
            </div>
        </header>
        <form method="POST" action="<?= $basePath ?>/atas/<?= $idAta ?>/finalizar">
            <?= ViewHelper::csrfField() ?>
            <button type="submit" class="button button-primary" <?= empty($atividades) ? 'disabled' : '' ?>
                    onclick="return confirm('Enviar a ata para validação? Depois disso não dá mais para editar.')">
                <i class="fas fa-paper-plane"></i> Enviar ata
            </button>
        </form>
    </li>
    <?php endif; ?>
</ol>

<script>
// Mostra o campo de motivo quando a pessoa não estava presente
document.querySelectorAll('.presenca-linha').forEach(function (li) {
    li.addEventListener('change', function (e) {
        if (e.target.type === 'radio') {
            li.dataset.presenca = e.target.value;
            if (e.target.value === 'justificado') li.querySelector('.presenca-motivo').focus();
        }
    });
});
// "Marcar todo o grupo" nos formulários de atividade e relatório
document.querySelectorAll('[data-marcar-todos]').forEach(function (b) {
    b.addEventListener('click', function () {
        b.closest('.pessoas-lista').querySelectorAll('input[type=checkbox]').forEach(function (c) { c.checked = true; });
    });
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
