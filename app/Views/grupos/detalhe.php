<?php
$cssPagina = ['projetos.css', 'grupos.css', 'alunos.css'];

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';
?>

<section class="hero-section">
    <div>
        <div class="eyebrow">
            <span class="status-dot"></span>
            Grupo
        </div>
        <h1><?= h($grupo['nome']) ?></h1>
        <div class="grupo-hero-meta">
            <span>
                Projeto:
                <a href="<?= $basePath ?>/projetos/<?= (int) $projeto['id'] ?>"><?= h($projeto['nome']) ?></a>
            </span>
            <span>
                Modo: <code><?= h($grupo['modo_avaliacao_grupo']) ?></code>
                / <code><?= h($grupo['modo_avaliacao_por']) ?></code>
            </span>
        </div>
    </div>

    <div class="hero-actions">
        <?php if ($podeEditar): ?>
            <a class="button button-secondary" href="<?= $basePath ?>/grupos/<?= (int) $grupo['id'] ?>/editar">
                <i class="fas fa-pen"></i> Editar grupo
            </a>
            <a class="button button-secondary" href="<?= $basePath ?>/grupos/<?= (int) $grupo['id'] ?>/config-avaliacao">
                <i class="fas fa-sliders"></i> Config. avaliação
            </a>
            <a class="button button-secondary" href="<?= $basePath ?>/grupos/<?= (int) $grupo['id'] ?>/diretores/alocar">
                <i class="fas fa-user-plus"></i> Alocar em massa
            </a>
            <a class="button button-secondary" href="<?= $basePath ?>/grupos/<?= (int) $grupo['id'] ?>/diretores">
                <i class="fas fa-crown"></i> Gerenciar diretores
            </a>
            <a class="button button-secondary" href="<?= $basePath ?>/grupos/<?= (int) $grupo['id'] ?>/diretores/trocar">
                <i class="fas fa-right-left"></i> Trocar diretor
            </a>
        <?php endif; ?>
        <a class="button button-secondary" href="<?= $basePath ?>/grupos/<?= (int) $grupo['id'] ?>/diretores/historico">
            <i class="fas fa-history"></i> Histórico de diretores
        </a>
    </div>
</section>

<?php require __DIR__ . '/../projetos/_nav.php'; ?>

<!-- ============ DIRETORES ATIVOS ============ -->
<section class="panel mb-16">
    <div class="panel-header">
        <div>
            <h2>Diretores ativos (<?= count($diretores) ?>)</h2>
            <p>Quem lidera este grupo no momento.</p>
        </div>
    </div>

    <?php if (empty($diretores)): ?>
        <p class="text-muted">Nenhum diretor nomeado.</p>
    <?php else: ?>
        <div class="diretores-lista">
            <?php foreach ($diretores as $d): ?>
                <div class="diretor-item">
                    <span class="avatar"><?= strtoupper(substr($d['nome'], 0, 1)) ?></span>
                    <div class="diretor-info">
                        <strong><?= h($d['nome']) ?></strong>
                        <small>
                            <?= h($d['email']) ?>
                            — nomeado em <?= h($d['nomeado_em']) ?>
                        </small>
                    </div>
                    <span class="badge badge-yellow"><i class="fas fa-crown"></i> Diretor</span>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<!-- ============ MEMBROS + ADICIONAR EM MASSA ============ -->
<section class="panel">
    <div class="panel-header">
        <div>
            <h2>Membros (<?= count($membros) ?>)</h2>
            <p>Alunos ativos neste grupo.</p>
        </div>
    </div>

    <?php if (empty($membros)): ?>
        <p class="text-muted">Nenhum membro.</p>
    <?php else: ?>
        <div class="membros-lista">
            <?php foreach ($membros as $m): ?>
                <?php
                $ehDiretor = false;
                foreach ($diretores as $d) {
                    if ((int) $d['usuario_id'] === (int) $m['usuario_id']) { $ehDiretor = true; break; }
                }
                ?>
                <div class="membro-item">
                    <span class="avatar avatar-blue"><?= strtoupper(substr($m['nome'], 0, 1)) ?></span>
                    <div class="membro-info">
                        <strong><?= h($m['nome']) ?></strong>
                        <small><?= h($m['email']) ?></small>
                    </div>

                    <?php if ($ehDiretor): ?>
                        <span class="badge badge-yellow"><i class="fas fa-crown"></i> Diretor</span>
                    <?php endif; ?>

                    <?php if ($podeEditar): ?>
                        <form method="POST" action="<?= $basePath ?>/grupos/<?= (int) $grupo['id'] ?>/membros/remover"
                              class="d-inline">
                            <?= ViewHelper::csrfField() ?>
                            <input type="hidden" name="usuario_id" value="<?= (int) $m['usuario_id'] ?>">
                            <button type="submit" class="button small btn-outline-danger"
                                    onclick="return confirm('Remover este membro do grupo?')">
                                <i class="fas fa-user-minus"></i> Remover
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- ============ ADICIONAR MEMBROS (checkboxes) ============ -->
    <?php if ($podeEditar): ?>
        <?php if (empty($candidatos)): ?>
            <p class="sem-candidatos">
                <i class="fas fa-info-circle"></i>
                Nenhum aluno disponível — todos os alunos da turma já estão neste grupo.
            </p>
        <?php else: ?>
            <div class="add-membro-bloco">
                <div class="add-membro-header">
                    <h3>
                        <i class="fas fa-user-plus"></i>
                        Adicionar membros
                    </h3>
                    <p class="text-muted mb-0" style="font-size:12px;">
                        <?= count($candidatos) ?> aluno(s) disponível(is) na turma.
                        Apenas alunos que <strong>ainda não estão</strong> neste grupo aparecem abaixo.
                    </p>
                </div>

                <form method="POST" action="<?= $basePath ?>/grupos/<?= (int) $grupo['id'] ?>/membros/adicionar"
                      class="add-membro-form" id="form-add-membros">
                    <?= ViewHelper::csrfField() ?>

                    <div class="add-membro-acoes-topo">
                        <button type="button" class="button small button-ghost" onclick="marcarTodosAdd(true)">
                            <i class="fas fa-check-square"></i> Marcar todos
                        </button>
                        <button type="button" class="button small button-ghost" onclick="marcarTodosAdd(false)">
                            <i class="fas fa-square"></i> Desmarcar todos
                        </button>
                        <span class="add-membro-contador">
                            <strong id="add-membro-count">0</strong> selecionado(s)
                        </span>
                    </div>

                    <div class="checkbox-list">
                        <?php foreach ($candidatos as $c): ?>
                            <label class="checkbox-turma">
                                <input type="checkbox" name="usuario_ids[]" value="<?= (int) $c['id'] ?>"
                                       class="checkbox-add-membro"
                                       onchange="atualizarContadorAdd()">
                                <div class="checkbox-turma-info">
                                    <strong><?= h($c['nome']) ?></strong>
                                    <small><?= h($c['email']) ?></small>
                                </div>
                            </label>
                        <?php endforeach; ?>
                    </div>

                    <div class="add-membro-acoes-rodape">
                        <button type="submit" class="button button-primary" id="btn-add-membros" disabled>
                            <i class="fas fa-user-plus"></i> Adicionar selecionados
                        </button>
                    </div>
                </form>
            </div>
        <?php endif; ?>

        <div class="grupo-excluir-bloco">
            <form method="POST" action="<?= $basePath ?>/grupos/<?= (int) $grupo['id'] ?>/excluir"
                  onsubmit="return confirm('Excluir este grupo? Membros, diretores e avaliações do grupo serão perdidos.')">
                <?= ViewHelper::csrfField() ?>
                <button type="submit" class="button btn-outline-danger">
                    <i class="fas fa-trash"></i> Excluir grupo
                </button>
            </form>
        </div>
    <?php endif; ?>
</section>

<p class="mt-20">
    <a class="button button-ghost" href="<?= $basePath ?>/projetos/<?= (int) $projeto['id'] ?>/grupos">
        <i class="fas fa-arrow-left"></i> Voltar aos grupos
    </a>
</p>

<script>
(function () {
    var boxes = document.querySelectorAll('.checkbox-add-membro');
    var contador = document.getElementById('add-membro-count');
    var btn = document.getElementById('btn-add-membros');

    window.atualizarContadorAdd = function () {
        var marcados = 0;
        boxes.forEach(function (b) { if (b.checked) marcados++; });
        if (contador) contador.textContent = marcados;
        if (btn) btn.disabled = marcados === 0;
    };

    window.marcarTodosAdd = function (valor) {
        boxes.forEach(function (b) { b.checked = valor; });
        atualizarContadorAdd();
    };

    atualizarContadorAdd();
})();
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>