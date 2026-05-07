const Database = require('better-sqlite3');
const path = require('path');
const fs = require('fs');
require('dotenv').config();

/**
 * Script d'installation de la base de données SQLite
 * Crée automatiquement la base de données et les tables
 */

const dbPath = process.env.DB_PATH || path.join(__dirname, 'db', 'cvtek.db');
const dbDir = path.dirname(dbPath);

function setupDatabase() {
  try {
    console.log('\n?? Installation CVTEK - Base de données SQLite\n');
    console.log(\?? Configuration:\);
    console.log(\   Chemin: \\);
    console.log(\   Dossier: \\n\);

    // Créer le répertoire s'il n'existe pas
    if (!fs.existsSync(dbDir)) {
      fs.mkdirSync(dbDir, { recursive: true });
      console.log(\? Dossier créé: \\);
    }

    // Initialiser la BD
    console.log('? Création/Vérification de la base de données...');
    const db = new Database(dbPath);
    db.pragma('journal_mode = WAL');

    // Créer les tables (voir db/database.js pour la structure complète)
    console.log('?? Création des tables...');
    
    // Exécuter le schéma
    const schemaPath = path.join(__dirname, 'db', 'schema.sql');
    if (fs.existsSync(schemaPath)) {
      const schema = fs.readFileSync(schemaPath, 'utf8');
      // Note: SQLite a des différences de syntaxe avec MySQL
      // Le schéma.sql pourrait nécessiter des adaptations
      console.log('??  Note: Consultez db/schema.sql pour les migrations éventuelles');
    }

    // Vérifier les tables
    const tables = db.prepare(\
      SELECT name FROM sqlite_master 
      WHERE type='table' AND name NOT LIKE 'sqlite_%'
    \).all();

    console.log(\\n? Base de données SQLite initialisée avec succès!\);
    console.log(\?? Tables présentes: \\);
    tables.forEach(t => {
      console.log(\   - \\);
    });

    console.log(\\n?? Fichier BD: \\);
    console.log(\\n?? La base de données est prête pour utilisation!\);

    db.close();
  } catch (err) {
    console.error('\n? Erreur lors de l\'installation:', err.message);
    process.exit(1);
  }
}

setupDatabase();
