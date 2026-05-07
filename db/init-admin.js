const bcrypt = require("bcryptjs");
const dbModule = require("./database");

const SALT_ROUNDS = 10;

/**
 * Initialise le compte admin par défaut s'il n'existe pas
 */
const initializeAdmin = async () => {
  const adminUsername = process.env.ADMIN_USERNAME || "admin";
  const adminEmail = process.env.ADMIN_EMAIL || "admin@test.com";
  const adminPassword = process.env.ADMIN_PASSWORD || "admin123";

  try {
    // Attendre que la DB soit prête
    await dbModule.waitForDB();
    
    const db = dbModule.getDatabase();

    // Vérifier si l'admin existe déjà
    const checkStmt = dbModule.prepare(
      "SELECT id FROM users WHERE username = ? LIMIT 1"
    );
    const existingUser = checkStmt.get(adminUsername);

    if (existingUser) {
      console.log(`✓ Admin existe déjà: ${adminUsername}`);
      return;
    }

    // Hash le mot de passe
    const passwordHash = await bcrypt.hash(adminPassword, SALT_ROUNDS);

    // Créer l'admin
    const stmt = dbModule.prepare(
      `INSERT INTO users (username, email, password_hash, role)
       VALUES (?, ?, ?, ?)`
    );
    
    stmt.run(adminUsername, adminEmail, passwordHash, "admin");

    console.log(
      `✓ Admin créé avec succès\n  Username: ${adminUsername}\n  Email: ${adminEmail}\n  Password: ${adminPassword}`
    );
  } catch (error) {
    console.error("❌ Erreur initialization admin:", error.message);
  }
};

module.exports = initializeAdmin;
