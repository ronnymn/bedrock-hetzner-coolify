<?php
/**
 * Debug script — hit /db-test.php to see actual DB connection error
 */

header('Content-Type: text/plain');

echo "=== ENV ===\n";
echo "DB_HOST: " . getenv('DB_HOST') . "\n";
echo "DB_USER: " . getenv('DB_USER') . "\n";
echo "DB_NAME: " . getenv('DB_NAME') . "\n";
echo "DB_PASSWORD length: " . strlen((string)getenv('DB_PASSWORD')) . "\n\n";

echo "=== DNS RESOLUTION ===\n";
$host = getenv('DB_HOST');
$ip = gethostbyname($host);
echo "Hostname: $host\n";
echo "Resolved IP: $ip\n";
if ($ip === $host) {
    echo "❌ DNS FAILED — hostname did not resolve\n";
} else {
    echo "✓ DNS resolved successfully\n";
}
echo "\n";

echo "=== PORT CHECK ===\n";
$fp = @fsockopen($host, 3306, $errno, $errstr, 5);
if ($fp) {
    echo "✓ TCP connection to $host:3306 succeeded\n";
    fclose($fp);
} else {
    echo "❌ TCP connection failed: $errno - $errstr\n";
}
echo "\n";

echo "=== MYSQLI CONNECT ===\n";
$mysqli = @new mysqli(
    getenv('DB_HOST'),
    getenv('DB_USER'),
    getenv('DB_PASSWORD'),
    getenv('DB_NAME')
);

if ($mysqli->connect_error) {
    echo "❌ MySQLi error: " . $mysqli->connect_error . "\n";
    echo "Error code: " . $mysqli->connect_errno . "\n";
} else {
    echo "✓ MySQLi connection succeeded\n";
    $result = $mysqli->query("SELECT VERSION() as v");
    $row = $result->fetch_assoc();
    echo "MariaDB version: " . $row['v'] . "\n";
}
