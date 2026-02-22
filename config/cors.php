<?php
return [
    'origin' => $_ENV['FRONTEND_ORIGIN'] ?? '*',
    'methods' => 'GET,POST,PUT,DELETE,OPTIONS',
    'headers' => 'Content-Type, Authorization'
];
