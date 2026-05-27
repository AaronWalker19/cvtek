<?php
/**
 * check-version-23.php
 * Check real IDs from the interface: version 23, doc 37
 */

// Load .env
if (file_exists(__DIR__ . '/.env')) {
    $lines = file(__DIR__ . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            putenv(trim($key) . '=' . trim($value));
        }
    }
}

require_once __DIR__ . '/db.php';

echo "<h1>Check Version 23 and Document 37</h1>\n";

$db = Database::getConnection();

// Test 1: Version 23 exists?
echo "<h2>1. Version 23 exists?</h2>\n";
$stmt = $db->prepare("SELECT id, id_doc FROM doc_version WHERE id = 23");
$stmt->execute();
$v23 = $stmt->fetch(PDO::FETCH_ASSOC);

if ($v23) {
    echo "OK - Version 23 found (id_doc = " . $v23['id_doc'] . ")<br>\n";
} else {
    echo "FAIL - Version 23 NOT FOUND<br>\n";
    echo "Versions in DB:<br>\n";
    $stmt = $db->prepare("SELECT id, id_doc FROM doc_version ORDER BY id");
    $stmt->execute();
    while ($row = $stmt->fetch()) {
        echo "  Version ID " . $row['id'] . " -> Doc " . $row['id_doc'] . "<br>\n";
    }
    die;
}

// Test 2: Document 37 exists?
echo "<h2>2. Document 37 exists?</h2>\n";
$stmt = $db->prepare("SELECT id, user_id, nom_fichier FROM documents WHERE id = 37");
$stmt->execute();
$doc = $stmt->fetch(PDO::FETCH_ASSOC);

if ($doc) {
    echo "OK - Document 37 found (user_id = " . $doc['user_id'] . ")<br>\n";
} else {
    echo "FAIL - Document 37 NOT FOUND<br>\n";
    echo "Documents in DB:<br>\n";
    $stmt = $db->prepare("SELECT id, user_id, nom_fichier FROM documents ORDER BY id");
    $stmt->execute();
    while ($row = $stmt->fetch()) {
        echo "  Doc ID " . $row['id'] . " (user_id=" . $row['user_id'] . "): " . $row['nom_fichier'] . "<br>\n";
    }
    die;
}

// Test 3: Version 23 linked to Document 37?
echo "<h2>3. Version 23 linked to Document 37?</h2>\n";
if ($v23['id_doc'] == 37) {
    echo "OK - Version 23 is linked to Document 37<br>\n";
} else {
    echo "FAIL - Version 23 is linked to Document " . $v23['id_doc'] . ", not 37<br>\n";
    die;
}

// Test 4: Student user exists?
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

// Test 5: User has email?
echo "<h2>5. User has email?</h2>\n";
if ($student['email']) {
    echo "OK - Email: " . $student['email'] . "<br>\n";
} else {
    echo "FAIL - User has no email<br>\n";
    die;
}

// SUMMARY
echo "<h2 style='color:green'>ALL TESTS PASS!</h2>\n";
echo "Chain: Version 23 -> Doc 37 (user_id=" . $doc['user_id'] . ") -> " . $student['username'] . " (" . $student['email'] . ")<br>\n";
echo "Comment notifications should work!<br>\n";

?>
