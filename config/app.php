<?php

declare(strict_types=1);

return [
    'auth' => [
        // Credenciais usadas apenas na inicializacao do primeiro usuario.
        'initial_username' => 'trofeusZanoello',
        'initial_password' => 'zanoello*#$admin@trofeus)',
    ],
    'storage' => [
        'dir' => dirname(__DIR__) . '/storage',
    ],
];
