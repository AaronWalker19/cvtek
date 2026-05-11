const fs = require('fs');
const path = require('path');
const initSqlJs = require('sql.js');

const dbPath = process.env.DB_PATH || path.join(__dirname, 'cvtek.db');

async function runMigration() {
  try {
    console.log('🔧 Migration: Ajout de la colonne titre...');
    console.log('📂 Chemin DB:', dbPath);
    
    const SQL = await initSqlJs();
    
    // Charger la base de données existante
    if (!fs.existsSync(dbPath)) {
      console.error('❌ Le fichier DB n\'existe pas:', dbPath);
      process.exit(1);
    }
    
    const fileBuffer = fs.readFileSync(dbPath);
    const db = new SQL.Database(fileBuffer);
    
    // Vérifier si la colonne existe déjà
    let hasTitle = false;
    try {
      const result = db.exec("PRAGMA table_info(documents)");
      if (result.length > 0) {
        hasTitle = result[0].values.some(row => row[1] === 'titre');
      }
    } catch (err) {
      console.error('Erreur lors de la vérification:', err.message);
    }
    
    if (hasTitle) {
      console.log('✅ La colonne titre existe déjà.');
    } else {
      // Ajouter la colonne titre
      try {
        db.run('ALTER TABLE documents ADD COLUMN titre TEXT;');
        console.log('✅ Colonne titre ajoutée avec succès!');
      } catch (err) {
        console.error('❌ Erreur lors de l\'ajout:', err.message);
        throw err;
      }
    }
    
    // Sauvegarder la base de données
    const data = db.export();
    const buffer = Buffer.from(data);
    fs.writeFileSync(dbPath, buffer);
    
    db.close();
    console.log('✅ Migration complétée!');
    console.log('💾 Base de données sauvegardée');
    
  } catch (err) {
    console.error('❌ Erreur lors de la migration:', err.message);
    process.exit(1);
  }
}

runMigration();
