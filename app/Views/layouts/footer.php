<?php
// app/Views/layouts/footer.php
$basePath = App::getBasePath();
$appName  = App::getName();
$logado   = !empty($_SESSION['usuario']);
?>
        </div><!-- /.page-wrap -->

        <footer class="footer">
            <span>&copy; <?= date('Y') ?> <?= h($appName) ?> — Sistema de Gestão de Turmas</span>
            <span>
                <a href="<?= $basePath ?>/sobre">Sobre</a>
                <a href="<?= $basePath ?>/termos">Termos</a>
                <a href="<?= $basePath ?>/lgpd">Privacidade</a>
            </span>
        </footer>

    </div><!-- /.main-content -->
</div><!-- /.app-shell -->

<script>
(function () {
    'use strict';

    function $(id) { return document.getElementById(id); }

    // ============================================================
    // 1. TEMA CLARO/ESCURO
    // ============================================================
    (function initTema() {
        var KEY  = 'sgt-tema';
        var html = document.documentElement;
        var input = $('theme-toggle-input');
        if (!input) return;

        input.checked = html.getAttribute('data-theme') === 'dark';

        input.addEventListener('change', function () {
            var novo = this.checked ? 'dark' : 'light';
            html.setAttribute('data-theme', novo);
            try { localStorage.setItem(KEY, novo); } catch (e) {}
        });

        window.alternarTema = function () {
            var atual = html.getAttribute('data-theme') || 'light';
            var novo  = atual === 'dark' ? 'light' : 'dark';
            html.setAttribute('data-theme', novo);
            try { localStorage.setItem(KEY, novo); } catch (e) {}
        };
    })();

    // ============================================================
    // 2. CONTADOR DE NOTIFICAÇÕES
    // ============================================================
    function atualizarContador() {
        if (document.hidden) return;
        var badge = $('notif-badge');
        var dot   = $('notif-dot');
        if (!badge && !dot) return;

        fetch('<?= $basePath ?>/notificacoes/contador')
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.total > 0) {
                    if (badge) { badge.textContent = data.total; badge.style.display = 'inline-grid'; }
                    if (dot)   { dot.style.display = 'block'; }
                } else {
                    if (badge) badge.style.display = 'none';
                    if (dot)   dot.style.display = 'none';
                }
            })
            .catch(function () {});
    }
    document.addEventListener('DOMContentLoaded', atualizarContador);
    setInterval(atualizarContador, 30000);

    // ============================================================
    // 3. DROPDOWN DO USUÁRIO (avatar no topbar)
    // ============================================================
    (function initUserMenu() {
        var menu   = $('user-menu');
        var toggle = $('user-menu-toggle');
        if (!menu || !toggle) return;

        toggle.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            var aberto = menu.classList.toggle('open');
            toggle.setAttribute('aria-expanded', aberto ? 'true' : 'false');
        });

        document.addEventListener('click', function (e) {
            if (!menu.contains(e.target)) {
                menu.classList.remove('open');
                toggle.setAttribute('aria-expanded', 'false');
            }
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                menu.classList.remove('open');
                toggle.setAttribute('aria-expanded', 'false');
            }
        });

        menu.querySelectorAll('.user-menu-item').forEach(function (item) {
            item.addEventListener('click', function () {
                menu.classList.remove('open');
            });
        });
    })();

    // ============================================================
    // 4. SIDEBAR (hamburger + overlay)
    // ============================================================
    (function initSidebar() {
        var sidebar  = $('sidebar');
        var overlay  = $('sidebar-overlay');
        var btnOpen  = $('hamburger');
        var btnClose = $('sidebar-close');
        var body     = document.body;

        function abrir() {
            if (!sidebar) return;
            sidebar.classList.add('open');
            if (overlay) overlay.classList.add('visible');
            body.classList.add('sidebar-open');
        }
        function fechar() {
            if (!sidebar) return;
            sidebar.classList.remove('open');
            if (overlay) overlay.classList.remove('visible');
            body.classList.remove('sidebar-open');
        }

        if (btnOpen)  btnOpen.addEventListener('click', abrir);
        if (btnClose) btnClose.addEventListener('click', fechar);
        if (overlay)  overlay.addEventListener('click', fechar);

        if (sidebar) {
            sidebar.querySelectorAll('.nav-item').forEach(function (a) {
                a.addEventListener('click', fechar);
            });
        }

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') fechar();
        });

        window.addEventListener('resize', function () {
            if (window.innerWidth > 800) fechar();
        });
    })();

})();
</script>

</body>
</html>