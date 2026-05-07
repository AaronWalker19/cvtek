// ============================================
// Service d'envoi d'emails
// ============================================
// À intégrer dans routes/auth.js

const nodemailer = require('nodemailer');

// Configuration du transporteur d'emails
// À personnaliser selon votre fournisseur d'email
const createMailTransporter = () => {
  console.log(`[EMAIL] 📧 Création du transporteur (SERVICE: ${process.env.EMAIL_SERVICE})...`);
  
  // Option 1: Gmail
  if (process.env.EMAIL_SERVICE === 'gmail') {
    console.log(`[EMAIL] 🟠 Utilisation: Gmail`);
    return nodemailer.createTransport({
      service: 'gmail',
      auth: {
        user: process.env.EMAIL_USER,
        pass: process.env.EMAIL_PASSWORD, // Mot de passe d'application Gmail
      }
    });
  }

  // Option 2: Service SMTP générique (ex: Brevo, Mailgun, etc.)
  if (process.env.EMAIL_SERVICE === 'smtp') {
    console.log(`[EMAIL] 🟠 Utilisation: SMTP (${process.env.SMTP_HOST}:${process.env.SMTP_PORT})`);
    return nodemailer.createTransport({
      host: process.env.SMTP_HOST,
      port: process.env.SMTP_PORT,
      secure: process.env.SMTP_SECURE === 'true', // true for 465, false for other ports
      auth: {
        user: process.env.SMTP_USER,
        pass: process.env.SMTP_PASSWORD,
      }
    });
  }

  // Option 3: Service (SendGrid, Mailgun, etc.)
  if (process.env.EMAIL_SERVICE === 'sendgrid') {
    console.log(`[EMAIL] 🟠 Utilisation: SendGrid`);
    return nodemailer.createTransport({
      host: 'smtp.sendgrid.net',
      port: 587,
      auth: {
        user: 'apikey',
        pass: process.env.SENDGRID_API_KEY,
      }
    });
  }

  console.warn(`[EMAIL] ⚠️  Aucun service d'email configuré (EMAIL_SERVICE=${process.env.EMAIL_SERVICE})`);
  return null;
};

// Fonction pour envoyer l'email d'invitation
const sendInvitationEmail = async (email, token, frontendUrl, ccEmail = null) => {
  console.log(`[EMAIL-INVITE] 📧 Envoi invitation à: ${email}${ccEmail ? ` (CC: ${ccEmail})` : ''}`);
  
  const transporter = createMailTransporter();

  if (!transporter) {
    console.warn(`[EMAIL-INVITE] ⚠️  Aucun service d'email configuré. Email non envoyé.`);
    console.log(`[EMAIL-INVITE] 📧 L'utilisateur devrait recevoir ce lien:\n${frontendUrl}/activate?token=${token}`);
    return false;
  }

  const activationUrl = `${frontendUrl}/activate?token=${token}`;
  
  const htmlContent = `
    <h2>Bienvenue sur CVTEK!</h2>
    <p>Vous avez été invité(e) à rejoindre notre plateforme.</p>
    <p>Cliquez sur le lien ci-dessous pour créer votre compte:</p>
    <a href="${activationUrl}" style="
      display: inline-block;
      padding: 12px 30px;
      background-color: #007bff;
      color: white;
      text-decoration: none;
      border-radius: 4px;
      margin: 20px 0;
    ">
      Créer mon compte
    </a>
    <p>Ou copiez ce lien dans votre navigateur:</p>
    <p><code>${activationUrl}</code></p>
    <p><strong>Attention:</strong> Ce lien expire dans 48 heures.</p>
    <hr>
    <p style="color: #999; font-size: 12px;">
      Si vous n'avez pas demandé cette invitation, vous pouvez ignorer cet email.
    </p>
  `;

  try {
    const mailOptions = {
      from: process.env.EMAIL_FROM || process.env.EMAIL_USER,
      to: email,
      subject: 'Invitation CVTEK - Créez votre compte',
      html: htmlContent,
      text: `Cliquez sur ce lien pour créer votre compte:\n${activationUrl}\n\nCe lien expire dans 48 heures.`
    };

    // Ajouter CC si fourni
    if (ccEmail) {
      mailOptions.cc = ccEmail;
    }

    console.log(`[EMAIL-INVITE] 📬 Envoi du mail via transporter...`);
    console.log(`[EMAIL-INVITE]    From: ${mailOptions.from}`);
    console.log(`[EMAIL-INVITE]    To: ${mailOptions.to}`);
    if (ccEmail) console.log(`[EMAIL-INVITE]    CC: ${mailOptions.cc}`);
    
    await transporter.sendMail(mailOptions);

    console.log(`✅ Email d'invitation envoyé à ${email}${ccEmail ? ` (CC: ${ccEmail})` : ''}`);
    return true;
  } catch (err) {
    console.error(`❌ Erreur lors de l'envoi de l'email à ${email}:`, err.message);
    console.error(`[EMAIL-INVITE] Stack:`, err.stack);
    return false;
  }
};

// Fonction pour envoyer l'email de réinitialisation de mot de passe
const sendPasswordResetEmail = async (email, token, frontendUrl, ccEmail = null) => {
  const transporter = createMailTransporter();

  if (!transporter) {
    console.warn("⚠️  Aucun service d'email configuré. Email non envoyé.");
    console.log(`📧 L'utilisateur devrait recevoir ce lien:\n${frontendUrl}/reset-password?token=${token}`);
    return false;
  }

  const resetUrl = `${frontendUrl}/reset-password?token=${token}`;
  
  const htmlContent = `
    <h2>Réinitialisation de votre mot de passe</h2>
    <p>Vous avez demandé une réinitialisation de mot de passe pour votre compte CVTEK.</p>
    <p>Cliquez sur le lien ci-dessous pour définir un nouveau mot de passe:</p>
    <a href="${resetUrl}" style="
      display: inline-block;
      padding: 12px 30px;
      background-color: #007bff;
      color: white;
      text-decoration: none;
      border-radius: 4px;
      margin: 20px 0;
    ">
      Réinitialiser mon mot de passe
    </a>
    <p>Ou copiez ce lien dans votre navigateur:</p>
    <p><code>${resetUrl}</code></p>
    <p><strong>Attention:</strong> Ce lien expire dans 1 heure.</p>
    <hr>
    <p style="color: #999; font-size: 12px;">
      Si vous n'avez pas demandé cette réinitialisation, veuillez ignorer cet email. Votre mot de passe ne sera pas modifié.
    </p>
  `;

  try {
    const mailOptions = {
      from: process.env.EMAIL_FROM || process.env.EMAIL_USER,
      to: email,
      subject: 'CVTEK - Réinitialisation de votre mot de passe',
      html: htmlContent,
      text: `Cliquez sur ce lien pour réinitialiser votre mot de passe:\n${resetUrl}\n\nCe lien expire dans 1 heure.`
    };

    // Ajouter CC si fourni
    if (ccEmail) {
      mailOptions.cc = ccEmail;
    }

    await transporter.sendMail(mailOptions);

    console.log(`✅ Email de réinitialisation envoyé à ${email}${ccEmail ? ` (CC: ${ccEmail})` : ''}`);
    return true;
  } catch (err) {
    console.error(`❌ Erreur lors de l'envoi de l'email de réinitialisation à ${email}:`, err.message);
    return false;
  }
};

module.exports = { sendInvitationEmail, sendPasswordResetEmail, createMailTransporter };
