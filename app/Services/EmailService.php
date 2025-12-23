<?php

namespace App\Services;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;
use Illuminate\Support\Facades\Log;

class EmailService
{
    private PHPMailer $mailer;
    
    public function __construct()
    {
        $this->mailer = new PHPMailer(true);
        $this->configureSMTP();
    }

    /**
     * Configuration SMTP Gmail
     */
    private function configureSMTP(): void
    {
        try {
            // Configuration du serveur
            $this->mailer->isSMTP();
            $this->mailer->Host       = env('MAIL_HOST', 'smtp.gmail.com');
            $this->mailer->SMTPAuth   = true;
            $this->mailer->Username   = env('MAIL_USERNAME', 'augustinhountondji82@gmail.com');
            $this->mailer->Password   = env('MAIL_PASSWORD', 'yvlc pjdm rcsw skpl');
            $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $this->mailer->Port       = env('MAIL_PORT', 587);
            $this->mailer->Timeout    = 10;

            // Configuration générale
            $this->mailer->CharSet = 'UTF-8';
            $this->mailer->isHTML(true);
            
            // Désactiver la vérification SSL en développement
            if (env('APP_ENV') === 'local') {
                $this->mailer->SMTPOptions = [
                    'ssl' => [
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                        'allow_self_signed' => true
                    ]
                ];
            }

        } catch (Exception $e) {
            Log::error('Erreur configuration SMTP: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Envoyer l'email de création de compte
     */
    public function sendAccountCreatedEmail(string $recipientEmail, string $recipientName, string $password, string $creatorName): bool
    {
        try {
            // Expéditeur
            $this->mailer->setFrom(
                env('MAIL_FROM_ADDRESS', 'noreply@diaddem.com'),
                env('MAIL_FROM_NAME', 'DIADDEM - Gestion des Processus')
            );

            // Destinataire
            $this->mailer->addAddress($recipientEmail, $recipientName);

            // Sujet
            $this->mailer->Subject = '🎉 Bienvenue sur DIADDEM - Votre compte a été créé';

            // Corps de l'email
            $this->mailer->Body = $this->getEmailTemplate($recipientName, $recipientEmail, $password, $creatorName);
            $this->mailer->AltBody = $this->getPlainTextEmail($recipientName, $recipientEmail, $password, $creatorName);

            // Envoi
            $result = $this->mailer->send();
            
            Log::info("Email envoyé avec succès à {$recipientEmail}");
            
            return $result;

        } catch (Exception $e) {
            Log::error("Erreur envoi email à {$recipientEmail}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Template HTML de l'email
     */
    private function getEmailTemplate(string $name, string $email, string $password, string $creatorName): string
    {
        $loginUrl = url('/login');
        $appName = config('app.name', 'DIADDEM');
        
        return "
        <!DOCTYPE html>
        <html lang='fr'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Bienvenue sur {$appName}</title>
            <style>
                body {
                    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                    line-height: 1.6;
                    color: #333;
                    background-color: #f4f4f4;
                    margin: 0;
                    padding: 0;
                }
                .email-container {
                    max-width: 600px;
                    margin: 40px auto;
                    background: white;
                    border-radius: 12px;
                    overflow: hidden;
                    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
                }
                .email-header {
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    color: white;
                    padding: 40px 30px;
                    text-align: center;
                }
                .email-header h1 {
                    margin: 0;
                    font-size: 28px;
                    font-weight: 700;
                }
                .email-body {
                    padding: 40px 30px;
                }
                .welcome-box {
                    background: #f8f9fa;
                    border-left: 4px solid #667eea;
                    padding: 20px;
                    margin: 20px 0;
                    border-radius: 6px;
                }
                .credentials-box {
                    background: #fff3cd;
                    border: 2px solid #ffc107;
                    padding: 20px;
                    margin: 30px 0;
                    border-radius: 8px;
                }
                .credentials-box h3 {
                    margin-top: 0;
                    color: #856404;
                }
                .credential-item {
                    margin: 15px 0;
                    padding: 12px;
                    background: white;
                    border-radius: 6px;
                }
                .credential-label {
                    font-weight: 600;
                    color: #495057;
                    display: block;
                    margin-bottom: 5px;
                }
                .credential-value {
                    font-size: 18px;
                    color: #212529;
                    font-family: 'Courier New', monospace;
                    font-weight: 700;
                    background: #e9ecef;
                    padding: 8px 12px;
                    border-radius: 4px;
                    display: inline-block;
                }
                .btn-primary {
                    display: inline-block;
                    padding: 14px 32px;
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    color: white;
                    text-decoration: none;
                    border-radius: 8px;
                    font-weight: 600;
                    margin: 20px 0;
                    transition: all 0.3s;
                }
                .btn-primary:hover {
                    transform: translateY(-2px);
                    box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
                }
                .security-notice {
                    background: #d1ecf1;
                    border-left: 4px solid #17a2b8;
                    padding: 15px;
                    margin: 20px 0;
                    border-radius: 6px;
                }
                .security-notice h4 {
                    margin-top: 0;
                    color: #0c5460;
                }
                .email-footer {
                    background: #343a40;
                    color: #adb5bd;
                    padding: 30px;
                    text-align: center;
                    font-size: 14px;
                }
                .email-footer a {
                    color: #667eea;
                    text-decoration: none;
                }
                ul {
                    padding-left: 20px;
                }
                li {
                    margin: 8px 0;
                }
            </style>
        </head>
        <body>
            <div class='email-container'>
                <div class='email-header'>
                    <h1>🎉 Bienvenue sur {$appName}</h1>
                    <p style='margin: 10px 0 0; font-size: 16px; opacity: 0.95;'>
                        Votre compte a été créé avec succès
                    </p>
                </div>
                
                <div class='email-body'>
                    <div class='welcome-box'>
                        <h2 style='color: #667eea; margin-top: 0;'>Bonjour {$name} 👋</h2>
                        <p>
                            <strong>{$creatorName}</strong> vient de créer un compte pour vous sur la plateforme 
                            <strong>{$appName}</strong> - Système de Gestion des Processus.
                        </p>
                        <p>
                            Vous pouvez maintenant accéder à la plateforme et commencer à gérer 
                            les processus, évaluations et rapports de votre organisation.
                        </p>
                    </div>

                    <div class='credentials-box'>
                        <h3>🔐 Vos identifiants de connexion</h3>
                        <p style='margin: 0 0 15px; color: #856404;'>
                            Utilisez ces informations pour vous connecter à votre compte :
                        </p>
                        
                        <div class='credential-item'>
                            <span class='credential-label'>📧 Email :</span><br>
                            <span class='credential-value'>{$email}</span>
                        </div>
                        
                        <div class='credential-item'>
                            <span class='credential-label'>🔑 Mot de passe temporaire :</span><br>
                            <span class='credential-value'>{$password}</span>
                        </div>
                    </div>

                    <div style='text-align: center;'>
                        <a href='{$loginUrl}' class='btn-primary'>
                            🚀 Se connecter maintenant
                        </a>
                    </div>

                    <div class='security-notice'>
                        <h4>🔒 Conseils de sécurité</h4>
                        <ul style='margin: 10px 0;'>
                            <li><strong>Changez votre mot de passe</strong> dès votre première connexion</li>
                            <li>Ne partagez <strong>jamais</strong> votre mot de passe</li>
                            <li>Utilisez un mot de passe <strong>unique et complexe</strong></li>
                            <li>Activez l'authentification à deux facteurs si disponible</li>
                        </ul>
                    </div>

                    <hr style='border: none; border-top: 1px solid #dee2e6; margin: 30px 0;'>

                    <div style='color: #6c757d; font-size: 14px;'>
                        <p><strong>Besoin d'aide ?</strong></p>
                        <p>
                            Si vous avez des questions ou rencontrez des difficultés pour vous connecter, 
                            n'hésitez pas à contacter <strong>{$creatorName}</strong> ou notre support technique.
                        </p>
                    </div>
                </div>

                <div class='email-footer'>
                    <p style='margin: 0 0 10px;'>
                        <strong>{$appName}</strong> - Système de Gestion des Processus
                    </p>
                    <p style='margin: 0 0 10px;'>
                        Cotonou, Bénin | <a href='mailto:support@diaddem.com'>support@diaddem.com</a>
                    </p>
                    <p style='margin: 0; font-size: 12px; color: #868e96;'>
                        © " . date('Y') . " DIADDEM. Tous droits réservés.
                    </p>
                </div>
            </div>
        </body>
        </html>
        ";
    }

    /**
     * Version texte brut de l'email
     */
    private function getPlainTextEmail(string $name, string $email, string $password, string $creatorName): string
    {
        $loginUrl = url('/login');
        $appName = config('app.name', 'DIADDEM');
        
        return "
Bienvenue sur {$appName}

Bonjour {$name},

{$creatorName} vient de créer un compte pour vous sur la plateforme {$appName}.

VOS IDENTIFIANTS DE CONNEXION :
--------------------------------
Email : {$email}
Mot de passe : {$password}

Connectez-vous maintenant : {$loginUrl}

CONSEILS DE SÉCURITÉ :
- Changez votre mot de passe dès votre première connexion
- Ne partagez jamais votre mot de passe
- Utilisez un mot de passe unique et complexe

Besoin d'aide ? Contactez {$creatorName} ou notre support technique.

---
{$appName} - Système de Gestion des Processus
© " . date('Y') . " DIADDEM. Tous droits réservés.
        ";
    }

    /**
     * Envoyer un email de test
     */
    public function sendTestEmail(string $recipientEmail): bool
    {
        try {
            $this->mailer->setFrom(
                env('MAIL_FROM_ADDRESS', 'noreply@diaddem.com'),
                env('MAIL_FROM_NAME', 'DIADDEM')
            );
            
            $this->mailer->addAddress($recipientEmail);
            $this->mailer->Subject = 'Test Email - DIADDEM';
            $this->mailer->Body = '<h1>Email de test</h1><p>Si vous recevez cet email, la configuration fonctionne correctement !</p>';
            
            return $this->mailer->send();
        } catch (Exception $e) {
            Log::error('Erreur test email: ' . $e->getMessage());
            return false;
        }
    }
}