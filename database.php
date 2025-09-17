<?php
/**
 * Returns a shared PDO instance using the application configuration.
 *
 * @throws RuntimeException when the configuration is invalid.
 * @throws PDOException when the connection cannot be established.
 */
function getPdo(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $config = require __DIR__ . '/config.php';

    if (!is_array($config) || !isset($config['dsn'], $config['username'], $config['password'])) {
        throw new RuntimeException('Database configuration is invalid.');
    }

    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    $pdo = new PDO($config['dsn'], $config['username'], $config['password'], $options);

    return $pdo;
}
