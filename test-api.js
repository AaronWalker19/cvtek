#!/usr/bin/env node

/**
 * Script de test et diagnostic de l'API
 * Usage: node test-api.js [URL]
 * 
 * Exemples:
 * - Node: npm start (puis dans une autre fenêtre)
 * - Local: node test-api.js http://localhost:3000
 * - Production: node test-api.js https://cvtek.alwaysdata.net
 */

const http = require('http');
const https = require('https');

async function fetch(url, options = {}) {
  return new Promise((resolve, reject) => {
    const protocol = url.startsWith('https') ? https : http;
    
    protocol.get(url, (res) => {
      let data = '';
      res.on('data', chunk => data += chunk);
      res.on('end', () => {
        try {
          resolve({
            status: res.statusCode,
            headers: res.headers,
            body: JSON.parse(data)
          });
        } catch (e) {
          resolve({
            status: res.statusCode,
            headers: res.headers,
            body: data
          });
        }
      });
    }).on('error', reject);
  });
}

async function runTests() {
  const baseUrl = process.argv[2] || 'http://localhost:3000';
  
  console.log('\n🔧 TEST D\'API CVTEK');
  console.log('═'.repeat(50));
  console.log(`📍 URL de base: ${baseUrl}\n`);
  
  const tests = [
    {
      name: '🏥 Health Check',
      url: '/api/health',
      expected: 200
    },
    {
      name: '📚 Récupérer les projets',
      url: '/api/projects',
      expected: 200
    },
    {
      name: '🖼️ Récupérer tous les fichiers',
      url: '/api/projects/files/all',
      expected: 200
    }
  ];
  
  for (const test of tests) {
    try {
      console.log(`⏳ ${test.name}...`);
      const response = await fetch(baseUrl + test.url);
      
      const statusOk = response.status === test.expected;
      const statusEmoji = statusOk ? '✅' : '❌';
      
      console.log(`  ${statusEmoji} Status: ${response.status} (attendu: ${test.expected})`);
      
      if (response.body.error) {
        console.log(`  ⚠️  Erreur: ${response.body.error}`);
      }
      
      if (response.body.message) {
        console.log(`  ℹ️  Message: ${response.body.message}`);
      }
      
      if (test.url === '/api/health' && response.body.config) {
        console.log(`  📋 Base de données: ${response.body.database}`);
        console.log(`  📊 Tables: ${response.body.config.tables ? response.body.config.tables.join(', ') : 'N/A'}`);
      }
      
      if (test.url === '/api/projects/files/all' && Array.isArray(response.body)) {
        console.log(`  📁 Fichiers trouvés: ${response.body.length}`);
      }
      
      console.log('');
    } catch (err) {
      console.log(`  ❌ Erreur: ${err.message}\n`);
    }
  }
  
  console.log('═'.repeat(50));
  console.log('\n💡 Conseils:\n');
  console.log('1. Si vous voyez ❌ sur tous les tests:');
  console.log('   - Vérifiez que le serveur est en cours d\'exécution');
  console.log('   - Vérifiez l\'URL\n');
  console.log('2. Si vous voyez ❌ sur "Récupérer tous les fichiers":');
  console.log('   - Vérifiez que la base de données est initialisée');
  console.log('   - Exécutez: npm run setup (pour l\'initialisation locale)\n');
  console.log('3. Pour diagnostiquer le problème sur alwaysdata.net:');
  console.log(`   - Ouvrez: ${baseUrl}/api/health\n`);
}

runTests();
