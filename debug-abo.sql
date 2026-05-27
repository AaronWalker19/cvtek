-- Vérifier la table abonnement
SELECT 'Abonnements pour Student 16:' as info;
SELECT a.*, u.username as prof_name, u.email FROM abonnement a 
LEFT JOIN users u ON a.id_prof = u.id 
WHERE a.id_user = 16;

SELECT '' as blank;
SELECT 'Tous les abonnements:' as info;
SELECT * FROM abonnement;

SELECT '' as blank;
SELECT 'Users table (pour vérifier les emails):' as info;
SELECT id, username, email, role FROM users WHERE role = 'professor' OR id IN (SELECT id_prof FROM abonnement);
