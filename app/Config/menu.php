<?php
// app/Config/menu.php
//
// Campos:
//   label      — texto exibido
//   url        — destino (só para links)
//   icon       — classe FontAwesome
//   roles      — [] público | ['master','aluno'] restrito
//   guest_only — true = só quando NÃO logado
//   type       — 'link' (padrão) | 'text' | 'notif'
//   confirm    — texto do confirm() antes de sair
//   secao      — agrupador na sidebar (null = sem grupo)

return [
    'menu' => [
        // ============ SEM SEÇÃO ============
        [
            'label'      => 'Início',
            'url'        => '/',
            'icon'       => 'fas fa-house',
            'roles'      => [],
            'secao'      => null,
        ],
        [
            'label'      => 'Tutorial',
            'url'        => '/tutorial',
            'icon'       => 'fas fa-graduation-cap',
            'roles'      => [],
            'secao'      => null,
        ],
        [
            'label'      => 'Entrar',
            'url'        => '/login',
            'icon'       => 'fas fa-right-to-bracket',
            'guest_only' => true,
            'secao'      => null,
        ],
        [
            'label'      => 'Cadastrar',
            'url'        => '/login/cadastrar',
            'icon'       => 'fas fa-user-plus',
            'guest_only' => true,
            'secao'      => null,
        ],

        // ============ VISÃO GERAL ============
        [
            'label' => 'Turmas',
            'url'   => '/turmas',
            'icon'  => 'fas fa-users',
            'roles' => ['master', 'aluno'],
            'secao' => 'Visão geral',
        ],
        [
            'label' => 'Notificações',
            'url'   => '/notificacoes',
            'icon'  => 'fas fa-bell',
            'roles' => ['master', 'aluno'],
            'type'  => 'notif',
            'secao' => 'Visão geral',
        ],

        // ============ MINHA CONTA ============
        [
            'label' => 'Minha Conta',
            'url'   => '/user',
            'icon'  => 'fas fa-user',
            'roles' => ['master', 'aluno'],
            'secao' => 'Minha conta',
        ],
        [
            'label' => 'Meus direitos (LGPD)',
            'url'   => '/lgpd/meus-direitos',
            'icon'  => 'fas fa-shield-halved',
            'roles' => ['master', 'aluno'],
            'secao' => 'Minha conta',
        ],

        // ============ ADMINISTRAÇÃO (MASTER) ============
        [
            'label' => 'Painel Master',
            'url'   => '/master',
            'icon'  => 'fas fa-gauge',
            'roles' => ['master'],
            'secao' => 'Administração',
        ],
        [
            'label' => 'Auditoria',
            'url'   => '/master/auditoria',
            'icon'  => 'fas fa-clipboard-list',
            'roles' => ['master'],
            'secao' => 'Administração',
        ],
        [
            'label' => 'Critérios arquivados',
            'url'   => '/master/criterios-arquivados',
            'icon'  => 'fas fa-archive',
            'roles' => ['master'],
            'secao' => 'Administração',
        ],
        [
            'label' => 'Solicitações LGPD',
            'url'   => '/master/lgpd',
            'icon'  => 'fas fa-user-shield',
            'roles' => ['master'],
            'secao' => 'Administração',
        ],
        [
            'label' => 'Backup',
            'url'   => '/master/backup',
            'icon'  => 'fas fa-database',
            'roles' => ['master'],
            'secao' => 'Administração',
        ],
        [
            'label' => 'Cursos e Períodos',
            'url'   => '/configuracoes/turmas',
            'icon'  => 'fas fa-cog',
            'roles' => ['master'],
            'secao' => 'Administração',
        ],
    ],
];