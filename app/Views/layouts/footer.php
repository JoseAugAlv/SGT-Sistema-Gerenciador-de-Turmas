<?php
// app/Views/layouts/footer.php
$basePath = App::getBasePath();
$appName  = App::getName();
$logado   = !empty($_SESSION['usuario']);
$ano      = date('Y');
?>
        </div><!-- /.page-wrap -->

        <?php if ($logado): ?>
        <!-- Dentro do sistema: rodapé discreto -->
        <footer class="footer-app">
            <span>&copy; <?= $ano ?> <?= h($appName) ?></span>
            <nav aria-label="Links do rodapé">
                <a href="<?= $basePath ?>/sobre">Sobre</a>
                <a href="<?= $basePath ?>/termos">Termos</a>
                <a href="<?= $basePath ?>/lgpd">Privacidade</a>
                <a href="<?= $basePath ?>/tutorial">Ajuda</a>
            </nav>
        </footer>
        <?php else: ?>
        <footer class="footer">
            <div class="footer-container">

                <!-- ============================================================ -->
                <!-- GRID: about + navegação + contato                             -->
                <!-- ============================================================ -->
                <div class="footer-grid">

                    <!-- ---------- About ---------- -->
                    <div class="footer-col footer-about">
                        <div class="footer-logo">
                            <h3><?= h(strtoupper($appName)) ?><span>.</span></h3>
                        </div>
                        <p>
                            Sistema de gestão de turmas, projetos, grupos, avaliações,
                            atas e materiais — com cálculo automático de média ponderada
                            e conformidade com a LGPD.
                        </p>

                        <div class="footer-social">
                            <a href="https://github.com/JoseAugAlv" target="_blank" rel="noopener noreferrer" aria-label="GitHub">
                                <i class="fab fa-github"></i>
                            </a>
                            <a href="https://www.linkedin.com/" target="_blank" rel="noopener noreferrer" aria-label="LinkedIn">
                                <i class="fab fa-linkedin-in"></i>
                            </a>
                            <a href="https://www.instagram.com/jose.asx_" target="_blank" rel="noopener noreferrer" aria-label="Instagram">
                                <i class="fab fa-instagram"></i>
                            </a>
                        </div>
                    </div>

                    <!-- ---------- Navegação ---------- -->
                    <div class="footer-col">
                        <h4>Navegação</h4>
                        <ul>
                            <li><a href="<?= $basePath ?>/"><i class="fas fa-chevron-right"></i> Início</a></li>
                            <li><a href="<?= $basePath ?>/sobre"><i class="fas fa-chevron-right"></i> Sobre</a></li>
                            <li><a href="<?= $basePath ?>/termos"><i class="fas fa-chevron-right"></i> Termos</a></li>
                            <li><a href="<?= $basePath ?>/tutorial"><i class="fas fa-chevron-right"></i> Tutorial</a></li>
                            <li><a href="<?= $basePath ?>/lgpd"><i class="fas fa-chevron-right"></i> Privacidade</a></li>
                        </ul>
                    </div>

                    <!-- ---------- Contato ---------- -->
                    <div class="footer-col">
                        <h4>Contato</h4>
                        <ul>
                            <li>
                                <a href="mailto:contato@josealvesdev.com">
                                    <i class="fas fa-envelope"></i> contato@josealvesdev.com
                                </a>
                            </li>
                            <li>
                                <a href="https://www.instagram.com/jose.asx_" target="_blank" rel="noopener noreferrer">
                                    <i class="fab fa-instagram"></i> @jose.asx_
                                </a>
                            </li>
                            <li>
                                <a href="https://github.com/JoseAugAlv" target="_blank" rel="noopener noreferrer">
                                    <i class="fab fa-github"></i> GitHub
                                </a>
                            </li>
                        </ul>
                    </div>

                </div>

                <!-- ---------- Divider ---------- -->
                <div class="footer-divider"></div>

                <!-- ---------- Bottom ---------- -->
                <div class="footer-bottom">
                    <div class="footer-bottom-content">
                        <p>
                            &copy; <?= $ano ?> <strong><?= h($appName) ?></strong>. Todos os direitos reservados.
                        </p>

                        <a href="https://josealvesdev.com/"
                           target="_blank"
                           rel="noopener noreferrer"
                           class="footer-credits-btn">
                            <span class="credit-code">&lt;/&gt;</span>
                            <span class="credit-name">José Augusto Alves</span>
                            <i class="fas fa-arrow-right credit-arrow"></i>
                        </a>
                    </div>
                </div>

            </div>
        </footer>
        <?php endif; ?>

    </div><!-- /.main-content -->

    <?php if (!empty($_SESSION['usuario'])):
        $rotaAtual = rtrim(substr(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), strlen($basePath)), '/') ?: '/';
        $ativoEm   = fn(string $prefixo) => ($prefixo === '/' ? $rotaAtual === '/' : str_starts_with($rotaAtual, $prefixo)) ? 'ativo' : '';
    ?>
        <!-- Barra inferior (celular) -->
        <nav class="bottom-nav" aria-label="Navegação rápida">
            <a class="<?= $ativoEm('/') ?>" href="<?= $basePath ?>/"><i class="fas fa-house"></i>Início</a>
            <a class="<?= $ativoEm('/agenda') ?>" href="<?= $basePath ?>/agenda"><i class="fas fa-calendar-days"></i>Agenda</a>
            <a class="<?= $ativoEm('/turmas') ?>" href="<?= $basePath ?>/turmas"><i class="fas fa-users"></i>Turmas</a>
            <a class="<?= $ativoEm('/notificacoes') ?>" href="<?= $basePath ?>/notificacoes"><i class="fas fa-bell"></i>Avisos</a>
            <button type="button" class="bottom-nav-menu" onclick="document.getElementById('hamburger').click()"><i class="fas fa-bars"></i>Menu</button>
        </nav>

        <!-- Busca rápida de funções (Ctrl+K) -->
        <dialog class="busca-rapida" id="busca-rapida" aria-label="Buscar função">
            <div class="busca-campo">
                <i class="fas fa-magnifying-glass"></i>
                <input type="search" placeholder="O que você quer fazer? Ex.: atas, notas, materiais" autocomplete="off" aria-label="Buscar função">
                <kbd>Esc</kbd>
            </div>
            <ul role="listbox"></ul>
            <div hidden>
                <a data-busca="Entrar em uma turma com código" href="<?= $basePath ?>/turmas/entrar"><i class="fas fa-right-to-bracket"></i></a>
                <a data-busca="Trocar senha" href="<?= $basePath ?>/user/senha"><i class="fas fa-key"></i></a>
                <a data-busca="Preferências de notificação" href="<?= $basePath ?>/notificacoes/preferencias"><i class="fas fa-sliders"></i></a>
                <a data-busca="Tutorial do sistema" href="<?= $basePath ?>/tutorial"><i class="fas fa-circle-question"></i></a>
            </div>
        </dialog>
        <script src="<?= ViewHelper::asset('js/busca.js') ?>"></script>
    <?php endif; ?>
</div><!-- /.app-shell -->

<script src="<?= ViewHelper::asset('js/theme.js') ?>"></script>
<script src="<?= ViewHelper::asset('js/carregando.js') ?>"></script>
<script src="<?= ViewHelper::asset('js/pwa.js') ?>"></script>
<script src="<?= ViewHelper::asset('js/parcial.js') ?>"></script>
<?php if (!empty($_SESSION['usuario']['id'])):
    // tutorial guiado: tours das telas e os que a pessoa já fez/dispensou
    $toursCfg = require __DIR__ . '/../../Config/tours.php';
    $stTour = Database::getConnection()->prepare("SELECT tour FROM tutorial_vistos WHERE usuario_id = ?");
    $stTour->execute([(int) $_SESSION['usuario']['id']]);
    $toursJs = [];
    foreach ($toursCfg as $tid => $tc) $toursJs[$tid] = ['titulo' => $tc['titulo'], 'icone' => $tc['icone'], 'descricao' => $tc['descricao'], 'rota' => $tc['rota'], 'passos' => $tc['passos']]; ?>
<script>window.PRUMO_TOUR = <?= json_encode(['base' => App::getBasePath(), 'tours' => $toursJs, 'vistos' => $stTour->fetchAll(PDO::FETCH_COLUMN), 'token' => CsrfMiddleware::generateToken()], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) ?>;</script>
<script src="<?= ViewHelper::asset('js/tour.js') ?>"></script>
<?php endif; ?>
<script>
(function () {
    'use strict';

    function $(id) { return document.getElementById(id); }

    // 1. TEMA: ver public/js/theme.js

    // ============================================================
    // 2. VOLTAR AO TOPO
    // ============================================================
    window.voltarAoTopo = function () {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    };

    // ============================================================
    // 3. CONTADOR DE NOTIFICAÇÕES
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
    document.addEventListener('parcial:feito', atualizarContador);

    // ============================================================
    // 4. DROPDOWN DO USUÁRIO
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
    // 5. SIDEBAR
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