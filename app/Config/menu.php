<?php
// app/Config/menu.php

return [
    'menu' => [
        // ========================================
        // ITENS PARA TODOS OS USUÁRIOS
        // ========================================
        [
            'label' => 'Início',
            'icon' => 'fas fa-home',
            'url' => '/',
            'roles' => [],
        ],
        [
            'label' => 'Tutorial',
            'icon' => 'fa-solid fa-graduation-cap',
            'url' => '/tutorial',
            'roles' => [],
        ],
        [
            'label' => 'Dashboard',
            'icon' => 'fas fa-chart-pie',
            'url' => '/dashboard',
            'roles' => [1, 2],
        ],
        [
            'label' => 'Minha Conta',
            'icon' => 'fas fa-user',
            'url' => '/user',
            'roles' => [1, 2, 3, 4],
        ],
        [
            'label' => 'Notificações',
            'icon' => 'fas fa-bell',
            'url' => '/notificacoes',
            'roles' => [1, 2, 3, 4],
            'badge' => 'notificacoes',
        ],

        // ========================================
        // ADMIN E MASTER
        // ========================================
        [
            'label' => 'Administração',
            'icon' => 'fas fa-cog',
            'roles' => [1, 2],
            'subitems' => [
                [
                    'label' => 'Usuários',
                    'icon' => 'fas fa-users',
                    'url' => '/usuarios',
                    'roles' => [1, 2],
                ],
                // ============================================================
                // REMOVIDO: Primeiro Acesso Pendente
                // ============================================================
            ],
        ],

        // ========================================
        // APENAS MASTER
        // ========================================
        [
            'label' => 'Sistema',
            'icon' => 'fas fa-server',
            'roles' => [1],
            'subitems' => [
                [
                    'label' => 'Configurações',
                    'icon' => 'fas fa-sliders-h',
                    'url' => '/master/configuracoes',
                    'roles' => [1],
                ],
                [
                    'label' => 'Logs do Sistema',
                    'icon' => 'fas fa-history',
                    'url' => '/logs',
                    'roles' => [1],
                    'module' => 'logs',
                ],
                [
                    'label' => 'Backup',
                    'icon' => 'fas fa-database',
                    'url' => '/master/backup',
                    'roles' => [1],
                    'module' => 'backup',
                ],
            ],
        ],

        // ========================================
        // APENAS ADMIN
        // ========================================
        [
            'label' => 'Relatórios',
            'icon' => 'fas fa-file-alt',
            'roles' => [2],
            'subitems' => [
                [
                    'label' => 'Relatório de Usuários',
                    'icon' => 'fas fa-users',
                    'url' => '/relatorios/usuarios',
                    'roles' => [2],
                ],
                [
                    'label' => 'Exportar Excel',
                    'icon' => 'fas fa-file-excel',
                    'url' => '/relatorios/exportar',
                    'roles' => [2],
                    'module' => 'export',
                ],
            ],
        ],

        // ========================================
        // OPERADOR
        // ========================================
        [
            'label' => 'Operações',
            'icon' => 'fas fa-tasks',
            'roles' => [3],
            'subitems' => [
                [
                    'label' => 'Minhas Atividades',
                    'icon' => 'fas fa-clipboard-list',
                    'url' => '/operador/atividades',
                    'roles' => [3],
                ],
            ],
        ],

        // ========================================
        // PÚBLICOS
        // ========================================
        [
            'label' => 'Sobre',
            'icon' => 'fas fa-info-circle',
            'url' => '/sobre',
            'roles' => [1, 2, 3, 4],
        ],
        [
            'label' => 'Termos',
            'icon' => 'fas fa-file-contract',
            'url' => '/termos',
            'roles' => [1, 2, 3, 4],
        ],
    ],
];