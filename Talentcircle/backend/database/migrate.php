<?php

// Simple migration runner for this project.
// Runs all files in database/migrations/ ordered by filename.
// Each migration file must return an array like:
//   return [ 'table_name' => ['sql' => 'CREATE TABLE ...'] ];
//
// Usage:
//   php backend/database/migrate.php

require_once __DIR__ . '/../app/Services/Database.php';

$pdo = Database::connection();

$migrationsDir = __DIR__ . '/migrations';
$files = glob($migrationsDir . '/*.php');

sort($files);

foreach ($files as $file) {
    $base = basename($file);

    // Load file and get definitions.
    $result = include $file;

    if ($result === true || $result === null) {
        echo "[skip] {$base} (no SQL returned)\n";
        continue;
    }

    if (!is_array($result)) {
        echo "[skip] {$base} (unexpected return type)\n";
        continue;
    }

    foreach ($result as $name => $def) {
        $sql = null;
        if (is_array($def) && isset($def['sql'])) {
            $sql = $def['sql'];
        } elseif (is_string($def)) {
            $sql = $def;
        }

        if (!$sql) {
            echo "[skip] {$base}: {$name} (no sql)\n";
            continue;
        }

        $pdo->exec($sql);
        echo "[ok] {$base}: {$name}\n";
    }
}

echo "✅ Migrations completed.\n";

