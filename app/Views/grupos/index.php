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
            Grupos
        </div>
        <h1><?= h($projeto['nome']) ?></h1>
        <p class="hero-subtitle">
            <?= count($grupos) ?> grupo(s) neste projeto.
        </p>
    </div>
    <div class="hero-actions">
        <?php if ($podeEditar && !$projeto['encerrado']): ?>
            <a class="button button-primary" href="<?= $basePath ?>/projetos/<?= (int) $projeto['id'] ?>/grupos/criar">
                <i class="fas fa-plus"></i> Novo grupo
            </a>
        <?php endif; ?>
    </div>
</section>

<?php require __DIR__ . '/../projetos/_nav.php'; ?>

<?php
$pedeGrupo   = !empty($projeto['alunos_pedem_grupo']);
$pedeDiretor = !empty($projeto['alunos_pedem_diretor']);
$maxDir      = (int) ($projeto['diretores_por_grupo'] ?? 1);
$semGrupo    = empty($meusGrupos);
$pedidoEntrarPendente = null;
foreach ($meusPedidos as $porTipo) if (!empty($porTipo['entrar'])) $pedidoEntrarPendente = $porTipo['entrar'];
?>

<!-- ============ REGRAS DO PROJETO ============ -->
<div class="notice notice-blue regras-grupo">
    <span><i class="fas fa-circle-info"></i></span>
    <div>
        <strong>Como funcionam os grupos neste projeto</strong>
        <p class="mb-0">
            <?= $pedeGrupo ? 'Os alunos pedem para entrar num grupo e o representante aprova.' : 'O representante monta os grupos.' ?>
            <?= $pedeDiretor ? 'Membros podem pedir para ser diretor.' : 'O representante escolhe os diretores.' ?>
            Até <?= $maxDir ?> diretor(es) por grupo.
        </p>
    </div>
</div>
<?php if ($podeEditar && !$projeto['encerrado']): ?>
    <details class="panel mb-16 regras-editar">
        <summary><strong><i class="fas fa-sliders"></i> Mudar as regras dos grupos</strong></summary>
        <form method="POST" action="<?= $basePath ?>/projetos/<?= (int) $projeto['id'] ?>/regras-grupos" class="mt-16">
            <?= ViewHelper::csrfField() ?>
            <?php $regras = $projeto; require __DIR__ . '/../projetos/_config_grupos.php'; ?>
            <p class="mt-16 mb-0"><button type="submit" class="button button-primary">Salvar regras</button></p>
        </form>
    </details>
<?php endif; ?>

<?php if ($podeEditar && $pedidos): ?>
    <!-- ============ PEDIDOS PARA O REPRESENTANTE ============ -->
    <section class="panel mb-16 pedidos-grupo">
        <div class="panel-header"><div><h2><i class="fas fa-inbox"></i> Pedidos dos alunos (<?= count($pedidos) ?>)</h2></div></div>
        <ul class="lista-tarefas">
            <?php foreach ($pedidos as $p): ?>
                <li>
                    <span class="tarefa-icone"><i class="fas <?= $p['tipo'] === 'diretor' ? 'fa-crown' : 'fa-user-plus' ?>"></i></span>
                    <div class="tarefa-info">
                        <strong><?= h($p['usuario_nome']) ?></strong>
                        <small><?= $p['tipo'] === 'diretor' ? 'quer ser diretor do' : 'quer entrar no' ?> grupo <?= h($p['grupo_nome']) ?> · <?= ViewHelper::data($p['created_at'], true) ?></small>
                    </div>
                    <form method="POST" action="<?= $basePath ?>/solicitacoes-grupo/<?= (int) $p['id'] ?>/responder" class="d-inline">
                        <?= ViewHelper::csrfField() ?>
                        <button type="submit" name="acao" value="aprovar" class="tarefa-acao primaria">Aprovar</button>
                        <button type="submit" name="acao" value="recusar" class="tarefa-acao">Recusar</button>
                    </form>
                </li>
            <?php endforeach; ?>
        </ul>
    </section>
<?php endif; ?>

<?php if (!$podeEditar && $pedeGrupo && $semGrupo && !$projeto['encerrado']): ?>
    <div class="notice <?= $pedidoEntrarPendente ? 'notice-warning' : 'notice-blue' ?>">
        <span><i class="fas fa-users"></i></span>
        <div><strong><?= $pedidoEntrarPendente ? 'Seu pedido está aguardando o representante' : 'Você ainda não está em nenhum grupo' ?></strong>
            <p class="mb-0"><?= $pedidoEntrarPendente ? 'Assim que ele aprovar, você entra no grupo.' : 'Escolha um grupo abaixo e peça para entrar.' ?></p></div>
    </div>
<?php endif; ?>

<!-- ============================================================ -->
<!-- Grid de grupos                                               -->
<!-- ============================================================ -->
<?php if (empty($grupos)): ?>
    <section class="panel empty-state">
        <span class="icon-large text-muted"><i class="fas fa-users"></i></span>
        <h2>Nenhum grupo cadastrado</h2>
        <p>
            <?php if ($podeEditar && !$projeto['encerrado']): ?>
                Crie o primeiro grupo deste projeto clicando no botão abaixo.
            <?php else: ?>
                Aguarde o representante criar os grupos.
            <?php endif; ?>
        </p>
        <?php if ($podeEditar && !$projeto['encerrado']): ?>
            <a class="button button-primary" href="<?= $basePath ?>/projetos/<?= (int) $projeto['id'] ?>/grupos/criar">
                <i class="fas fa-plus"></i> Criar grupo
            </a>
        <?php endif; ?>
    </section>
<?php else: ?>
    <section class="grupos-grid">
        <?php foreach ($grupos as $g): ?>
            <article class="panel grupo-card">
                <div class="panel-header">
                    <div>
                        <h2><?= h($g['nome']) ?></h2>
                        <p>
                            Modo: <code><?= h($g['modo_avaliacao_grupo']) ?></code>
                            / <code><?= h($g['modo_avaliacao_por']) ?></code>
                        </p>
                    </div>
                </div>

                <div class="status-line">
                    <strong><?= (int) $g['total_membros'] ?></strong>
                    <span>membro(s)</span>
                </div>

                <div class="status-line">
                    <strong><?= (int) $g['total_diretores'] ?>/<?= $maxDir ?></strong>
                    <span>diretor(es)</span>
                </div>

                <?php
                $gid      = (int) $g['id'];
                $pedEntr  = $meusPedidos[$gid]['entrar'] ?? null;
                $pedDir   = $meusPedidos[$gid]['diretor'] ?? null;
                $souMemb  = array_key_exists($gid, $meusGrupos);
                $souDir   = $meusGrupos[$gid] ?? false;
                $lotadoDir = (int) $g['total_diretores'] >= $maxDir;
                ?>
                <?php if (!$podeEditar && !$projeto['encerrado']): ?>
                    <?php if ($pedEntr || $pedDir): $pend = $pedEntr ?: $pedDir; ?>
                        <form method="POST" action="<?= $basePath ?>/solicitacoes-grupo/<?= (int) $pend['id'] ?>/cancelar" class="grupo-pedido">
                            <?= ViewHelper::csrfField() ?>
                            <span><i class="fas fa-hourglass-half"></i> <?= $pedEntr ? 'Pedido para entrar enviado' : 'Pedido para ser diretor enviado' ?></span>
                            <button type="submit" class="tarefa-acao">Cancelar</button>
                        </form>
                    <?php elseif ($pedeGrupo && $semGrupo && !$pedidoEntrarPendente): ?>
                        <form method="POST" action="<?= $basePath ?>/grupos/<?= $gid ?>/solicitar" class="grupo-pedido">
                            <?= ViewHelper::csrfField() ?>
                            <input type="hidden" name="tipo" value="entrar">
                            <button type="submit" class="button button-secondary full-width"><i class="fas fa-user-plus"></i> Pedir para entrar</button>
                        </form>
                    <?php elseif ($pedeDiretor && $souMemb && !$souDir && !$lotadoDir): ?>
                        <form method="POST" action="<?= $basePath ?>/grupos/<?= $gid ?>/solicitar" class="grupo-pedido">
                            <?= ViewHelper::csrfField() ?>
                            <input type="hidden" name="tipo" value="diretor">
                            <button type="submit" class="button button-secondary full-width"><i class="fas fa-crown"></i> Pedir para ser diretor</button>
                        </form>
                    <?php elseif ($souMemb): ?>
                        <p class="grupo-pedido"><i class="fas fa-check"></i> <?= $souDir ? 'Você é diretor deste grupo' : 'Você está neste grupo' ?></p>
                    <?php endif; ?>
                <?php endif; ?>
                <a class="button button-primary full-width" href="<?= $basePath ?>/grupos/<?= (int) $g['id'] ?>">
                    <i class="fas fa-arrow-right"></i> Abrir grupo
                </a>
            </article>
        <?php endforeach; ?>
    </section>
<?php endif; ?>

<!-- ============================================================ -->
<!-- Tabela: todos os alunos e seus grupos                        -->
<!-- ============================================================ -->
<section class="panel mt-20">
    <div class="panel-header">
        <div>
            <h2>Alunos e seus grupos</h2>
            <p>
                Todos os <?= count($alunosComGrupos) ?> aluno(s) da turma e em quais grupos deste projeto estão.
            </p>
        </div>
    </div>

    <?php if (empty($alunosComGrupos)): ?>
        <p class="text-muted mb-0">Nenhum aluno ativo na turma.</p>
    <?php else: ?>
        <div class="alunos-wrapper">
            <table class="alunos-tabela">
                <thead>
                    <tr>
                        <th>Aluno</th>
                        <th>Grupos neste projeto</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($alunosComGrupos as $a): ?>
                        <?php
                        $gruposAluno = !empty($a['grupos_nomes'])
                            ? array_filter(array_map('trim', explode(',', $a['grupos_nomes'])))
                            : [];
                        ?>
                        <tr>
                            <td>
                                <div class="aluno-cell">
                                    <span class="avatar avatar-blue"><?= strtoupper(substr($a['nome'], 0, 1)) ?></span>
                                    <div class="aluno-cell-info">
                                        <strong><?= h($a['nome']) ?></strong>
                                        <small><?= h($a['email']) ?></small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <?php if (empty($gruposAluno)): ?>
                                    <span class="turma-badge vazio">Sem grupo</span>
                                <?php else: ?>
                                    <div class="turmas-badges">
                                        <?php foreach ($gruposAluno as $gn): ?>
                                            <span class="turma-badge"><?= h($gn) ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>