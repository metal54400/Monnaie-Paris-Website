<?php
declare(strict_types=1);

$dataDir   = __DIR__ . '/data';
$uploadDir = __DIR__ . '/uploads';
$dbFile    = $dataDir . '/collection.sqlite';

foreach ([$dataDir, $uploadDir] as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

try {
    $pdo = new PDO('sqlite:' . $dbFile);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    http_response_code(500);
    die('Erreur de connexion à la base : ' . $e->getMessage());
}

$pdo->exec("
    CREATE TABLE IF NOT EXISTS pieces (
        id           INTEGER PRIMARY KEY AUTOINCREMENT,
        accession_no INTEGER NOT NULL,
        name         TEXT NOT NULL,
        piece_date   TEXT NOT NULL,
        image_path   TEXT NOT NULL,
        added_at     INTEGER NOT NULL
    )
");

$pdo->exec("
    CREATE TABLE IF NOT EXISTS posts (
        id           INTEGER PRIMARY KEY AUTOINCREMENT,
        title        TEXT NOT NULL,
        post_date    TEXT NOT NULL,
        content      TEXT NOT NULL,
        image_path   TEXT,
        added_at     INTEGER NOT NULL
    )
");