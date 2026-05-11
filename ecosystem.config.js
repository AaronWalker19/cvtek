module.exports = {
  apps: [
    {
      // Application name
      name: 'cvtek-server',
      
      // Script to run
      script: './server.js',
      
      // Working directory
      cwd: '/home/valin6/cvtek',
      
      // Environment variables
      env: {
        NODE_ENV: 'production',
        PORT: 3000,
        DB_HOST: 'mysql-casibio.alwaysdata.net',
        DB_USER: 'casibio',
        DB_NAME: 'casibio_data',
        JWT_SECRET: '8X2kP7mQ9nL5rS3vT6wU1yA4bC7dE0fG2hI5jK8mN1oP4qR7sT0uV3wX6yZ9aB2c',
        CORS_ORIGIN: 'https://cvtek.alwaysdata.net',
        FRONTEND_URL: 'https://cvtek.alwaysdata.net'
      },
      
      // Restart policy
      instances: 1,
      exec_mode: 'cluster',
      
      // Watch files for changes (set to false in production)
      watch: false,
      
      // Restart delay
      restart_delay: 4000,
      
      // Max restarts
      max_restarts: 10,
      
      // Min uptime before restart
      min_uptime: '10s',
      
      // Error log file
      error_file: './logs/error.log',
      
      // Output log file
      out_file: './logs/out.log',
      
      // Combined log file
      log_date_format: 'YYYY-MM-DD HH:mm:ss Z',
      
      // Kill timeout
      kill_timeout: 5000,
      
      // Wait for listening port
      listen_timeout: 10000,
      
      // Shutdown timeout
      shutdown_timeout: 5000,
    }
  ],
  
  // Deployment configurations
  deploy: {
    production: {
      // Server address
      host: 'ssh.alwaysdata.net',
      user: 'valin6',
      key: '/path/to/ssh/key',
      port: 22,
      
      // Path to deploy to
      path: '/home/valin6/cvtek',
      
      // Ref to deploy
      ref: 'origin/main',
      
      // Repository URL
      repo: 'git@github.com:user/cvtek.git',
      
      // Pre-deploy commands
      'pre-deploy-local': 'npm run build',
      
      // Post-deploy commands
      'post-deploy': 'npm install && npm run build && pm2 reload ecosystem.config.js --env production',
      
      // On update
      'exec-mode': 'cluster'
    }
  }
};
