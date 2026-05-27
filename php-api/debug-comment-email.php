<?php
/**
 * debug-comment-email.php
 * Simulates exactly what happens when professor adds a comment
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
require_once __DIR__ . '/Service/EmailService.php';

echo "<h1>Debug Comment Email - Version 23</h1>\n";

$db = Database::getConnection();
$emailService = new EmailService();

echo "<h2>Simulating: Professor 17 adds comment on Version 23</h2>\n";

// This is what CommentController does
$profId = 17;
$docVersionId = 23;
$commentText = "Test comment";

echo "<p>Step 1: Get document from version $docVersionId</p>\n";
$stmt = $db->prepare("SELECT d.id, d.user_id, d.nom_fichier, d.titre FROM documents d INNER JOIN doc_version dv ON dv.id_doc = d.id WHERE dv.id = ?");
$stmt->execute([$docVersionId]);
$doc = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$doc) {
    echo "<p style='color:red'>ERROR: Document not found!</p>\n";
    die;
}
echo "<p style='color:green'>OK: Document found</p>\n";
echo "<pre>Doc: id=" . $doc['id'] . ", user_id=" . $doc['user_id'] . "</pre>\n";

// Get student
echo "<p>Step 2: Get student user (user_id=" . $doc['user_id'] . ")</p>\n";
$stmt = $db->prepare("SELECT id, username, email FROM users WHERE id = ?");
$stmt->execute([$doc['user_id']]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$student) {
    echo "<p style='color:red'>ERROR: Student not found!</p>\n";
    die;
}
echo "<p style='color:green'>OK: Student found</p>\n";
echo "<pre>Student: " . $student['username'] . " (" . $student['email'] . ")</pre>\n";

// Get professor
echo "<p>Step 3: Get professor (id=$profId)</p>\n";
$stmt = $db->prepare("SELECT id, username FROM users WHERE id = ?");
$stmt->execute([$profId]);
$professor = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$professor) {
    echo "<p style='color:red'>ERROR: Professor not found!</p>\n";
    die;
}
echo "<p style='color:green'>OK: Professor found</p>\n";
echo "<pre>Professor: " . $professor['username'] . "</pre>\n";

// Send email
echo "<p>Step 4: Send email</p>\n";
$result = $emailService->sendNewCommentNotification(
    $student['email'],
    $student['username'],
    $professor['username'],
    $doc['titre'] ?: $doc['nom_fichier'],
    $commentText
);

echo "<p>Email result:</p>\n";
echo "<pre>\n";
var_dump($result);
echo "</pre>\n";

if ($result['success']) {
    echo "<p style='color:green'>SUCCESS: Email sent!</p>\n";
} else {
    echo "<p style='color:red'>FAILED: " . ($result['error'] ?? 'Unknown error') . "</p>\n";
}

?>

