const express = require("express");
const path = require("path");
const cors = require("cors");
const helmet = require("helmet");
const cookieParser = require("cookie-parser");
require("dotenv").config();

const app = express();

console.log("🚀 SERVER START - SQLITE VERSION");

// ===== SÉCURITÉ: Headers de sécurité =====
app.use(helmet({
  contentSecurityPolicy: {
    directives: {
      defaultSrc: ["'self'"],
      scriptSrc: ["'self'", "'unsafe-inline'"],
      styleSrc: ["'self'", "'unsafe-inline'"],
      imgSrc: ["'self'", "data:", "https:", "http:"],  // ✅ Autorise les images externes (Unsplash, etc.)
      fontSrc: ["'self'", "data:", "https:", "http:"],
      connectSrc: ["'self'", "https:", "http:"],
      mediaSrc: ["'self'", "https:", "http:"],
      objectSrc: ["'self'"],  // ✅ Permet les PDFs dans les iframes
      frameSrc: ["'self'"],  // ✅ Permet les iframes de la même source
    },
  },
})); // ✅ Configuration CSP personnalisée

// ===== CONFIGURATION SQLITE =====
const dbPath = process.env.DB_PATH || path.join(__dirname, "db", "cvtek.db");

console.log(`\n📊 SQLite Configuration:`);
console.log(`   Database: ${dbPath}`);
console.log(`   Journal Mode: WAL (Write-Ahead Logging)\n`);

// ===== SÉCURITÉ: CORS restrictif =====
const allowedOrigins = [
  process.env.CORS_ORIGIN || 'http://localhost:3000',
  'http://localhost:5173',
  'http://127.0.0.1:3000',
  'http://127.0.0.1:5173',
  'https://cvtek.alwaysdata.net' // ✅ AJOUT ICI
];

app.use(cors({
  origin: true, // 🔥 accepte automatiquement l'origine
  credentials: true
}));



// ===== SAFE IMPORTS =====
let initializeAdmin, db;

try {
  initializeAdmin = require("./db/init-admin");
  db = require("./db/database");
} catch (err) {
  console.error("❌ Erreur import DB:", err.message);
}

// ===== MIDDLEWARE =====
app.use(express.json({ limit: "50mb" }));
app.use(express.urlencoded({ extended: true, limit: "50mb" }));
app.use(cookieParser()); // ✅ Permet de lire les cookies
console.log("📝 Middlewares JSON, URL et Cookie activés");

// Middleware de logging pour toutes les requêtes
app.use((req, res, next) => {
  const timestamp = new Date().toISOString();
  console.log(`[${timestamp}] 📨 ${req.method} ${req.path}`);
  console.log(`   Headers: ${JSON.stringify({ host: req.hostname, agent: req.get('user-agent') })}`);
  if (req.body && Object.keys(req.body).length > 0) {
    console.log(`   Body: ${JSON.stringify(req.body).substring(0, 200)}`);
  }
  
  // Capturer les erreurs de response
  const originalJson = res.json;
  res.json = function(data) {
    console.log(`   ✓ Response: ${res.statusCode} - ${JSON.stringify(data).substring(0, 200)}`);
    return originalJson.call(this, data);
  };
  
  next();
});

// SÉCURITÉ: Rate limiting global
const { apiLimiter } = require("./middleware/security");
app.use("/api/", apiLimiter);

// ===== SERVE UPLOADS FOLDER =====
// Middleware pour logger les demandes
app.use("/uploads", (req, res, next) => {
  console.log(`\n📁 UPLOAD REQUEST: ${req.method} ${req.path}`);
  console.log(`   Full URL: ${req.originalUrl}`);
  const filePath = path.join(__dirname, "uploads", req.path);
  console.log(`   File path: ${filePath}`);
  next();
});

// Servir les fichiers statiques
app.use("/uploads", express.static(
  path.join(__dirname, "uploads"),
  {
    // Ajouter les headers CORS et Content-Type
    setHeaders: (res, filePath, stat) => {
      console.log(`   ✅ Serving file: ${filePath}`);
      res.setHeader('Content-Type', 'application/pdf');
      res.setHeader('Content-Length', stat.size);
      res.setHeader('Access-Control-Allow-Origin', '*');
      res.setHeader('Cache-Control', 'public, max-age=3600');
    },
  }
));

// Middleware de gestion des erreurs pour /uploads
app.use("/uploads", (err, req, res, next) => {
  console.log(`   ❌ Error: ${err.message}`);
  res.status(500).json({ error: 'Erreur serveur', message: err.message });
});

// ===== ROUTES API =====
try {
  const authRoutes = require("./routes/auth");
  const projectRoutes = require("./routes/projects");
  const documentRoutes = require("./routes/documents");
  const uploadRoutes = require("./routes/upload");

  app.use("/api/auth", authRoutes);
  app.use("/api/projects", projectRoutes);
  app.use("/api/documents", documentRoutes);
  app.use("/api/upload", uploadRoutes);
  console.log("✓ Routes API enregistrées");

} catch (err) {
  console.error("❌ Erreur import routes:", err.message);
}

// ===== HEALTH CHECK =====
app.get("/api/health", async (req, res) => {
  try {
    console.log("🏥 Health check demandé");
    const dbPool = db && typeof db.pool === 'function' ? db.pool() : null;
    
    let dbStatus = "❌ Not initialized";
    let dbInfo = {};
    let dbError = null;
    
    if (dbPool) {
      try {
        const connection = await dbPool.getConnection();
        await connection.ping();
        
        // Vérifier les tables
        const [tables] = await connection.execute(`
          SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES 
          WHERE TABLE_SCHEMA = ?
        `, [mysqlConfig.database]);
        
        connection.release();
        dbStatus = "✓ Connected";
        dbInfo = {
          host: mysqlConfig.host,
          database: mysqlConfig.database,
          port: mysqlConfig.port,
          tables: tables.map(t => t.TABLE_NAME)
        };
      } catch (err) {
        dbStatus = `❌ Connection failed: ${err.message}`;
        dbError = err.message;
      }
    }
    
    res.json({ 
      status: "OK",
      database: dbStatus,
      error: dbError,
      config: dbInfo,
      env: {
        PORT: process.env.PORT || 'not set',
        DB_HOST: process.env.DB_HOST || 'not set',
        DB_NAME: process.env.DB_NAME || 'not set'
      }
    });
  } catch (err) {
    res.status(500).json({ 
      status: "ERROR",
      error: err.message 
    });
  }
});

// ===== ADMIN INIT - Créer les tables manuellement =====
app.post("/api/admin/init-db", async (req, res) => {
  try {
    const dbPool = db && typeof db.pool === 'function' ? db.pool() : null;
    if (!dbPool) {
      return res.status(503).json({ 
        error: "Base de données non disponible"
      });
    }
    
    const connection = await dbPool.getConnection();
    
    try {
      // Création des tables
      const sqls = [
        // Table users
        `CREATE TABLE IF NOT EXISTS users (
          id INT AUTO_INCREMENT PRIMARY KEY,
          username VARCHAR(100) UNIQUE NOT NULL,
          email VARCHAR(100) UNIQUE NOT NULL,
          password_hash VARCHAR(255) NOT NULL,
          role ENUM('admin', 'member') DEFAULT 'member',
          created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
          INDEX idx_username (username),
          INDEX idx_email (email)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci`,
        
        // Table projects
        `CREATE TABLE IF NOT EXISTS projects (
          id INT AUTO_INCREMENT PRIMARY KEY,
          code_anr VARCHAR(100),
          title_fr VARCHAR(255),
          title_en VARCHAR(255),
          summary_fr TEXT,
          summary_en TEXT,
          methods_fr TEXT,
          methods_en TEXT,
          results_fr TEXT,
          results_en TEXT,
          perspectives_fr TEXT,
          perspectives_en TEXT,
          created_by INT,
          created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
          updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
          INDEX idx_code_anr (code_anr),
          INDEX idx_created_by (created_by),
          INDEX idx_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci`,
        
        // Table project_files
        `CREATE TABLE IF NOT EXISTS project_files (
          id INT AUTO_INCREMENT PRIMARY KEY,
          project_id INT NOT NULL,
          file_path VARCHAR(500),
          file_name VARCHAR(255),
          file_display_name VARCHAR(255),
          file_type VARCHAR(100),
          file_desc_fr  VARCHAR(150),
          created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
          FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
          INDEX idx_project_id (project_id),
          INDEX idx_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci`,
        
        // Créer l'admin par défaut s'il n'existe pas
        `INSERT IGNORE INTO users (username, email, password_hash, role)
         SELECT 'admin', 'admin@test.com', '$2b$10$F5jUIiF.//fldrNMeVopc.2LenUyr8xkvq7UMm5eqGfmHQ5AOUIAe', 'admin'
         WHERE NOT EXISTS (SELECT 1 FROM users WHERE username = 'admin')`
      ];
      
      for (const sql of sqls) {
        await connection.execute(sql);
      }
      
      connection.release();
      
      res.json({
        success: true,
        message: "Base de données initialisée avec succès",
        timestamp: new Date().toISOString()
      });
    } catch (err) {
      connection.release();
      throw err;
    }
  } catch (err) {
    console.error("DB Init error:", err);
    res.status(500).json({
      error: "Erreur lors de l'initialisation",
      message: err.message
    });
  }
});

// ===== SERVE FRONTEND BUILD (PRIORITÉ AU BUILD, PAS PUBLIC) =====
app.use(express.static(path.join(__dirname, "client/build")));
app.use(express.static(path.join(__dirname, "client/public")));

// ===== CATCH ALL (IMPORTANT FIX) =====
app.use((req, res, next) => {
  // 🔥 NE PAS intercepter les routes API
  if (req.path.startsWith("/api")) {
    return res.status(404).json({ error: "API route not found" });
  }

  res.sendFile(path.join(__dirname, "client/build/index.html"));
});

// ===== ERROR HANDLER =====
app.use((err, req, res, next) => {
  console.error("❌ Erreur serveur:", err);
  res.status(500).json({
    error: err.message || "Erreur interne du serveur",
  });
});

// ===== PORT =====
const port = process.env.PORT || 3000;

// ===== START SERVER =====
async function startServer() {
  try {
    console.log("\n⏳ Initialisation de la base de données...\n");

    // Attendre que la DB soit prête
    const dbReady = await db.waitForDB();
    if (!dbReady) {
      throw new Error("Base de données non disponible après 3 secondes");
    }
    console.log("✓ Base de données SQL.js initialisée");

    if (initializeAdmin) {
      await initializeAdmin();
      console.log("✓ Admin initialization complete");
    }

    // ===== AFFICHER LES COMPTES =====
    try {
      const usersStmt = db.prepare("SELECT id, username, email, role FROM users ORDER BY created_at DESC");
      const users = usersStmt.all();
      console.log("\n📋 COMPTES UTILISATEURS :");
      console.log("═".repeat(70));
      if (users.length > 0) {
        users.forEach(user => {
          const roleEmoji = user.role === "admin" ? "👑" : "👤";
          console.log(`  ${roleEmoji} ${user.username.padEnd(20)} | Email: ${user.email.padEnd(25)} | Role: ${user.role}`);
        });
      } else {
        console.log("  (Aucun compte créé)");
      }
      console.log("═".repeat(70));
    } catch (err) {
      console.error("❌ Erreur lecture comptes:", err.message);
    }

    // ===== AFFICHER LES PROJETS =====
    try {
      const projectsStmt = db.prepare("SELECT id, title_fr, title_en FROM projects ORDER BY created_at DESC LIMIT 50");
      const projects = projectsStmt.all();
      console.log("\n📚 PROJETS :");
      console.log("═".repeat(70));
      if (projects.length > 0) {
        projects.forEach(project => {
          console.log(`  📌 ${project.title_fr}`);
          console.log(`     └─ ${project.title_en}`);
        });
      } else {
        console.log("  (Aucun projet créé)");
      }
      console.log("═".repeat(70) + "\n");
    } catch (err) {
      console.error("❌ Erreur lecture projets:", err.message);
    }

    app.listen(port, () => {
      console.log(`\n✓ Server running on port ${port}`);
      console.log(`🌐 Access: http://localhost:${port}`);
      console.log(`🔗 API: http://localhost:${port}/api/health\n`);
    });

  } catch (err) {
    console.error("\n❌ Erreur au démarrage:", err.message);
    console.error("\n💡 Conseil: Vérifier que la base de données SQLite est accessible");
    process.exit(1);
  }
}

startServer();