<?php

function febmart_sanitize_dbname(string $name): string
{
    return preg_match('/^[A-Za-z0-9_]+$/', $name) ? $name : 'febmart';
}

function febmart_to_root(): string
{
    $rootDir = str_replace('\\', '/', dirname(__DIR__));
    $scriptFile = str_replace('\\', '/', realpath($_SERVER['SCRIPT_FILENAME'] ?? '') ?: '');
    $scriptDir = dirname($scriptFile);

    if ($rootDir !== '' && strncasecmp($scriptDir, $rootDir, strlen($rootDir)) === 0) {
        $rel = trim(substr($scriptDir, strlen($rootDir)), '/');
        $depth = $rel === '' ? 0 : substr_count($rel, '/') + 1;

        return str_repeat('../', $depth);
    }

    return '';
}

function febmart_install_path(): string
{
    return febmart_to_root() . 'install.php';
}

function febmart_table_analysis(mysqli $conn): array
{
    $tabelInti = ['users', 'categories', 'products', 'transactions', 'transaction_details'];
    $tabelAda = [];

    $result = $conn->query('SHOW TABLES');

    if ($result) {
        while ($row = $result->fetch_array()) {
            $tabelAda[] = strtolower((string) $row[0]);
        }
        $result->free();
    }

    $tabelHilang = array_filter($tabelInti, function ($tabel) use ($tabelAda) {
        return !in_array($tabel, $tabelAda, true);
    });

    return [
        'ada'    => $tabelAda,
        'hilang' => array_values($tabelHilang),
    ];
}

function febmart_column_exists(mysqli $conn, string $table, string $column): bool
{
    $stmt = $conn->prepare(
        'SELECT COUNT(*) AS c FROM information_schema.COLUMNS '
        . 'WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?'
    );

    if (!$stmt) {
        return true; // anggap ada agar tidak mencoba ALTER
    }

    $stmt->bind_param('ss', $table, $column);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();

    return (int) $row['c'] > 0;
}

function febmart_apply_migrations(mysqli $conn, array $tabelAda): void
{
    $migrasi = [
        'categories' => [
            ['col' => 'description', 'sql' => 'ALTER TABLE categories ADD COLUMN description TEXT NULL AFTER name'],
            ['col' => 'updated_at', 'sql' => 'ALTER TABLE categories ADD COLUMN updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at'],
        ],
        'users' => [
            ['col' => 'photo', 'sql' => 'ALTER TABLE users ADD COLUMN photo VARCHAR(255) NULL AFTER password'],
            ['col' => 'updated_at', 'sql' => 'ALTER TABLE users ADD COLUMN updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at'],
        ],
        'products' => [
            ['col' => 'description', 'sql' => 'ALTER TABLE products ADD COLUMN description TEXT NULL AFTER name'],
            ['col' => 'updated_at', 'sql' => 'ALTER TABLE products ADD COLUMN updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at'],
        ],
        'transactions' => [
            ['col' => 'updated_at', 'sql' => 'ALTER TABLE transactions ADD COLUMN updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at'],
        ],
    ];

    foreach ($migrasi as $tabel => $pernyataan) {
        if (!in_array($tabel, $tabelAda, true)) {
            continue;
        }

        foreach ($pernyataan as $migrasi) {
            if (febmart_column_exists($conn, $tabel, $migrasi['col'])) {
                continue;
            }

            @$conn->query($migrasi['sql']);
        }
    }
}

function febmart_run_sql_file(mysqli $conn, string $sqlFile, string $dbName = 'febmart'): array
{
    if (!is_file($sqlFile)) {
        return ['ok' => false, 'error' => 'File skema tidak ditemukan: ' . $sqlFile];
    }

    $sql = file_get_contents($sqlFile);

    if ($sql === false || trim($sql) === '') {
        return ['ok' => false, 'error' => 'File skema kosong atau tidak terbaca.'];
    }

    if ($dbName !== 'febmart') { // sesuaikan nama DB di file skema
        $sql = str_replace('febmart', $dbName, $sql);
    }

    if (!$conn->multi_query($sql)) {
        return ['ok' => false, 'error' => $conn->error];
    }

    do {
        if ($result = $conn->store_result()) {
            $result->free();
        }
    } while ($conn->more_results() && $conn->next_result());

    if ($conn->error !== '') {
        return ['ok' => false, 'error' => $conn->error];
    }

    return ['ok' => true, 'error' => ''];
}
