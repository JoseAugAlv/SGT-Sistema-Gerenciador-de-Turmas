<?php
$cssPagina = 'user.css';

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';

$iniciais = '?';
if (!empty($user['nome'])) {
    $partes = preg_split('/\s+/', trim($user['nome']));
    $ini    = strtoupper(substr($partes[0] ?? '', 0, 1));
    if (count($partes) > 1) $ini .= strtoupper(substr(end($partes), 0, 1));
    $iniciais = $ini ?: '?';
}

$tipoLabel = match ($user['tipo']) {
    'master' => 'Master',
    'aluno'  => 'Aluno',
    default  => ucfirst($user['tipo']),
};
$tipoBadge = $user['tipo'] === 'master' ? 'badge-yellow' : 'badge-gray';
?>

<!-- ============================================================ -->
<!-- HEADER                                                        -->
<!-- ============================================================ -->
<section class="user-header">
    <span class="user-avatar-lg"><?= h($iniciais) ?></span>

    <div class="user-header-info">
        <h1><?= h($user['nome']) ?></h1>
        <span class="user-email">
            <i class="fas fa-envelope"></i> <?= h($user['email']) ?>
        </span>
        <div class="user-badges">
            <span class="badge <?= $tipoBadge ?>">
                <i class="fas <?= $user['tipo'] === 'master' ? 'fa-crown' : 'fa-user-graduate' ?>"></i>
                <?= h($tipoLabel) ?>
            </span>
            <?php if (!empty($user['email_confirmado'])): ?>
                <span class="badge badge-green"><i class="fas fa-check"></i> Email confirmado</span>
            <?php else: ?>
                <span class="badge badge-yellow"><i class="fas fa-hourglass-half"></i> Email pendente</span>
            <?php endif; ?>
            <?php if (!empty($user['lgpd_aceito'])): ?>
                <span class="badge badge-gray"><i class="fas fa-shield-halved"></i> LGPD aceita</span>
            <?php endif; ?>
        </div>
    </div>

    <div class="user-header-actions">
        <a class="button button-primary" href="<?= $basePath ?>/user/editar">
            <i class="fas fa-pen"></i> Editar perfil
        </a>
    </div>
</section>

<!-- ============================================================ -->
<!-- GRID: DADOS + PREFERÊNCIAS                                    -->
<!-- ============================================================ -->
<section class="user-grid">

    <!-- Dados -->
    <article class="panel">
        <div class="panel-header">
            <div>
                <h2>Dados da conta</h2>
                <p>Informações do seu cadastro.</p>
            </div>
        </div>

        <div class="user-info">
            <div class="user-info-linha">
                <span class="label">Nome completo</span>
                <span class="valor"><?= h($user['nome']) ?></span>
            </div>
            <div class="user-info-linha">
                <span class="label">Email</span>
                <span class="valor"><?= h($user['email']) ?></span>
            </div>
            <?php if (!empty($googleOk)): ?>
            <div class="user-info-linha user-google">
                <span class="label">Conta Google</span>
                <span class="valor">
                    <?php if (!empty($user['google_id'])): ?>
                        <span class="user-google-ok"><i class="fas fa-circle-check"></i> Conectada</span>
                        <form method="POST" action="<?= $basePath ?>/user/google/desconectar" class="d-inline"
                              onsubmit="return confirm('Desconectar a conta Google? Depois, para entrar, use seu email e senha.')">
                            <?= ViewHelper::csrfField() ?>
                            <button type="submit" class="user-google-link">Desconectar</button>
                        </form>
                    <?php else: ?>
                        <form method="POST" action="<?= $basePath ?>/user/google/conectar" class="d-inline">
                            <?= ViewHelper::csrfField() ?>
                            <button type="submit" class="user-google-btn">
                                <svg width="16" height="16" viewBox="0 0 48 48" aria-hidden="true"><path fill="#FFC107" d="M43.6 20.5H42V20H24v8h11.3C33.7 32.7 29.2 36 24 36c-6.6 0-12-5.4-12-12s5.4-12 12-12c3.1 0 5.8 1.2 7.9 3.1l5.7-5.7C34 6.1 29.3 4 24 4 12.9 4 4 12.9 4 24s8.9 20 20 20 20-8.9 20-20c0-1.3-.1-2.4-.4-3.5z"/><path fill="#FF3D00" d="M6.3 14.7l6.6 4.8C14.7 15.1 19 12 24 12c3.1 0 5.8 1.2 7.9 3.1l5.7-5.7C34 6.1 29.3 4 24 4 16.3 4 9.7 8.3 6.3 14.7z"/><path fill="#4CAF50" d="M24 44c5.2 0 9.9-2 13.4-5.2l-6.2-5.2C29.2 35.1 26.7 36 24 36c-5.2 0-9.6-3.3-11.3-8l-6.5 5C9.5 39.6 16.2 44 24 44z"/><path fill="#1976D2" d="M43.6 20.5H42V20H24v8h11.3c-.8 2.2-2.2 4.2-4.1 5.6l6.2 5.2C37 39.2 44 34 44 24c0-1.3-.1-2.4-.4-3.5z"/></svg>
                                Conectar com Google
                            </button>
                        </form>
                    <?php endif; ?>
                </span>
            </div>
            <style>
            .user-google .valor { display: inline-flex; align-items: center; gap: 12px; flex-wrap: wrap; }
            .user-google-ok { color: #0e9f6e; font-weight: 600; }
            .user-google-btn { display: inline-flex; align-items: center; gap: 8px; padding: 7px 12px; border-radius: 9px; cursor: pointer;
                border: 1.5px solid var(--border, #d9dee7); background: #fff; color: #1f2937; font: inherit; font-weight: 600; font-size: 14px; }
            .user-google-btn:hover { background: #f7f8fa; border-color: #b9c2d0; }
            .user-google-link { border: 0; background: none; padding: 0; color: var(--muted, #6b7280); text-decoration: underline; cursor: pointer; font: inherit; font-size: 13px; }
            </style>
            <?php endif; ?>
            <div class="user-info-linha">
                <span class="label">Tipo de conta</span>
                <span class="valor"><?= h($tipoLabel) ?></span>
            </div>
            <div class="user-info-linha">
                <span class="label">Telefone</span>
                <span class="valor <?= empty($user['telefone']) ? 'vazio' : '' ?>">
                    <?= !empty($user['telefone']) ? h($user['telefone']) : 'não informado' ?>
                </span>
            </div>
            <div class="user-info-linha">
                <span class="label">Data de nascimento</span>
                <span class="valor <?= empty($user['data_nascimento']) ? 'vazio' : '' ?>">
                    <?= !empty($user['data_nascimento']) ? h($user['data_nascimento']) : 'não informada' ?>
                </span>
            </div>
            <div class="user-info-linha">
                <span class="label">Último acesso</span>
                <span class="valor <?= empty($user['ultimo_acesso']) ? 'vazio' : '' ?>">
                    <?= !empty($user['ultimo_acesso']) ? h($user['ultimo_acesso']) : '—' ?>
                </span>
            </div>
            <div class="user-info-linha">
                <span class="label">Conta criada em</span>
                <span class="valor"><?= h($user['created_at']) ?></span>
            </div>
        </div>
    </article>

    <!-- Preferências de email -->
    <article class="panel">
        <div class="panel-header">
            <div>
                <h2>Preferências de email</h2>
                <p>Quais avisos chegam na sua caixa de entrada.</p>
            </div>
            <a class="text-link" href="<?= $basePath ?>/notificacoes/preferencias">
                Editar <span>&rarr;</span>
            </a>
        </div>

        <?php if ($prefs): ?>
            <div class="user-prefs">
                <div class="user-pref-linha">
                    <span class="pref-nome">
                        <i class="fas fa-envelope"></i> Emails gerais
                    </span>
                    <span class="pref-status <?= !empty($prefs['receber_email']) ? 'ativo' : 'inativo' ?>">
                        <?= !empty($prefs['receber_email']) ? 'Ativo' : 'Inativo' ?>
                    </span>
                </div>
                <div class="user-pref-linha">
                    <span class="pref-nome">
                        <i class="fas fa-clock"></i> Prazos de avaliação
                    </span>
                    <span class="pref-status <?= !empty($prefs['receber_email_prazos']) ? 'ativo' : 'inativo' ?>">
                        <?= !empty($prefs['receber_email_prazos']) ? 'Ativo' : 'Inativo' ?>
                    </span>
                </div>
                <div class="user-pref-linha">
                    <span class="pref-nome">
                        <i class="fas fa-bullhorn"></i> Alertas da turma
                    </span>
                    <span class="pref-status <?= !empty($prefs['receber_email_alertas']) ? 'ativo' : 'inativo' ?>">
                        <?= !empty($prefs['receber_email_alertas']) ? 'Ativo' : 'Inativo' ?>
                    </span>
                </div>
                <div class="user-pref-linha">
                    <span class="pref-nome">
                        <i class="fas fa-star"></i> Novas avaliações
                    </span>
                    <span class="pref-status <?= !empty($prefs['receber_email_avaliacoes']) ? 'ativo' : 'inativo' ?>">
                        <?= !empty($prefs['receber_email_avaliacoes']) ? 'Ativo' : 'Inativo' ?>
                    </span>
                </div>
            </div>
        <?php else: ?>
            <p class="text-muted mb-0">Nenhuma preferência registrada.</p>
        <?php endif; ?>
    </article>

</section>

<!-- ============================================================ -->
<!-- AÇÕES RÁPIDAS                                                 -->
<!-- ============================================================ -->
<section class="panel">
    <div class="panel-header">
        <div>
            <h2>Ações rápidas</h2>
            <p>Atalhos para as configurações da sua conta.</p>
        </div>
    </div>

    <div class="user-actions-grid">
        <a class="user-action-card" href="<?= $basePath ?>/user/editar">
            <span class="user-action-icon purple"><i class="fas fa-pen"></i></span>
            <div class="action-info">
                <strong>Editar perfil</strong>
                <small>Nome, telefone, nascimento</small>
            </div>
        </a>

        <a class="user-action-card" href="<?= $basePath ?>/user/senha">
            <span class="user-action-icon blue"><i class="fas fa-key"></i></span>
            <div class="action-info">
                <strong>Alterar senha</strong>
                <small>Trocar a senha da conta</small>
            </div>
        </a>

        <a class="user-action-card" href="<?= $basePath ?>/notificacoes/preferencias">
            <span class="user-action-icon orange"><i class="fas fa-sliders"></i></span>
            <div class="action-info">
                <strong>Notificações</strong>
                <small>Preferências de email</small>
            </div>
        </a>

        <a class="user-action-card" href="<?= $basePath ?>/lgpd/meus-direitos">
            <span class="user-action-icon green"><i class="fas fa-shield-halved"></i></span>
            <div class="action-info">
                <strong>Meus direitos (LGPD)</strong>
                <small>Exportar ou excluir dados</small>
            </div>
        </a>
    </div>
</section>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>