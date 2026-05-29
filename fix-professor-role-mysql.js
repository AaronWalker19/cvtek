/**
 * Script pour corriger le rôle du professeur dans la base de données MySQL
 * Exécution: node fix-professor-role.js
 */

const mysql = require('mysql2/promise');

async function fixProfessorRole() {
  let connection;
  
  try {
    console.log('🔌 Connexion à la base de données MySQL...');
    
    // Configuration de la connexion
    const config = {
      host: process.env.DB_HOST || 'localhost',
      user: process.env.DB_USER || 'root',
      password: process.env.DB_PASSWORD || '',
      database: process.env.DB_NAME || 'cvtek',
      port: process.env.DB_PORT || 3306,
      waitForConnections: true,
      connectionLimit: 1,
      queueLimit: 0,
    };
    
    console.log(`Configuration: Host=${config.host}, DB=${config.database}, User=${config.user}`);
    
    // Créer la connexion
    connection = await mysql.createConnection(config);
    console.log('✅ Connexion établie\n');
    
    // Vérifier l'utilisateur 17 avant correction
    console.log('📋 Vérification de l\'utilisateur 17 (AVANT):');
    const [userBefore] = await connection.execute(
      'SELECT id, username, email, role FROM users WHERE id = 17'
    );
    
    if (userBefore.length === 0) {
      console.log('❌ Utilisateur 17 non trouvé!');
      return;
    }
    
    console.log(`  ID: ${userBefore[0].id}`);
    console.log(`  Username: ${userBefore[0].username}`);
    console.log(`  Email: ${userBefore[0].email}`);
    console.log(`  Rôle actuel: ${userBefore[0].role}\n`);
    
    // Mettre à jour le rôle
    console.log('🔄 Mise à jour du rôle à "professor"...');
    const [result] = await connection.execute(
      'UPDATE users SET role = ? WHERE id = ?',
      ['professor', 17]
    );
    
    if (result.affectedRows > 0) {
      console.log(`✅ ${result.affectedRows} utilisateur(s) mis à jour\n`);
    } else {
      console.log('⚠️  Aucun utilisateur mis à jour\n');
    }
    
    // Vérifier après correction
    console.log('📋 Vérification de l\'utilisateur 17 (APRÈS):');
    const [userAfter] = await connection.execute(
      'SELECT id, username, email, role FROM users WHERE id = 17'
    );
    
    if (userAfter.length > 0) {
      console.log(`  ID: ${userAfter[0].id}`);
      console.log(`  Username: ${userAfter[0].username}`);
      console.log(`  Email: ${userAfter[0].email}`);
      console.log(`  Rôle: ${userAfter[0].role} ✅\n`);
    }
    
    // Afficher tous les professeurs
    console.log('📚 Tous les professeurs:');
    const [professors] = await connection.execute(
      'SELECT id, username, email, role FROM users WHERE role = "professor" ORDER BY id'
    );
    
    if (professors.length > 0) {
      professors.forEach(prof => {
        console.log(`  - ID ${prof.id}: ${prof.username} (${prof.email}) - Role: ${prof.role}`);
      });
    } else {
      console.log('  (Aucun professeur trouvé)');
    }
    
    console.log('\n✅ Correction complète!');
    
  } catch (error) {
    console.error('❌ Erreur:', error.message);
    process.exit(1);
  } finally {
    if (connection) {
      await connection.end();
      console.log('\n🔌 Connexion fermée');
    }
  }
}

// Lancer le script
fixProfessorRole().catch(err => {
  console.error('Error:', err);
  process.exit(1);
});
