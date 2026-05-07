const express = require("express");
const router = express.Router();
const { check, validationResult } = require("express-validator");
let db;

// ========================================
// Routes pour les projets - � d�velopper
// ========================================

try {
  const dbModule = require("../db/database");
  db = dbModule.getDatabase();
  console.log("✅ DB chargée");
} catch (err) {
  console.error("❌ DB FAILED:", err.message);
  db = null;
}

const { authenticateToken, requireAdmin } = require("../middleware/auth");

// Middleware pour v�rifier la disponibilit� de la DB
const requireDB = (req, res, next) => {
  if (!db) {
    return res.status(503).json({ 
      error: "Service indisponible",
      message: "Base de donn�es non initialis�e"
    });
  }
  next();
};

// TODO: Ajouter les routes CRUD ici
// - GET /api/projects
// - GET /api/projects/:id
// - POST /api/projects
// - PUT /api/projects/:id
// - DELETE /api/projects/:id

module.exports = router;
