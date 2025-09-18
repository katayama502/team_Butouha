<?php
$host = getenv('DB_HOST') ?: '127.0.0.1';
$database = getenv('DB_NAME') ?: 'butouha_app';
$charset = 'utf8mb4';

return [
    'dsn' => sprintf('mysql:host=%s;dbname=%s;charset=%s', $host, $database, $charset),
    'username' => getenv('DB_USER') ?: 'root',
    'password' => getenv('DB_PASS') ?: '',
];
