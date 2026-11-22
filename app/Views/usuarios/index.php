<?php
// app/Views/usuarios/index.php
// Lista com busca em tempo real + modal unificado (tipo + representante).

$tituloPagina = 'Gerenciar Usuários — ' . App::getName();
$cssPagina    = 'master.css';

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';

if (empty($_SESSION['usuario']) || ($_SESSION['usuario']['tipo'] ?? '') !== 'master') {
    header('Location: ' . $basePath . '/');
    exit;
}

$usuarios         = $usuarios         ?? [];
$total            = $total            ?? 0;
$busca            = $busca            ?? '';
$paginationHtml   = $paginationHtml   ?? '';
$turmas           = $turmas           ?? [];
$repsPorTurma     = $repsPorTurma     ?? [];
$repsPorUsuario   = $repsPorUsuario   ?? [];
$turmasPorUsuario = $turmasPorUsuario ?? [];

// Payload JS
$turmasJs = [];
foreach ($turmas as $t) {
    $turmasJs[] = [
        'id'      => (int) $t['id'],
        'nome'    => $t['nome'],
        'codigo'  => $t['codigo_acesso'],
        'qtdReps' => $repsPorTurma[(int) $t['id']] ?? 0,
    ];
}
?>

<section class="master-section">
    <div class="master-content">

        <!-- ============ HERO ============ -->
        <header class="master-hero">
            <div>
                <h1><i class="fas fa-users-cog"></i> Gerenciar Usuários</h1>
                <p class="master-subtitle">
                    <i class="fas fa-user-shield"></i>
                    Visualize, edite e altere o tipo de acesso dos usuários do sistema.
                </p>
            </div>
            <span class="badge-count">
                <i class="fas fa-users"></i> <?= (int) $total ?> usuário(s)
            </span>
        </header>

        <!-- ============ BARRA DE BUSCA ============ -->
        <form method="GET" action="<?= $basePath ?>/usuarios" class="master-search" id="form-busca">
            <span class="search-icon"><i class="fas fa-magnifying-glass"></i></span>

            <input type="text"
                   name="busca"
                   id="busca-input"
                   value="<?= h($busca) ?>"
                   placeholder="Digite para filtrar por nome ou email..."
                   autocomplete="off"
                   autofocus>

            <span class="search-counter" id="busca-contador"></span>

            <a class="search-clear"
               id="busca-clear"
               href="<?= $basePath ?>/usuarios"
               title="Limpar busca"
               style="<?= $busca === '' ? 'display:none;' : '' ?>">
                <i class="fas fa-xmark"></i>
            </a>

            <button type="submit" class="btn-new" id="busca-submit"
                    title="Buscar em todas as páginas">
                <i class="fas fa-search"></i> Buscar
            </button>
        </form>

        <!-- ============ INFO DA BUSCA ============ -->
        <div class="master-info" id="busca-aviso"
             style="<?= $busca === '' ? 'display:none;' : '' ?>">
            <i class="fas fa-filter"></i>
            <span id="busca-aviso-texto">
                <strong><?= (int) $total ?></strong> resultado(s) para
                <strong>"<?= h($busca) ?>"</strong>
            </span>
            <button type="button" id="busca-aviso-limpar"
                    style="margin-left:auto; background:transparent; border:none; color:var(--primary); font-weight:700; font-size:12px; cursor:pointer; display:inline-flex; align-items:center; gap:5px;">
                <i class="fas fa-xmark"></i> Limpar
            </button>
        </div>

        <!-- ============ AÇÃO NOVO ============ -->
        <div class="master-box box-primary">
            <div class="box-actions">
                <span class="box-info-text">
                    <i class="fas fa-circle-info"></i>
                    <strong>Master</strong> tem acesso total. <strong>Aluno</strong> vê apenas suas turmas e avaliações.
                </span>
                <a class="btn-new" href="<?= $basePath ?>/usuarios/criar">
                    <i class="fas fa-user-plus"></i> Novo usuário
                </a>
            </div>
        </div>

        <!-- ============ LISTA ============ -->
        <?php if (empty($usuarios)): ?>
            <div class="empty-state">
                <i class="fas fa-<?= $busca !== '' ? 'magnifying-glass' : 'users' ?>"></i>
                <?php if ($busca !== ''): ?>
                    <h3>Nenhum resultado</h3>
                    <p>Nenhum usuário encontrado para "<strong><?= h($busca) ?></strong>".</p>
                    <a class="btn-new" href="<?= $basePath ?>/usuarios">
                        <i class="fas fa-xmark"></i> Limpar busca
                    </a>
                <?php else: ?>
                    <h3>Nenhum usuário cadastrado</h3>
                    <p>Comece cadastrando o primeiro usuário do sistema.</p>
                    <a class="btn-new" href="<?= $basePath ?>/usuarios/criar">
                        <i class="fas fa-user-plus"></i> Cadastrar usuário
                    </a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="master-table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nome</th>
                            <th>Email</th>
                            <th>Tipo</th>
                            <th>Status</th>
                            <th style="text-align:right;">Ações</th>
                        </tr>
                    </thead>
                    <tbody id="users-tbody">
                        <?php foreach ($usuarios as $u): ?>
                            <?php
                            $uid       = (int) $u['id'];
                            $tipo      = $u['tipo'] ?? 'aluno';
                            $ativo     = !empty($u['ativo']);
                            $souEu     = $uid === (int) $_SESSION['usuario']['id'];
                            $perfilCl  = $tipo === 'master' ? 'perfil-1' : 'perfil-4';
                            $repsDeste = $repsPorUsuario[$uid] ?? [];
                            $qtdReps   = count($repsDeste);
                            ?>

                            <tr data-nome="<?= h(mb_strtolower($u['nome'])) ?>"
                                data-email="<?= h(mb_strtolower($u['email'])) ?>">
                                <td><strong>#<?= $uid ?></strong></td>

                                <td>
                                    <strong class="user-nome"
                                            data-raw="<?= h($u['nome']) ?>"><?= h($u['nome']) ?></strong>
                                    <?php if ($souEu): ?>
                                        <small style="color: var(--primary); font-weight: 700;">
                                            <i class="fas fa-user"></i> você
                                        </small>
                                    <?php elseif (!empty($u['ultimo_acesso'])): ?>
                                        <small>último acesso: <?= h($u['ultimo_acesso']) ?></small>
                                    <?php endif; ?>

                                    <?php if ($qtdReps > 0): ?>
                                        <small style="color: var(--orange); font-weight: 700; margin-top:3px; display:block;">
                                            <i class="fas fa-crown"></i>
                                            Representante em <?= $qtdReps ?> turma(s)
                                        </small>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <span class="user-email"
                                          data-raw="<?= h($u['email']) ?>"><?= h($u['email']) ?></span>
                                </td>

                                <td>
                                    <span class="status-badge <?= $perfilCl ?>">
                                        <?php if ($tipo === 'master'): ?>
                                            <i class="fas fa-crown"></i> Master
                                        <?php else: ?>
                                            <i class="fas fa-user-graduate"></i> Aluno
                                        <?php endif; ?>
                                    </span>
                                </td>

                                <td>
                                    <?php if ($ativo): ?>
                                        <span class="status-badge ativo">
                                            <i class="fas fa-check"></i> Ativo
                                        </span>
                                    <?php else: ?>
                                        <span class="status-badge inativo">
                                            <i class="fas fa-ban"></i> Inativo
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <td style="text-align:right; white-space:nowrap;">
                                    <button type="button"
                                            class="btn btn-sm btn-edit"
                                            title="Perfil, tipo e representante"
                                            onclick="abrirModal(<?= $uid ?>)">
                                        <i class="fas fa-user-tag"></i> Gerenciar
                                    </button>

                                    <a class="btn btn-sm btn-edit"
                                       title="Editar usuário"
                                       href="<?= $basePath ?>/usuarios/editar?id=<?= $uid ?>">
                                        <i class="fas fa-pen"></i>
                                    </a>

                                    <?php if (!$souEu): ?>
                                        <a class="btn btn-sm btn-danger"
                                           title="Desativar"
                                           href="<?= $basePath ?>/usuarios/excluir?id=<?= $uid ?>"
                                           onclick="return confirm('Desativar este usuário? O acesso será bloqueado.')">
                                            <i class="fas fa-user-slash"></i>
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Sem resultados do live filter -->
            <div class="empty-state" id="busca-sem-resultado" style="display:none;">
                <i class="fas fa-magnifying-glass"></i>
                <h3>Nenhum resultado nesta página</h3>
                <p>
                    Nenhum usuário corresponde a "<strong id="termo-sem-resultado"></strong>".
                    <br>
                    Pressione <kbd>Enter</kbd> ou clique em <strong>Buscar</strong> para procurar em todas as páginas.
                </p>
            </div>

            <?php if (!empty($paginationHtml)): ?>
                <div id="paginacao-wrap"><?= $paginationHtml ?></div>
            <?php endif; ?>
        <?php endif; ?>

        <div class="master-voltar">
            <a class="btn" href="<?= $basePath ?>/master"
               style="padding:9px 14px; border-radius:9px; border:1px solid var(--border); color:var(--muted); text-decoration:none; font-size:13px; font-weight:700; display:inline-flex; align-items:center; gap:6px;">
                <i class="fas fa-arrow-left"></i> Voltar ao painel
            </a>
        </div>

    </div>
</section>

<!-- ============================================================ -->
<!-- MODAL UNIFICADO — TIPO + REPRESENTANTE                        -->
<!-- ============================================================ -->
<div id="modalUser" class="master-modal">
    <div class="modal-box user-modal">

        <!-- HEADER -->
        <div class="modal-header">
            <h2>
                <i class="fas fa-user-gear" style="color: var(--primary);"></i>
                Gerenciar usuário
            </h2>
            <button type="button" class="modal-close" onclick="fecharModal()">&times;</button>
        </div>

        <!-- BODY -->
        <div class="modal-body">

            <!-- Info do usuário -->
            <div class="user-modal-hero">
                <span class="user-modal-avatar" id="modalAvatar">?</span>
                <div class="user-modal-info">
                    <strong id="modalUsuarioNome">—</strong>
                    <small id="modalUsuarioEmail">—</small>
                </div>
            </div>

            <!-- ============================================ -->
            <!-- SEÇÃO 1 — TIPO DE ACESSO                     -->
            <!-- ============================================ -->
            <section class="modal-section">
                <header class="modal-section-header">
                    <i class="fas fa-shield-halved" style="color: var(--primary);"></i>
                    <span>Tipo de acesso</span>
                </header>

                <p class="modal-section-desc">
                    Define as permissões do usuário no sistema.
                </p>

                <form action="<?= $basePath ?>/usuarios/atribuir-perfil" method="POST" id="form-tipo">
                    <?= ViewHelper::csrfField() ?>
                    <input type="hidden" name="id_usuario" id="tipoIdUsuario">

                    <div class="radio-cards-tipo">
                        <label class="radio-card-tipo">
                            <input type="radio" name="tipo" value="aluno" id="tipoAluno">
                            <span class="radio-card-tipo-body">
                                <span class="radio-card-tipo-icon">
                                    <i class="fas fa-user-graduate"></i>
                                </span>
                                <span class="radio-card-tipo-info">
                                    <strong>Aluno</strong>
                                    <small>Vê apenas suas turmas e avaliações</small>
                                </span>
                            </span>
                        </label>

                        <label class="radio-card-tipo">
                            <input type="radio" name="tipo" value="master" id="tipoMaster">
                            <span class="radio-card-tipo-body">
                                <span class="radio-card-tipo-icon master">
                                    <i class="fas fa-crown"></i>
                                </span>
                                <span class="radio-card-tipo-info">
                                    <strong>Master</strong>
                                    <small>Acesso total ao sistema</small>
                                </span>
                            </span>
                        </label>
                    </div>

                    <button type="submit" class="btn-new" style="margin-top:14px; width:100%; justify-content:center;">
                        <i class="fas fa-save"></i> Salvar tipo de acesso
                    </button>
                </form>
            </section>

            <!-- ============================================ -->
            <!-- SEÇÃO 2 — REPRESENTANTE                      -->
            <!-- ============================================ -->
            <section class="modal-section">
                <header class="modal-section-header">
                    <i class="fas fa-crown" style="color: var(--orange);"></i>
                    <span>Representante de turma</span>
                </header>

                <p class="modal-section-desc">
                    Representantes podem gerenciar a turma, criar critérios, validar atas e enviar alertas.
                </p>

                <!-- Reps atuais -->
                <div id="repAtuaisBox" style="display:none;">
                    <div class="modal-subtitle">Já é representante em</div>
                    <div id="repAtuaisLista" class="rep-chips"></div>
                </div>

                <!-- Vazio -->
                <div id="repVazio" class="modal-empty-mini" style="display:none;">
                    <i class="fas fa-circle-info"></i>
                    <span>Este usuário ainda não é representante de nenhuma turma.</span>
                </div>

                <!-- Form para adicionar -->
                <div id="repAdicionarWrap" style="margin-top:12px;">
                    <div class="modal-subtitle">Tornar representante de</div>

                    <form action="<?= $basePath ?>/usuarios/tornar-representante" method="POST" id="form-rep">
                        <?= ViewHelper::csrfField() ?>
                        <input type="hidden" name="id_usuario" id="repIdUsuario">

                        <select id="repTurmaSelect" name="turma_id" class="form-control" required>
                            <option value="">Selecione uma turma…</option>
                        </select>

                        <small id="repTurmaInfo" class="modal-hint"></small>

                        <button type="submit" class="btn-new"
                                style="margin-top:12px; width:100%; justify-content:center; background: var(--orange);"
                                id="repBtnSalvar">
                            <i class="fas fa-crown"></i> Tornar representante
                        </button>
                    </form>
                </div>

                <!-- Sem turmas disponíveis -->
                <div id="repSemTurmas" class="modal-empty-mini" style="display:none; margin-top:12px;">
                    <i class="fas fa-circle-exclamation"></i>
                    <span>Nenhuma turma disponível (já é representante em todas ou todas estão cheias).</span>
                </div>
            </section>

        </div>
    </div>
</div>

<script>
/* ============================================================
   DADOS DO BACKEND
   ============================================================ */
var TURMAS           = <?= json_encode($turmasJs, JSON_UNESCAPED_UNICODE) ?>;
var REPS_POR_USUARIO = <?= json_encode($repsPorUsuario, JSON_UNESCAPED_UNICODE) ?>;
var USUARIOS_MAP     = {};

<?php foreach ($usuarios as $u): ?>
USUARIOS_MAP[<?= (int) $u['id'] ?>] = {
    nome:  <?= json_encode($u['nome'], JSON_UNESCAPED_UNICODE) ?>,
    email: <?= json_encode($u['email'], JSON_UNESCAPED_UNICODE) ?>,
    tipo:  <?= json_encode($u['tipo'] ?? 'aluno') ?>
};
<?php endforeach; ?>

/* ============================================================
   ABRIR MODAL
   ============================================================ */
function abrirModal(usuarioId) {
    var modal = document.getElementById('modalUser');
    if (!modal) return;

    var user = USUARIOS_MAP[usuarioId] || { nome: '—', email: '—', tipo: 'aluno' };

    /* ---- Header do modal ---- */
    document.getElementById('modalUsuarioNome').textContent  = user.nome;
    document.getElementById('modalUsuarioEmail').textContent = user.email;

    var iniciais = user.nome.trim().split(/\s+/).map(function (p) {
        return p.charAt(0).toUpperCase();
    }).slice(0, 2).join('');
    document.getElementById('modalAvatar').textContent = iniciais || '?';

    /* ---- Seção 1: tipo ---- */
    document.getElementById('tipoIdUsuario').value = usuarioId;
    document.getElementById('tipoAluno').checked  = (user.tipo === 'aluno');
    document.getElementById('tipoMaster').checked = (user.tipo === 'master');

    /* ---- Seção 2: representante ---- */
    document.getElementById('repIdUsuario').value = usuarioId;

    var reps = REPS_POR_USUARIO[usuarioId] || [];
    var boxAtuais = document.getElementById('repAtuaisBox');
    var listaAtuais = document.getElementById('repAtuaisLista');
    var repVazio = document.getElementById('repVazio');

    listaAtuais.innerHTML = '';

    if (reps.length > 0) {
        boxAtuais.style.display = 'block';
        repVazio.style.display = 'none';

        reps.forEach(function (r) {
            var chip = document.createElement('div');
            chip.className = 'rep-chip';

            var nome = document.createElement('span');
            nome.className = 'rep-chip-nome';
            nome.innerHTML = '<i class="fas fa-crown"></i> ' + escapeHtml(r.turma_nome);

            var formRemover = document.createElement('form');
            formRemover.method = 'POST';
            formRemover.action = '<?= $basePath ?>/usuarios/remover-representante';
            formRemover.style.margin = '0';
            formRemover.innerHTML =
                '<?= ViewHelper::csrfField() ?>' +
                '<input type="hidden" name="id_usuario" value="' + usuarioId + '">' +
                '<input type="hidden" name="turma_id" value="' + r.turma_id + '">' +
                '<button type="submit" class="rep-chip-btn" ' +
                'onclick="return confirm(\'Remover como representante desta turma?\')">' +
                '<i class="fas fa-xmark"></i></button>';

            chip.appendChild(nome);
            chip.appendChild(formRemover);
            listaAtuais.appendChild(chip);
        });
    } else {
        boxAtuais.style.display = 'none';
        repVazio.style.display = 'flex';
    }

    /* ---- Select de turmas disponíveis ---- */
    var select       = document.getElementById('repTurmaSelect');
    var info         = document.getElementById('repTurmaInfo');
    var wrapAdicionar = document.getElementById('repAdicionarWrap');
    var semTurmas    = document.getElementById('repSemTurmas');

    select.innerHTML = '<option value="">Selecione uma turma…</option>';
    info.textContent = '';

    var repsIds = reps.map(function (r) { return parseInt(r.turma_id, 10); });
    var disponiveis = 0;

    TURMAS.forEach(function (t) {
        // Já é rep nesta turma?
        if (repsIds.indexOf(parseInt(t.id, 10)) !== -1) return;
        // Turma cheia?
        if (t.qtdReps >= 2) return;

        var opt = document.createElement('option');
        opt.value = t.id;
        opt.dataset.qtdReps = t.qtdReps;
        var extra = (t.qtdReps === 1) ? ' — 1 vaga restante' : ' — 2 vagas';
        opt.textContent = t.nome + extra;
        select.appendChild(opt);
        disponiveis++;
    });

    if (disponiveis === 0) {
        wrapAdicionar.style.display = 'none';
        semTurmas.style.display = 'flex';
    } else {
        wrapAdicionar.style.display = 'block';
        semTurmas.style.display = 'none';
    }

    select.onchange = function () {
        var opt = this.options[this.selectedIndex];
        if (this.value && opt.dataset.qtdReps !== undefined) {
            var q = parseInt(opt.dataset.qtdReps, 10) || 0;
            info.textContent = 'Esta turma tem ' + q + ' representante(s) ativo(s) de 2 possíveis.';
        } else {
            info.textContent = '';
        }
    };

    /* ---- Abre ---- */
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
}

/* ============================================================
   FECHAR
   ============================================================ */
function fecharModal() {
    var m = document.getElementById('modalUser');
    if (m) m.classList.remove('active');
    document.body.style.overflow = '';
}

document.getElementById('modalUser').addEventListener('click', function (e) {
    if (e.target === this) fecharModal();
});

document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') fecharModal();
});

/* ============================================================
   HELPERS
   ============================================================ */
function escapeHtml(s) {
    return String(s).replace(/[&<>"']/g, function (c) {
        return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
}

/* ============================================================
   LIVE SEARCH
   ============================================================ */
(function () {
    'use strict';

    var input      = document.getElementById('busca-input');
    var tbody      = document.getElementById('users-tbody');
    if (!input || !tbody) return;

    var form       = document.getElementById('form-busca');
    var contador   = document.getElementById('busca-contador');
    var clearBtn   = document.getElementById('busca-clear');
    var aviso      = document.getElementById('busca-aviso');
    var avisoTexto = document.getElementById('busca-aviso-texto');
    var avisoClr   = document.getElementById('busca-aviso-limpar');
    var semResult  = document.getElementById('busca-sem-resultado');
    var termoVazio = document.getElementById('termo-sem-resultado');
    var paginacao  = document.getElementById('paginacao-wrap');

    var rows       = Array.prototype.slice.call(tbody.querySelectorAll('tr[data-nome]'));
    var totalRows  = rows.length;

    function destacar(el, termo) {
        if (!el) return;
        var raw = el.dataset.raw || el.textContent;
        if (!termo) { el.textContent = raw; return; }
        var safe = termo.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
        var re = new RegExp('(' + safe + ')', 'gi');
        el.innerHTML = raw.replace(re, '<mark>$1</mark>');
    }

    function filtrar() {
        var termo = input.value.trim().toLowerCase();
        var visiveis = 0;

        rows.forEach(function (tr) {
            var nome  = tr.dataset.nome  || '';
            var email = tr.dataset.email || '';
            var match = termo === '' || nome.indexOf(termo) !== -1 || email.indexOf(termo) !== -1;
            tr.style.display = match ? '' : 'none';
            if (match) visiveis++;
        });

        rows.forEach(function (tr) {
            if (tr.style.display === 'none') return;
            destacar(tr.querySelector('.user-nome'),  termo);
            destacar(tr.querySelector('.user-email'), termo);
        });

        if (termo === '') {
            contador.textContent = '';
            contador.style.display = 'none';
        } else {
            contador.textContent = visiveis + '/' + totalRows;
            contador.style.display = 'inline-flex';
        }

        clearBtn.style.display = termo === '' ? 'none' : 'inline-flex';

        if (termo === '') {
            aviso.style.display = 'none';
        } else {
            aviso.style.display = 'flex';
            avisoTexto.innerHTML = '<strong>' + visiveis + '</strong> resultado(s) nesta página para <strong>"' + termo.replace(/</g, '&lt;') + '"</strong>';
        }

        if (visiveis === 0 && termo !== '') {
            semResult.style.display = 'block';
            termoVazio.textContent = termo;
        } else {
            semResult.style.display = 'none';
        }

        if (paginacao) paginacao.style.display = termo === '' ? '' : 'none';
    }

    var timer = null;
    input.addEventListener('input', function () {
        if (timer) clearTimeout(timer);
        timer = setTimeout(filtrar, 80);
    });

    input.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            if (input.value.trim() === '') window.location.href = form.action;
            else form.submit();
        }
    });

    function limparBusca(e) {
        if (e) e.preventDefault();
        input.value = '';
        filtrar();
        input.focus();
        if (window.location.search.indexOf('busca=') !== -1) {
            window.location.href = form.action;
        }
    }
    clearBtn.addEventListener('click', limparBusca);
    if (avisoClr) avisoClr.addEventListener('click', limparBusca);

    document.addEventListener('keydown', function (e) {
        if (e.key === '/' && document.activeElement !== input
            && !['INPUT', 'TEXTAREA', 'SELECT'].includes(document.activeElement.tagName)) {
            e.preventDefault();
            input.focus();
            input.select();
        }
        if (e.key === 'Escape' && document.activeElement === input) limparBusca();
    });

    if (input.value.trim() !== '') filtrar();
})();
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>