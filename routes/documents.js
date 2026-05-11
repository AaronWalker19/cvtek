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
    const { user_id, nom_fichier, titre, type_fichier, url_fichier, description } = req.body;

    // Validation
    if (!user_id || !nom_fichier || !type_fichier || !url_fichier) {
      return res.status(400).json({ 
        error: "Champs requis manquants: user_id, nom_fichier, type_fichier, url_fichier" 
      });
    }

    // Utiliser le titre comme nom_fichier s'il existe, sinon garder le nom_fichier original
    const finalNomFichier = titre || nom_fichier;

    const stmt = dbModule.prepare(
      `INSERT INTO documents (user_id, nom_fichier, titre, type_fichier, url_fichier, description)
       VALUES (?, ?, ?, ?, ?, ?)`
    );

    const result = stmt.run(user_id, finalNomFichier, titre || null, type_fichier, url_fichier, description || null);

    res.status(201).json({
      id: result.lastID,
      user_id,
      nom_fichier: finalNomFichier,
      titre: titre || null,
      type_fichier,
      url_fichier,
      description,
      version: 1,
      created_at: new Date().toISOString(),
      message: "Document créé avec succès"
    });
  } catch (err) {
    console.error("Erreur POST document:", err);
    res.status(500).json({ error: err.message });
  }
});

// GET un document par ID avec les versions disponibles
router.get("/:id", requireDB, (req, res) => {
  try {
    const { id } = req.params;
    const { version } = req.query; // version spécifique optionnelle

    const stmt = dbModule.prepare("SELECT * FROM documents WHERE id = ?");
    const document = stmt.get(id);

    if (!document) {
      return res.status(404).json({ error: "Document non trouvé" });
    }

    // Récupérer toutes les versions de ce document
    const versionsStmt = dbModule.prepare(
      "SELECT id, version, created_at FROM documents WHERE user_id = ? AND nom_fichier = ? ORDER BY version DESC"
    );
    const versions = versionsStmt.all(document.user_id, document.nom_fichier);

    // Si une version spécifique est demandée
    let currentDocument = document;
    if (version) {
      const versionStmt = dbModule.prepare(
        "SELECT * FROM documents WHERE user_id = ? AND nom_fichier = ? AND version = ?"
      );
      const versionDoc = versionStmt.get(document.user_id, document.nom_fichier, parseInt(version));
      if (versionDoc) {
        currentDocument = versionDoc;
      }
    }

    res.json({
      ...currentDocument,
      versions: versions,
      availableVersions: versions.map(v => ({
        id: v.id,
        version: v.version,
        created_at: v.created_at
      }))
    });
  } catch (err) {
    console.error("Erreur GET document:", err);
    res.status(500).json({ error: err.message });
  }
});

// POST créer une nouvelle version d'un document
router.post("/:id/version", requireDB, (req, res) => {
  try {
    const { id } = req.params;
    const { url_fichier } = req.body;

    if (!url_fichier) {
      return res.status(400).json({ error: "url_fichier requis" });
    }

    // Récupérer le document original
    const originalStmt = dbModule.prepare("SELECT * FROM documents WHERE id = ?");
    const originalDoc = originalStmt.get(id);

    if (!originalDoc) {
      return res.status(404).json({ error: "Document non trouvé" });
    }

    // Trouver la version la plus élevée pour ce document
    const maxVersionStmt = dbModule.prepare(
      "SELECT MAX(version) as max_version FROM documents WHERE user_id = ? AND nom_fichier = ?"
    );
    const maxVersionResult = maxVersionStmt.get(originalDoc.user_id, originalDoc.nom_fichier);
    const newVersion = (maxVersionResult?.max_version || 1) + 1;

    // Créer la nouvelle version
    const insertStmt = dbModule.prepare(
      `INSERT INTO documents (user_id, nom_fichier, titre, type_fichier, url_fichier, description, version)
       VALUES (?, ?, ?, ?, ?, ?, ?)`
    );

    const result = insertStmt.run(
      originalDoc.user_id,
      originalDoc.nom_fichier,
      originalDoc.titre,
      originalDoc.type_fichier,
      url_fichier,
      originalDoc.description,
      newVersion
    );

    res.status(201).json({
      id: result.lastID,
      version: newVersion,
      message: `Nouvelle version ${newVersion}.0 créée avec succès`
    });
  } catch (err) {
    console.error("Erreur POST version:", err);
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
