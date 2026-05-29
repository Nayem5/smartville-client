<?php
define('BASE_PATH', '/fyp_soop/smartville');
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'smartville_db');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die('<div style="font-family:sans-serif;padding:2rem;background:#ff4444;color:white;border-radius:8px;">
        <h2>Database Connection Failed</h2>
        <p>' . $conn->connect_error . '</p>
        <p>Make sure XAMPP MySQL is running and you have imported database.sql</p>
    </div>');
}

$conn->set_charset('utf8mb4');
