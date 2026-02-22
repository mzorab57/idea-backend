<?php
return [
    'account_id' => $_ENV['R2_ACCOUNT_ID'] ?? '',
    'access_key_id' => $_ENV['R2_ACCESS_KEY_ID'] ?? '',
    'secret_access_key' => $_ENV['R2_SECRET_ACCESS_KEY'] ?? '',
    'bucket' => $_ENV['R2_BUCKET'] ?? '',
    'endpoint' => $_ENV['R2_ENDPOINT'] ?? '',
    'region' => 'auto'
];
