#!/usr/bin/env node

/**
 * Script pour réinitialiser la base de données SQLite
 * Garde les admins et la structure, efface les données spécifiques au site
 */

const Database = require('better-sqlite3');
const path = require('path');

const dbPath = process.env.DB_PATH || path.join(__dirname, 'db', 'cvtek.db');
console.log(\?? Connexion à la BD SQLite: \\);

try {
  const db = new Database(dbPath);
  
  // Récupérer les admins avant la réinitialisation
  const admins = db.prepare('SELECT id, email, username FROM users WHERE role = \"admin\"').all();
  console.log(\\n?? Admins trouvés: \\);
  admins.forEach(admin => {
    console.log(\  - \ (\)\);
  });

  // Supprimer les tables de contenu (garder la structure des users)
  const tablesToClear = [
    'user_participation',
    'project_files',
    'project_contents',
    'projects',
    'articles',
    'gallery_items',
    'hal_documents',
    'password_resets'
  ];

  console.log(\\n?? Suppression des tables de contenu...\);
  for (const table of tablesToClear) {
    try {
      db.prepare(\DELETE FROM \\).run();
      console.log(\  ? \\);
    } catch (err) {
      console.log(\  ??  \ (n'existe pas ou erreur)\);
    }
  }

  // Supprimer les utilisateurs non-admin
  const deleteResult = db.prepare('DELETE FROM users WHERE role = \"member\"').run();
  console.log(\\n???  \ utilisateur(s) supprimé(s)\);

  // Supprimer les invitations
  const deleteInvites = db.prepare('DELETE FROM user_invitations').run();
  console.log(\?? \ invitation(s) supprimée(s)\);

  console.log(\\n? Base de données réinitialisée avec succès!\);
  console.log(\\n?? Admins restants:\);
  const adminsAfter = db.prepare('SELECT id, email, username FROM users WHERE role = \"admin\"').all();
  adminsAfter.forEach(admin => {
    console.log(\  - \ (\)\);
  });

  db.close();
} catch (err) {
  console.error(\\n? Erreur: \\);
  process.exit(1);
}
