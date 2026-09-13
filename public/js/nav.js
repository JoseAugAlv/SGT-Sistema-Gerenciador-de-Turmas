// ============================================================
// ProjetoBase - Lógica do menu principal (submenus por clique)
// ============================================================

document.addEventListener('DOMContentLoaded', function() {
    console.log('Nav.js - Inicializando...');

    // ==========================================
    // 1. SUBMENU POR CLIQUE
    // ==========================================
    var submenuToggles = document.querySelectorAll('.submenu-toggle');
    console.log('Encontrados ' + submenuToggles.length + ' toggles de submenu');

    // Fecha um submenu especifico (sempre via classe, nunca via inline style)
    function fecharSubmenu(submenu) {
        if (!submenu) return;
        submenu.classList.remove('open');
        var wrapper = submenu.closest('.has-submenu');
        var toggle = wrapper ? wrapper.querySelector('.submenu-toggle') : null;
        if (toggle) {
            toggle.classList.remove('active');
            var arrow = toggle.querySelector('.sub-arrow');
            if (arrow) arrow.classList.remove('rotated');
        }
    }

    // Fecha todos os submenus abertos, exceto o de id "exceto" (se informado)
    function fecharTodosSubmenus(exceto) {
        var menus = document.querySelectorAll('.submenu.open');
        for (var i = 0; i < menus.length; i++) {
            var menu = menus[i];
            if (exceto && menu.id === exceto) continue;
            fecharSubmenu(menu);
        }
    }

    // Adiciona evento de clique em cada botão
    for (var i = 0; i < submenuToggles.length; i++) {
        (function(toggle) {
            toggle.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();

                var targetId = this.getAttribute('data-target');
                console.log('Clique no toggle, target:', targetId);

                if (!targetId) {
                    console.warn('data-target não definido');
                    return;
                }

                var submenu = document.getElementById(targetId);

                if (!submenu) {
                    console.warn('Submenu não encontrado:', targetId);
                    return;
                }

                var isOpen = submenu.classList.contains('open');
                console.log('Submenu está aberto?', isOpen);

                // Fecha todos os outros (nunca deixa mais de um aberto)
                fecharTodosSubmenus(targetId);

                if (isOpen) {
                    fecharSubmenu(submenu);
                    console.log('Fechou submenu');
                } else {
                    submenu.classList.add('open');
                    this.classList.add('active');
                    var arrow = this.querySelector('.sub-arrow');
                    if (arrow) arrow.classList.add('rotated');
                    console.log('Abriu submenu');
                }
            });
        })(submenuToggles[i]);
    }

    // ==========================================
    // 2. FECHAR SUBMENU AO CLICAR FORA
    // ==========================================
    document.addEventListener('click', function(e) {
        var isToggle = e.target.closest('.submenu-toggle');
        var isSubmenu = e.target.closest('.submenu');
        var isHasSubmenu = e.target.closest('.has-submenu');

        if (!isToggle && !isSubmenu && !isHasSubmenu) {
            fecharTodosSubmenus();
        }
    });

    // ==========================================
    // 3. FECHAR SUBMENU COM ESC
    // ==========================================
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            fecharTodosSubmenus();
        }
    });

    // ==========================================
    // 4. FECHAR SUBMENU AO CLICAR EM UM ITEM
    // ==========================================
    var submenuLinks = document.querySelectorAll('.submenu li a');
    for (var j = 0; j < submenuLinks.length; j++) {
        (function(link) {
            link.addEventListener('click', function() {
                var submenu = this.closest('.submenu');
                fecharSubmenu(submenu);
            });
        })(submenuLinks[j]);
    }

    // ==========================================
    // 5. MOBILE - SIDEBAR
    // ==========================================
    var openBtn = document.getElementById('openBtn');
    var closeBtn = document.getElementById('closeBtn');
    var sidebar = document.getElementById('sidebar');
    var body = document.body;

    if (openBtn && sidebar) {
        openBtn.addEventListener('click', function(e) {
            e.preventDefault();
            sidebar.classList.add('active');
            body.classList.add('sidebar-open');
        });
    }

    if (closeBtn && sidebar) {
        closeBtn.addEventListener('click', function(e) {
            e.preventDefault();
            sidebar.classList.remove('active');
            body.classList.remove('sidebar-open');
        });
    }

    document.addEventListener('click', function(e) {
        if (sidebar && sidebar.classList.contains('active')) {
            if (!sidebar.contains(e.target) && e.target !== openBtn) {
                sidebar.classList.remove('active');
                body.classList.remove('sidebar-open');
            }
        }
    });

    console.log('Nav.js - Submenus por clique inicializados!');
});