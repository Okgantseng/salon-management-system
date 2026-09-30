<?php
require_once __DIR__ . '/auth.php';
require_admin();

function managed_tables(): array
{
    return array_map(static fn($row) => array_values($row)[0], db()->query('SHOW TABLES')->fetchAll());
}

function valid_table_name(string $table): bool
{
    return in_array($table, managed_tables(), true);
}

function safe_identifier(string $identifier): ?string
{
    return preg_match('/^[A-Za-z][A-Za-z0-9_]{0,63}$/', $identifier) ? $identifier : null;
}

function quoted_identifier(string $identifier): string
{
    return '`' . str_replace('`', '``', $identifier) . '`';
}

function approved_column_type(string $base, string $length): ?string
{
    $base = strtoupper($base);
    $simple = ['DATE', 'DATETIME', 'TEXT', 'DECIMAL(10,2)', 'INT', 'BIGINT', 'BOOLEAN'];
    if (in_array($base, $simple, true)) return $base;
    if (in_array($base, ['VARCHAR', 'CHAR'], true) && ctype_digit($length) && (int)$length >= 1 && (int)$length <= 255) return $base . '(' . (int)$length . ')';
    return null;
}
