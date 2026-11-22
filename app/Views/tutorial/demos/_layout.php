<?php
// app/Views/tutorial/demos/_layout.php
// Layout próprio para as telas de DEMO — sem auth, sem nav/footer do sistema.

if (!function_exists('h')) {
    function h($s) { return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }
}

if (!isset($basePath)) $basePath = App::getBasePath();
if (!isset($appName))  $appName  = App::getName();
$ativo    = $ativo    ?? '';
$conteudo = $conteudo ?? null;

// Definição do menu lateral (fictício)
$menuPrincipal = [
    ['label' => 'Início',        'icon' => 'fas fa-house',           'chave' => 'inicio'],
    ['label' => 'Turmas',        'icon' => 'fas fa-users',           'chave' => 'turmas'],
    ['label' => 'Notificações',  'icon' => 'fas fa-bell',            'chave' => 'notificacoes'],
    ['label' => 'Projetos',      'icon' => 'fas fa-diagram-project', 'chave' => 'projetos'],
];
$menuConta = [
    ['label' => 'Minha Conta',      'icon' => 'fas fa-user',            'chave' => 'conta'],
    ['label' => 'Meus direitos',    'icon' => 'fas fa-shield-halved',   'chave' => 'lgpd'],
];

// Chave de "seção ativa" derivada do slug
$slugAtivo = $ativo;
$chaveAtiva = 'inicio';
if (in_array($slugAtivo, ['dashboard-aluno', 'dashboard-diretor', 'dashboard-representante'], true)) $chaveAtiva = 'inicio';
if (in_array($slugAtivo, ['turma-detalhe'], true))      $chaveAtiva = 'turmas';
if (in_array($slugAtivo, ['notificacoes'], true))       $chaveAtiva = 'notificacoes';
if (in_array($slugAtivo, ['projeto-detalhe','grupo-detalhe','criterios','avaliacoes-rep','avaliacoes-diretor','avaliacoes-pares','avaliacoes-auto','minhas-notas','atas-rep','atas-diretor','materiais','relatorio-geral','conceitos'], true)) $chaveAtiva = 'projetos';

$pageTitle = ucwords(str_replace('-', ' ', $slugAtivo)) . ' — Demo · ' . $appName;
?>
<!DOCTYPE html>
<html lang="pt-BR" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h($pageTitle) ?></title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

        <?php
    $cssDemo = [
        'base.css',
        'layout.css',
        'components.css',
        'footer.css',
        'turmas.css',
        'projetos.css',
        'grupos.css',
        'criterios.css',
        'avaliacoes.css',
        'atas.css',
        'materiais.css',
        'relatorios.css',
        'notificacoes.css',
        'conceitos.css',
        'responsive.css',
    ];
    foreach ($cssDemo as $arquivo):
    ?>
        <link rel="stylesheet" href="<?= $basePath ?>/public/css/<?= $arquivo ?>">
    <?php endforeach; ?>

    <!-- Aplica o tema do parent (localStorage é compartilhado) -->
    <script>
        (function () {
            try {
                var salvo = localStorage.getItem('sgt-tema');
                var tema = (salvo === 'dark' || salvo === 'light')
                    ? salvo
                    : (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
                document.documentElement.setAttribute('data-theme', tema);
            } catch (e) {
                document.documentElement.setAttribute('data-theme', 'light');
            }
        })();

        // Escuta mudanças no tema (via storage event no mesmo origin)
        window.addEventListener('storage', function (e) {
            if (e.key === 'sgt-tema' && e.newValue) {
                document.documentElement.setAttribute('data-theme', e.newValue);
            }
        });
    </script>

    <style>
        /* ============================================================
           ESTILOS INTERNOS DO LAYOUT DE DEMO
           Reaproveita o "chrome" (sidebar + topbar) do sistema real
           ============================================================ */
        body.demo-body {
            margin: 0;
            display: grid;
            grid-template-columns: 250px 1fr;
            min-height: 100vh;
            background: var(--bg);
            color: var(--text);
            font: 14px/1.5 var(--sans);
        }

        /* Sidebar */
        .demo-sidebar {
            background: var(--surface);
            border-right: 1px solid var(--border);
            padding: 20px 14px;
            display: flex;
            flex-direction: column;
            gap: 4px;
            overflow-y: auto;
        }
        .demo-brand {
            display: flex;
            align-items: center;
            gap: 11px;
            padding: 6px 8px 16px;
            border-bottom: 1px solid var(--border);
            margin-bottom: 10px;
        }
        .demo-brand .brand-mark {
            width: 40px;
            height: 40px;
            border-radius: 11px;
            background: var(--primary);
            color: #fff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 750;
            font-size: 17px;
            flex-shrink: 0;
        }
        .demo-brand strong {
            display: block;
            font-size: 15px;
            font-weight: 750;
            letter-spacing: -.2px;
        }
        .demo-brand span {
            display: block;
            font-size: 11px;
            color: var(--muted);
            margin-top: 1px;
        }
        .demo-nav-label {
            font-size: 10px;
            font-weight: 750;
            text-transform: uppercase;
            letter-spacing: .8px;
            color: var(--muted);
            padding: 14px 8px 6px;
        }
        .demo-nav-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 9px 12px;
            border-radius: 9px;
            font-size: 13px;
            font-weight: 500;
            color: var(--muted);
        }
        .demo-nav-item i {
            width: 16px;
            text-align: center;
            font-size: 13px;
            flex-shrink: 0;
        }
        .demo-nav-item.ativo {
            background: var(--primary-soft);
            color: var(--primary);
            font-weight: 700;
        }
        .demo-nav-item.ativo i { color: var(--primary); }

        /* Área principal */
        .demo-main {
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        /* Topbar */
        .demo-topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 14px;
            padding: 14px 26px;
            background: var(--surface);
            border-bottom: 1px solid var(--border);
            flex-shrink: 0;
        }
        .demo-breadcrumb {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            color: var(--muted);
        }
        .demo-breadcrumb strong {
            color: var(--text);
            font-weight: 700;
        }
        .demo-topbar-actions {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .demo-icon-btn {
            position: relative;
            width: 36px;
            height: 36px;
            border-radius: 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: var(--muted);
            font-size: 14px;
            background: transparent;
        }
        .demo-icon-btn .notif-dot {
            position: absolute;
            top: 8px;
            right: 8px;
            width: 8px;
            height: 8px;
            background: var(--red);
            border-radius: 50%;
            border: 2px solid var(--surface);
        }
        .demo-avatar {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            background: var(--primary);
            color: #fff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 750;
            font-size: 13px;
            flex-shrink: 0;
        }

        /* Conteúdo */
        .demo-content {
            padding: 24px 32px 32px;
            flex: 1;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            gap: 18px;
        }

        /* Esconde hamburger real (não há sidebar mobile aqui, é uma demo) */
        .demo-content .hamburger,
        .demo-content .sidebar-close { display: none !important; }

        /* ============================================================
           RESPONSIVO — replica o comportamento real
           ============================================================ */
        @media (max-width: 800px) {
            body.demo-body {
                grid-template-columns: 1fr;
            }
            .demo-sidebar {
                display: none;
            }
            .demo-topbar {
                padding: 12px 18px;
            }
            .demo-content {
                padding: 18px 18px 26px;
                gap: 14px;
            }
            .demo-breadcrumb span:first-child,
            .demo-breadcrumb span:nth-child(2) {
                display: none;
            }
        }
    </style>
</head>
<body class="demo-body">

    <!-- Sidebar -->
    <aside class="demo-sidebar">
        <div class="demo-brand">
            <span class="brand-mark"><?= h(strtoupper(substr($appName, 0, 1))) ?></span>
            <div>
                <strong><?= h($appName) ?></strong>
                <span>Gestão de turmas</span>
            </div>
        </div>

        <?php foreach ($menuPrincipal as $item): ?>
            <div class="demo-nav-item <?= $chaveAtiva === $item['chave'] ? 'ativo' : '' ?>">
                <i class="<?= h($item['icon']) ?>"></i>
                <span><?= h($item['label']) ?></span>
            </div>
        <?php endforeach; ?>

        <div class="demo-nav-label">Minha conta</div>
        <?php foreach ($menuConta as $item): ?>
            <div class="demo-nav-item <?= $chaveAtiva === $item['chave'] ? 'ativo' : '' ?>">
                <i class="<?= h($item['icon']) ?>"></i>
                <span><?= h($item['label']) ?></span>
            </div>
        <?php endforeach; ?>
    </aside>

    <!-- Main -->
    <div class="demo-main">
        <div class="demo-topbar">
            <div class="demo-breadcrumb">
                <span><?= h($appName) ?></span>
                <span>/</span>
                <strong><?= h(ucwords(str_replace('-', ' ', $slugAtivo))) ?></strong>
            </div>
            <div class="demo-topbar-actions">
                <span class="demo-icon-btn"><i class="fas fa-sun"></i></span>
                <span class="demo-icon-btn">
                    <i class="fas fa-bell"></i>
                    <span class="notif-dot"></span>
                </span>
                <span class="demo-avatar">JS</span>
            </div>
        </div>

        <div class="demo-content">
            <?php require $conteudo; ?>
        </div>
    </div>

</body>
</html>