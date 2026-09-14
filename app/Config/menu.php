<?php
// app/Config/menu.php
//
// Cada item tem:
//   label  => texto exibido
//   url    => caminho relativo ao APP_BASE_PATH
//   roles  => [] (todos) ou lista de tipos ('master', 'aluno')
//   module => (opcional) nome de módulo que precisa estar ativo em modules.php

return [
    'menu' => [
        // ============ PÚBLICO ============
        [
            'label' => 'Início',
            'url'   => '/',
            'roles' => [],
        ],
        [
            'label' => 'Tutorial',
            'url'   => '/tutorial',
            'roles' => [],
        ],

        // ============ LOGADO ============
        [
            'label' => 'Turmas',
            'url'   => '/turmas',
            'roles' => ['master', 'aluno'],
        ],

        // ============ MASTER ============
        [
            'label' => 'Painel Master',
            'url'   => '/master',
            'roles' => ['master'],
        ],
                [
            'label' => 'Config. Turmas',
            'url'   => '/configuracoes/turmas',
            'roles' => ['master'],
        ],
        
    ],
];