<?php
// Connect to TiDB using the DATABASE_URL environment variable
$dbUrl = getenv('DATABASE_URL');

if (!$dbUrl) {
    die("Error: DATABASE_URL environment variable is missing. Please set it in Render.");
}

$dbopts = parse_url($dbUrl);
$host = $dbopts["host"];
$port = isset($dbopts["port"]) ? $dbopts["port"] : 4000;
$user = $dbopts["user"];
$pass = $dbopts["pass"];
$dbname = ltrim($dbopts["path"], '/');

$dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";

// TiDB strictly requires SSL connection parameters
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::MYSQL_ATTR_SSL_CA => '/etc/ssl/certs/ca-certificates.crt', // Standard path in Debian Docker containers
    PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false 
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>