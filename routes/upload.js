const express = require('express');
const multer = require('multer');
const path = require('path');
const fs = require('fs');

const router = express.Router();

// Créer le dossier uploads s'il n'existe pas
const uploadsDir = path.join(__dirname, '../uploads');
if (!fs.existsSync(uploadsDir)) {
  fs.mkdirSync(uploadsDir, { recursive: true });
}

// Configuration multer
const storage = multer.diskStorage({
  destination: (req, file, cb) => {
    cb(null, uploadsDir);
  },
  filename: (req, file, cb) => {
    // Créer un nom unique avec timestamp
    const uniqueSuffix = Date.now() + '-' + Math.round(Math.random() * 1E9);
    const ext = path.extname(file.originalname);
    const name = path.basename(file.originalname, ext);
    cb(null, name + '-' + uniqueSuffix + ext);
  }
});

const upload = multer({
  storage: storage,
  limits: { fileSize: 50 * 1024 * 1024 }, // 50MB max
  fileFilter: (req, file, cb) => {
    // Accepter tous les types de fichiers
    cb(null, true);
  }
});

// POST upload un fichier
router.post('/', upload.single('file'), (req, res) => {
  try {
    console.log('\n📤 UPLOAD REQUEST');
    console.log(`   File: ${req.file?.originalname}`);
    console.log(`   Size: ${req.file?.size} bytes`);
    
    if (!req.file) {
      console.log('   ❌ No file provided');
      return res.status(400).json({ error: 'Aucun fichier fourni' });
    }

    const fileUrl = `/uploads/${req.file.filename}`;
    
    console.log(`   ✅ Saved as: ${req.file.filename}`);
    console.log(`   URL: ${fileUrl}`);

    res.json({
      success: true,
      filename: req.file.filename,
      originalname: req.file.originalname,
      url: fileUrl,
      size: req.file.size,
      message: 'Fichier uploadé avec succès'
    });
  } catch (err) {
    console.error('❌ Erreur upload fichier:', err);
    res.status(500).json({ error: err.message });
  }
});

module.exports = router;
