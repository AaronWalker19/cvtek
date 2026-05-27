<?php
/**
 * mysql-test.php
 * Test direct de la connexion MySQL
 */

echo "<h1>MySQL Connection Test</h1>\n";
echo "<hr>\n";

// Configuration
$host = 'localhost';
$dbname = 'cvtek';
$user = 'cvtek_admin';
$password = 'bAJCEKDok5ymrV7Ona';
$port = 3306;

echo "<h2>Parameters:</h2>\n";
echo "<pre>\n";
echo "Host: $host\n";
echo "Port: $port\n";
echo "DB: $dbname\n";
echo "User: $user\n";
echo "Password: " . ($password ? "(set)" : "(empty)") . "\n";
echo "</pre>\n";

echo "<h2>Trying connection...</h2>\n";

try {
    $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ];
    
    $pdo = new PDO($dsn, $user, $password, $options);
    echo "<p style='color:green'>SUCCESS: Connected to MySQL!</p>\n";
    
    // Test a simple query
    $stmt = $pdo->query("SELECT 1");
    $result = $stmt->fetch();
    echo "<p>Query test: OK</p>\n";
    
} catch (PDOException $e) {
    echo "<p style='color:red'>ERROR: " . $e->getMessage() . "</p>\n";
    
    echo "<h3>Troubleshooting:</h3>\n";
    echo "<ul>\n";
    echo "<li>Is MySQL running?</li>\n";
    echo "<li>Check host/port/credentials</li>\n";
    echo "<li>Check if database 'cvtek' exists</li>\n";
    echo "<li>Check if user 'root' exists</li>\n";
    echo "</ul>\n";
}

?>
