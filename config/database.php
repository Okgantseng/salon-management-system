<?php
/**
 * PDO connection settings for XAMPP.
 * Change these values only if the local MySQL account or database name differs.
 */
const DB_HOST = 'localhost';
const DB_NAME = 'salon_management';
const DB_USER = 'root';
const DB_PASS = '';
const APP_BASE_URL = '/salon_management';

function db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    } catch (PDOException $exception) {
        http_response_code(503);
        exit('Database connection is unavailable. Please start MySQL and import database/salon_management.sql.');
    }

    return $pdo;
}
