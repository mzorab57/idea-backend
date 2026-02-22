<?php
return [
    'secret' => $_ENV['JWT_SECRET'] ?? 'secret',
    'alg' => $_ENV['JWT_ALG'] ?? 'HS256',
    'expires_in' => (int)($_ENV['JWT_EXPIRES_IN'] ?? 3600)
];
