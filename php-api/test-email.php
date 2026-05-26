<?php

/**
 * test-email.php
 * Script de test pour le service d'email
 * 
 * Utilisation: 
 * - Accéder à http://localhost/cvtek/php-api/test-email.php?action=test
 * - Ou utiliser: curl http://localhost/cvtek/php-api/test-email.php?action=test
 */

require_once __DIR__ . '/Service/EmailService.php';
require_once __DIR__ . '/helpers.php';

header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? '';
$emailService = new EmailService();

try {
    switch ($action) {
        case 'test':
            // Test simple d'envoi
            $result = $emailService->testConnection();
            echo json_encode([
                'success' => $result,
                'message' => $result ? 'Email de test envoyé avec succès' : 'Échec d\'envoi d\'email'
            ]);
            break;
            
        case 'test-document':
            // Test de notification de document
            $studentEmail = $_GET['student_email'] ?? 'student@example.com';
            $studentName = $_GET['student_name'] ?? 'Étudiant Test';
            $documentName = $_GET['document_name'] ?? 'Rapport Final';
            $profEmails = ['prof@example.com', 'prof2@example.com'];
            
            $result = $emailService->sendNewDocumentNotification(
                $studentEmail,
                $studentName,
                $documentName,
                $profEmails
            );
            
            echo json_encode([
                'success' => $result,
                'message' => $result ? 'Notification de document envoyée' : 'Échec de l\'envoi',
                'test_data' => [
                    'student_email' => $studentEmail,
                    'student_name' => $studentName,
                    'document_name' => $documentName,
                    'prof_emails' => $profEmails
                ]
            ]);
            break;
            
        case 'test-comment':
            // Test de notification de commentaire
            $studentEmail = $_GET['student_email'] ?? 'student@example.com';
            $studentName = $_GET['student_name'] ?? 'Étudiant Test';
            $profName = $_GET['prof_name'] ?? 'Professeur Test';
            $docTitle = $_GET['doc_title'] ?? 'Rapport Final';
            $comment = $_GET['comment'] ?? 'Excellent travail, bien à revoir en p.5';
            
            $result = $emailService->sendNewCommentNotification(
                $studentEmail,
                $studentName,
                $profName,
                $docTitle,
                $comment
            );
            
            echo json_encode([
                'success' => $result,
                'message' => $result ? 'Notification de commentaire envoyée' : 'Échec de l\'envoi',
                'test_data' => [
                    'student_email' => $studentEmail,
                    'student_name' => $studentName,
                    'prof_name' => $profName,
                    'doc_title' => $docTitle,
                    'comment' => $comment
                ]
            ]);
            break;
            
        case 'config':
            // Afficher la configuration (sauf les mots de passe)
            echo json_encode([
                'from_email' => 'benoitccasibio@gmail.com',
                'smtp_host' => 'smtp.gmail.com',
                'smtp_port' => 587,
                'encryption' => 'STARTTLS',
                'status' => 'Configuré'
            ]);
            break;
            
        default:
            echo json_encode([
                'error' => 'Action non reconnue',
                'available_actions' => [
                    'test' => 'Test d\'envoi simple',
                    'test-document' => 'Test notification de document',
                    'test-comment' => 'Test notification de commentaire',
                    'config' => 'Afficher la configuration'
                ]
            ]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
