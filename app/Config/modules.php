<?php
// app/Config/modules.php

return [
    'modules' => [
        'logs' => true,          // Sistema de logs
        'notificacoes' => true,  // Sistema de notificações
        'backup' => true,        // Sistema de backup
        'api' => false,          // API REST
        'export' => true,        // Exportação (PDF/Excel)
        'uploads' => true,       // Sistema de upload
        'auditoria' => true,     // Auditoria de ações
    ],
    'custom_modules' => [
        // Adicione seus módulos personalizados aqui
        // Ex: 'clientes' => true,
    ],
];