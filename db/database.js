const fs = require("fs");
const path = require("path");
const initSqlJs = require("sql.js");

// Configuration SQLite
const dbPath = process.env.DB_PATH || path.join(__dirname, "cvtek.db");
const dbDir = path.dirname(dbPath);

// Créer le répertoire s'il n'existe pas
if (!fs.existsSync(dbDir)) {
  fs.mkdirSync(dbDir, { recursive: true });
}

let db = null;
let SQL = null;

/**
 * Initialise la connexion SQLite avec sql.js
 */
async function initializeDB() {
  try {
    SQL = await initSqlJs();
    
    if (fs.existsSync(dbPath)) {
      const fileBuffer = fs.readFileSync(dbPath);
      db = new SQL.Database(fileBuffer);
    } else {
      db = new SQL.Database();
    }
    
    console.log(`✅ Connexion sql.js réussie: ${dbPath}`);
    
    // Créer les tables
    createTables();
    
    // Sauvegarder la base de données
    saveDatabase();
  } catch (err) {
    console.error("❌ Erreur connexion sql.js:", err.message);
    throw err;
  }
}

/**
 * Crée les tables si elles n'existent pas
 */
function createTables() {
  if (!db) return;

  try {
    // Table users
    db.run(`
      CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT UNIQUE NOT NULL,
        email TEXT UNIQUE NOT NULL,
        password_hash TEXT,
        name TEXT,
        role TEXT DEFAULT 'member' CHECK(role IN ('admin', 'member')),
        is_active BOOLEAN DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        activated_at DATETIME NULL
      )
    `);

    // Table user_invitations
    db.run(`
      CREATE TABLE IF NOT EXISTS user_invitations (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        email TEXT NOT NULL,
        token TEXT UNIQUE NOT NULL,
        token_hash TEXT,
        invited_by INTEGER,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        expires_at DATETIME NOT NULL,
        activated_at DATETIME NULL,
        FOREIGN KEY (invited_by) REFERENCES users(id) ON DELETE SET NULL
      )
    `);

    // Table documents (pour les fichiers étudiants)
    db.run(`
      CREATE TABLE IF NOT EXISTS documents (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        nom_fichier TEXT NOT NULL,
        titre TEXT,
        type_fichier TEXT NOT NULL CHECK(type_fichier IN ('cv', 'portfolio', 'autre')),
        url_fichier TEXT NOT NULL,
        description TEXT,
        version INTEGER DEFAULT 1,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
      )
    `);

    // Table password_resets
    db.run(`
      CREATE TABLE IF NOT EXISTS password_resets (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        email TEXT NOT NULL,
        token_hash TEXT UNIQUE NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        expires_at DATETIME NOT NULL,
        reset_at DATETIME NULL,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
      )
    `);

    // Table projects
    db.run(`
      CREATE TABLE IF NOT EXISTS projects (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        code_anr TEXT,
        title_fr TEXT,
        title_en TEXT,
        summary_fr TEXT,
        summary_en TEXT,
        methods_fr TEXT,
        methods_en TEXT,
        results_fr TEXT,
        results_en TEXT,
        perspectives_fr TEXT,
        perspectives_en TEXT,
        created_by INTEGER,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
      )
    `);

    // Table project_contents
    db.run(`
      CREATE TABLE IF NOT EXISTS project_contents (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        project_id INTEGER NOT NULL,
        title_fr TEXT,
        title_en TEXT,
        content_fr TEXT,
        content_en TEXT,
        position INTEGER DEFAULT 1,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
      )
    `);

    // Table project_files
    db.run(`
      CREATE TABLE IF NOT EXISTS project_files (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        project_id INTEGER NOT NULL,
        file_path TEXT,
        file_name TEXT,
        file_display_name TEXT,
        file_type TEXT,
        file_desc_fr TEXT,
        file_desc_en TEXT,
        is_present_image BOOLEAN DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
      )
    `);

    // Table articles
    db.run(`
      CREATE TABLE IF NOT EXISTS articles (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        title_fr TEXT,
        title_en TEXT,
        summary_fr TEXT,
        summary_en TEXT,
        content_fr TEXT,
        content_en TEXT,
        status TEXT DEFAULT 'draft' CHECK(status IN ('draft', 'published')),
        created_by INTEGER,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
      )
    `);

    // Table gallery_items
    db.run(`
      CREATE TABLE IF NOT EXISTS gallery_items (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        title_fr TEXT,
        title_en TEXT,
        description_fr TEXT,
        description_en TEXT,
        image_path TEXT,
        display_name TEXT,
        position INTEGER DEFAULT 1,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
      )
    `);

    // Table hal_documents
    db.run(`
      CREATE TABLE IF NOT EXISTS hal_documents (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        hal_id TEXT UNIQUE,
        title TEXT,
        authors TEXT,
        abstract TEXT,
        publication_date DATE,
        source TEXT,
        url TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
      )
    `);

    // Table user_participation
    db.run(`
      CREATE TABLE IF NOT EXISTS user_participation (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        project_id INTEGER NOT NULL,
        user_id INTEGER NOT NULL,
        added_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
      )
    `);

    // Table comments (Commentaires validés sur les documents)
    db.run(`
      CREATE TABLE IF NOT EXISTS comments (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        document_id INTEGER NOT NULL,
        prof_id INTEGER NOT NULL,
        contenu TEXT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (document_id) REFERENCES documents(id) ON DELETE CASCADE,
        FOREIGN KEY (prof_id) REFERENCES users(id) ON DELETE CASCADE
      )
    `);

    // Table comments_temp (Commentaires temporaires sur les documents)
    db.run(`
      CREATE TABLE IF NOT EXISTS comments_temp (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        document_id INTEGER NOT NULL,
        prof_id INTEGER NOT NULL,
        contenu TEXT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (document_id) REFERENCES documents(id) ON DELETE CASCADE,
        FOREIGN KEY (prof_id) REFERENCES users(id) ON DELETE CASCADE
      )
    `);

    console.log("✅ Tables SQLite créées/vérifiées");
  } catch (err) {
    console.error("❌ Erreur création tables:", err.message);
    throw err;
  }
}

/**
 * Sauvegarde la base de données sur le disque
 */
function saveDatabase() {
  if (!db) return;
  
  try {
    const data = db.export();
    const buffer = Buffer.from(data);
    fs.writeFileSync(dbPath, buffer);
  } catch (err) {
    console.error("❌ Erreur sauvegarde DB:", err.message);
  }
}

/**
 * Récupère la connexion DB
 */
function getDatabase() {
  if (!db) {
    throw new Error("Database not initialized");
  }
  return db;
}

/**
 * Ferme la connexion DB
 */
function closeDatabase() {
  if (db) {
    saveDatabase();
    db.close();
    db = null;
  }
}

/**
 * Prépare et exécute une requête
 */
function prepare(sql) {
  return {
    run: (...params) => {
      try {
        db.run(sql, params);
        saveDatabase();
        return { changes: 1 };
      } catch (err) {
        console.error("❌ Erreur prepare().run():", err.message);
        throw err;
      }
    },
    get: (...params) => {
      try {
        const stmt = db.prepare(sql);
        stmt.bind(params);
        if (stmt.step()) {
          const result = stmt.getAsObject();
          stmt.free();
          return result;
        }
        stmt.free();
        return undefined;
      } catch (err) {
        console.error("❌ Erreur prepare().get():", err.message);
        throw err;
      }
    },
    all: (...params) => {
      try {
        const stmt = db.prepare(sql);
        stmt.bind(params);
        const results = [];
        while (stmt.step()) {
          results.push(stmt.getAsObject());
        }
        stmt.free();
        return results;
      } catch (err) {
        console.error("❌ Erreur prepare().all():", err.message);
        throw err;
      }
    }
  };
}

// Initialiser au démarrage (async)
let dbReady = false;

initializeDB().then(() => {
  dbReady = true;
}).catch(err => {
  console.error("❌ Impossible d'initialiser la BD:", err.message);
});

/**
 * Attendre que la DB soit prête
 */
async function waitForDB(maxAttempts = 30) {
  for (let i = 0; i < maxAttempts; i++) {
    if (dbReady && db) {
      return true;
    }
    await new Promise(resolve => setTimeout(resolve, 100));
  }
  return false;
}

module.exports = {
  getDatabase,
  closeDatabase,
  prepare,
  db: () => db,
  isReady: () => dbReady,
  waitForDB,
};
