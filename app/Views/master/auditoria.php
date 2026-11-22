<?php
$tituloPagina = 'Auditoria — ' . App::getName();
$cssPagina    = 'master.css';

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';

$filtros = $filtros ?? ['usuario_id' => 0, 'acao' => '', 'tabela' => '', 'de' => '', 'ate' => ''];
$logs    = $logs ?? [];
$usuarios = $usuarios ?? [];
$tabelas  = $tabelas  ?? [];
?>

<section class="master-section">
    <div class="master-content">

        <header class="master-hero">
            <div>
                <h1><i class="fas fa-clipboard-list"></i> Auditoria</h1>
                <p class="master-subtitle">
                    <i class="fas fa-history"></i>
                    Últimas 500 ações registradas no sistema.
                </p>
            </div>
            <span class="badge-count">
                <i class="fas fa-list"></i> <?= count($logs) ?> registro(s)
            </span>
        </header>

        <!-- ============ FILTROS ============ -->
        <form method="GET" action="<?= $basePath ?>/master/auditoria" class="master-filtros">
            <div class="field-group">
                <label for="f-usuario">Usuário</label>
                <select name="usuario_id" id="f-usuario">
                    <option value="">Todos</option>
                    <?php foreach ($usuarios as $u): ?>
                        <option value="<?= (int) $u['id'] ?>"
                            <?= (int) $filtros['usuario_id'] === (int) $u['id'] ? 'selected' : '' ?>>
                            <?= h($u['nome']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="field-group">
                <label for="f-acao">Ação</label>
                <input type="text" name="acao" id="f-acao"
                       value="<?= h($filtros['acao']) ?>"
                       placeholder="Ex: login, criado, excluido">
            </div>

            <div class="field-group">
                <label for="f-tabela">Tabela</label>
                <select name="tabela" id="f-tabela">
                    <option value="">Todas</option>
                    <?php foreach ($tabelas as $t): ?>
                        <option value="<?= h($t) ?>" <?= $filtros['tabela'] === $t ? 'selected' : '' ?>>
                            <?= h($t) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="field-group">
                <label for="f-de">De</label>
                <input type="date" name="de" id="f-de" value="<?= h($filtros['de']) ?>">
            </div>

            <div class="field-group">
                <label for="f-ate">Até</label>
                <input type="date" name="ate" id="f-ate" value="<?= h($filtros['ate']) ?>">
            </div>

            <div class="field-group" style="justify-content:flex-end; flex-direction:row; gap:8px; align-items:flex-end;">
                <button type="submit" class="btn-new">
                    <i class="fas fa-filter"></i> Filtrar
                </button>
                <a class="btn" href="<?= $basePath ?>/master/auditoria"
                   style="padding:9px 14px; border-radius:9px; border:1px solid var(--border); color:var(--muted); text-decoration:none; font-size:13px; font-weight:700;">
                    <i class="fas fa-eraser"></i> Limpar
                </a>
            </div>
        </form>

        <!-- ============ LISTA ============ -->
        <?php if (empty($logs)): ?>
            <div class="empty-state">
                <i class="fas fa-clipboard-list"></i>
                <h3>Nenhum registro encontrado</h3>
                <p>Nenhuma ação corresponde aos filtros aplicados.</p>
            </div>
        <?php else: ?>
            <div class="master-table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Data</th>
                            <th>Usuário</th>
                            <th>Ação</th>
                            <th>Tabela</th>
                            <th>Registro</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $log): ?>
                            <tr>
                                <td>#<?= (int) $log['id'] ?></td>
                                <td><small><?= h($log['created_at']) ?></small></td>
                                <td>
                                    <strong><?= h($log['usuario_nome'] ?? '—') ?></strong>
                                </td>
                                <td>
                                    <span class="status-badge">
                                        <?= h($log['acao']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if (!empty($log['tabela_afetada'])): ?>
                                        <code style="font-size:11.5px; color:var(--muted);">
                                            <?= h($log['tabela_afetada']) ?>
                                        </code>
                                    <?php else: ?>
                                        <span style="color:var(--muted);">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($log['registro_id'] !== null): ?>
                                        <strong>#<?= (int) $log['registro_id'] ?></strong>
                                    <?php else: ?>
                                        <span style="color:var(--muted);">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a class="btn btn-sm btn-edit"
                                       href="<?= $basePath ?>/master/auditoria/<?= (int) $log['id'] ?>">
                                        <i class="fas fa-eye"></i> Ver
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <div class="master-voltar">
            <a class="btn" href="<?= $basePath ?>/master"
               style="padding:9px 14px; border-radius:9px; border:1px solid var(--border); color:var(--muted); text-decoration:none; font-size:13px; font-weight:700;">
                <i class="fas fa-arrow-left"></i> Voltar ao painel
            </a>
        </div>

    </div>
</section>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>