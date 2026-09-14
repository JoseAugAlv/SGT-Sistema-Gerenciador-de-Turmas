<?php
// app/Config/menu.php

return [
    'menu' => [
        ['label' => 'Início',   'url' => '/',          'roles' => []],
        ['label' => 'Tutorial', 'url' => '/tutorial',  'roles' => []],

        // Apenas logado
        ['label' => 'Turmas',   'url' => '/turmas',    'roles' => ['master', 'aluno']],

        // Apenas master
        ['label' => 'Config. Turmas', 'url' => '/configuracoes/turmas', 'roles' => ['master']],
    ],
];