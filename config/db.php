<?php
$host = 'localhost';
$dbname = 'complaint_system';
$username = 'root';
$password = '';

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // ALSO create alias
    $pdo = $conn;

} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Personalized Configuration
define('ENROLLMENT', '230210107029');
define('STUDENT_B', 0);
define('STUDENT_S', 29);
define('STUDENT_U', 29);
define('COMPLAINT_DOMAIN', 'Network and Connectivity Issues');
define('AREA_MODEL', 'Zone > Sector > Spot');
define('INITIAL_RESPONSE_SLA', 5); // hours
define('RESOLUTION_SLA', 48); // hours
define('SPECIAL_RULE', 'repeated_complaint_flagging'); // U is odd
define('MANDATORY_REPORT', 'Reopened complaints summary'); // R=5
?>