<?php
/**
 * simple-check.php
 * Simple verification without Repository classes
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>Version 22 Check</h1>\n";

try {
    require_once __DIR__ . '/db.php';
    echo "<p>DB loaded OK</p>\n";
    
    $db = Database::getConnection();
    echo "<p>DB connection OK</p>\n";
    
} catch (Exception $e) {
    echo "<p style='color:red'>ERROR: " . $e->getMessage() . "</p>\n";
    die;
}

// Test 1
echo "<h2>1. Version 22 exists?</h2>\n";
$stmt = $db->prepare("SELECT id, id_doc FROM doc_version WHERE id = 22");
$stmt->execute();
$v22 = $stmt->fetch(PDO::FETCH_ASSOC);

if ($v22) {
    echo "OK - Version 22 found (id_doc = " . $v22['id_doc'] . ")<br>\n";
} else {
    echo "FAIL - Version 22 NOT FOUND<br>\n";
    die;
}

// Test 2
echo "<h2>2. Document 36 exists?</h2>\n";
$stmt = $db->prepare("SELECT id, user_id, nom_fichier FROM documents WHERE id = 36");
$stmt->execute();
$doc = $stmt->fetch(PDO::FETCH_ASSOC);

if ($doc) {
    echo "OK - Document 36 found (user_id = " . $doc['user_id'] . ")<br>\n";
} else {
    echo "FAIL - Document 36 NOT FOUND<br>\n";
    die;
}

// Test 3
echo "<h2>3. Version 22 linked to Document 36?</h2>\n";
if ($v22['id_doc'] == $doc['id']) {
    echo "OK - Version 22 is linked to Document 36<br>\n";
} else {
    echo "FAIL - Version 22 is linked to Document " . $v22['id_doc'] . ", not 36<br>\n";
    die;
}

// Test 4
echo "<h2>4. Student user exists?</h2>\n";
$studentId = $doc['user_id'];
$stmt = $db->prepare("SELECT id, username, email FROM users WHERE id = ?");
$stmt->execute([$studentId]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);

if ($student) {
    echo "OK - User " . $studentId . " found: " . $student['username'] . "<br>\n";
} else {
    echo "FAIL - User " . $studentId . " NOT FOUND<br>\n";
    die;
}

// Test 5
echo "<h2>5. User has email?</h2>\n";
if ($student['email']) {
    echo "OK - Email: " . $student['email'] . "<br>\n";
} else {
    echo "FAIL - User has no email<br>\n";
    die;
}

// SUMMARY
echo "<h2 style='color:green'>ALL TESTS PASS!</h2>\n";
echo "Chain: Version 22 -> Doc 36 (user_id=" . $doc['user_id'] . ") -> " . $student['username'] . " (" . $student['email'] . ")<br>\n";
echo "Comment notifications should work!<br>\n";

?>

