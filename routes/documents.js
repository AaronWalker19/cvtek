const express = require("express");
const router = express.Router();

// ========================================
// Routes pour les documents (fichiers étudiants)
// ========================================

let dbModule;
try {
  dbModule = require("../db/database");
  console.log("✅ DB Module chargé (documents)");
} catch (err) {
  console.error("❌ DB Module FAILED (documents):", err.message);
  dbModule = null;
}

// Middleware pour vérifier la disponibilité de la DB
const requireDB = (req, res, next) => {
  if (!dbModule || !dbModule.db()) {
    return res.status(503).json({ 
      error: "Service indisponible",
      message: "Base de données non initialisée"
    });
  }
  next();
};

// GET tous les documents de l'utilisateur connecté (avec compte de commentaires)
router.get("/", requireDB, (req, res) => {
  try {
    const userId = req.query.user_id;
    
    if (!userId) {
      return res.status(400).json({ error: "user_id requis" });
    }

    const stmt = dbModule.prepare(
      "SELECT * FROM documents WHERE user_id = ? ORDER BY created_at DESC"
    );
    const documents = stmt.all(userId);

    // Ajouter le nombre de commentaires pour chaque document
    const documentsWithComments = documents.map(doc => {
      try {
        const commentsStmt = dbModule.prepare(
          "SELECT COUNT(*) as count FROM comments WHERE document_id = ?"
        );
        const commentCount = commentsStmt.get(doc.id);
        return {
          ...doc,
          comment_count: commentCount?.count || 0
        };
      } catch (err) {
        return {
          ...doc,
          comment_count: 0
        };
      }
    });

    res.json(documentsWithComments);
  } catch (err) {
    console.error("Erreur GET documents:", err);
    res.status(500).json({ error: err.message });
  }
});

// POST créer un nouveau document
router.post("/", requireDB, (req, res) => {
  try {
    const { user_id, nom_fichier, type_fichier, url_fichier, description } = req.body;

    // Validation
    if (!user_id || !nom_fichier || !type_fichier || !url_fichier) {
      return res.status(400).json({ 
        error: "Champs requis manquants: user_id, nom_fichier, type_fichier, url_fichier" 
      });
    }

    const stmt = dbModule.prepare(
      `INSERT INTO documents (user_id, nom_fichier, type_fichier, url_fichier, description)
       VALUES (?, ?, ?, ?, ?)`
    );

    const result = stmt.run(user_id, nom_fichier, type_fichier, url_fichier, description || null);

    res.status(201).json({
      id: result.lastID,
      user_id,
      nom_fichier,
      type_fichier,
      url_fichier,
      description,
      created_at: new Date().toISOString(),
      message: "Document créé avec succès"
    });
  } catch (err) {
    console.error("Erreur POST document:", err);
    res.status(500).json({ error: err.message });
  }
});

// GET un document par ID
router.get("/:id", requireDB, (req, res) => {
  try {
    const { id } = req.params;

    const stmt = dbModule.prepare("SELECT * FROM documents WHERE id = ?");
    const document = stmt.get(id);

    if (!document) {
      return res.status(404).json({ error: "Document non trouvé" });
    }

    res.json(document);
  } catch (err) {
    console.error("Erreur GET document:", err);
    res.status(500).json({ error: err.message });
  }
});

// DELETE un document
router.delete("/:id", requireDB, (req, res) => {
  try {
    const { id } = req.params;

    const stmt = dbModule.prepare("DELETE FROM documents WHERE id = ?");
    stmt.run(id);

    res.json({ message: "Document supprimé avec succès" });
  } catch (err) {
    console.error("Erreur DELETE document:", err);
    res.status(500).json({ error: err.message });
  }
});

module.exports = router;
