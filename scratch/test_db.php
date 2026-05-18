<?php
try {
    $pdo = new PDO('mysql:host=localhost;dbname=alt_firma_takip', 'root', '');
    echo "DB_OK\n";
    $stmt = $pdo->query("SELECT * FROM users");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Users: " . count($users) . "\n";
} catch (Exception $e) {
    echo "DB_ERROR: " . $e->getMessage() . "\n";
}
