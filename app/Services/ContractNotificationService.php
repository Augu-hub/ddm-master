<?php

namespace App\Services;

use App\Models\Tenant\Process\ContractNotification;
use App\Services\EmailService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class ContractNotificationService
{
    protected EmailService $emailService;

    public function __construct(EmailService $emailService)
    {
        $this->emailService = $emailService;
    }

    /**
     * Envoyer une notification à l'utilisateur d'une fonction
     */
    public function sendFunctionNotification(array $data): array
    {
        try {
            $sender = Auth::user();
            
            // Créer la notification en base
            $notification = ContractNotification::create([
                'contract_id' => $data['contract_id'],
                'output_id' => $data['output_id'],
                'output_label' => $data['output_label'],
                'function_id' => $data['function_id'],
                'function_name' => $data['function_name'],
                'user_id' => $data['user_id'],
                'user_name' => $data['user_name'],
                'user_email' => $data['user_email'],
                'sender_id' => $sender->id,
                'sender_name' => $sender->name,
                'message' => $data['message'] ?? null,
                'expectations' => $data['expectations'] ?? null,
                'status' => 'sent',
            ]);

            // Envoyer l'email
            $emailSent = $this->sendNotificationEmail($notification, $data);

            if (!$emailSent) {
                $notification->update(['status' => 'failed']);
                Log::warning("Email notification non envoyé pour notification #{$notification->id}");
            }

            return [
                'success' => true,
                'notification_id' => $notification->id,
                'email_sent' => $emailSent
            ];

        } catch (\Exception $e) {
            Log::error('Erreur sendFunctionNotification: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Envoyer l'email de notification
     */
    protected function sendNotificationEmail(ContractNotification $notification, array $data): bool
    {
        try {
            $subject = "📋 Contrat d'Interfaces - " . $notification->output_label;
            
            $htmlContent = $this->buildEmailTemplate($notification, $data);

            return $this->emailService->sendCustomEmail(
                $notification->user_email,
                $notification->user_name,
                $subject,
                $htmlContent
            );

        } catch (\Exception $e) {
            Log::error('Erreur sendNotificationEmail: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Template HTML de l'email
     */
    protected function buildEmailTemplate(ContractNotification $notification, array $data): string
    {
        $processCode = $data['process_code'] ?? 'N/A';
        $processName = $data['process_name'] ?? 'Processus';
        $contractUrl = $data['contract_url'] ?? route('process.contracts.index');

        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <style>
                body {
                    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                    background-color: #f4f6f9;
                    margin: 0;
                    padding: 0;
                }
                .container {
                    max-width: 600px;
                    margin: 30px auto;
                    background: white;
                    border-radius: 12px;
                    overflow: hidden;
                    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
                }
                .header {
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    padding: 30px;
                    text-align: center;
                    color: white;
                }
                .header h1 {
                    margin: 0;
                    font-size: 24px;
                    font-weight: 700;
                }
                .content {
                    padding: 30px;
                }
                .info-box {
                    background: #f8f9fa;
                    border-left: 4px solid #667eea;
                    padding: 15px;
                    margin: 20px 0;
                    border-radius: 8px;
                }
                .info-label {
                    font-weight: 600;
                    color: #495057;
                    font-size: 13px;
                    text-transform: uppercase;
                    margin-bottom: 5px;
                }
                .info-value {
                    color: #212529;
                    font-size: 15px;
                }
                .expectations-box {
                    background: #e3f2fd;
                    border-left: 4px solid #2196f3;
                    padding: 15px;
                    margin: 20px 0;
                    border-radius: 8px;
                }
                .cta-button {
                    display: inline-block;
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    color: white !important;
                    text-decoration: none;
                    padding: 14px 30px;
                    border-radius: 8px;
                    font-weight: 600;
                    margin: 20px 0;
                    text-align: center;
                }
                .footer {
                    background: #f8f9fa;
                    padding: 20px;
                    text-align: center;
                    font-size: 13px;
                    color: #6c757d;
                    border-top: 1px solid #dee2e6;
                }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>📋 Contrat d'Interfaces</h1>
                    <p style='margin: 5px 0 0 0; font-size: 14px; opacity: 0.9;'>
                        Nouvelle notification
                    </p>
                </div>

                <div class='content'>
                    <p style='font-size: 16px; color: #212529;'>
                        Bonjour <strong>{$notification->user_name}</strong>,
                    </p>

                    <p style='font-size: 15px; color: #495057; line-height: 1.6;'>
                        <strong>{$notification->sender_name}</strong> vous a affecté une sortie 
                        dans le contrat d'interfaces du processus :
                    </p>

                    <div class='info-box'>
                        <div class='info-label'>📊 Processus</div>
                        <div class='info-value'><strong>{$processCode}</strong> — {$processName}</div>
                    </div>

                    <div class='info-box'>
                        <div class='info-label'>📤 Sortie affectée</div>
                        <div class='info-value'>{$notification->output_label}</div>
                    </div>

                    <div class='info-box'>
                        <div class='info-label'>👤 Votre fonction</div>
                        <div class='info-value'>{$notification->function_name}</div>
                    </div>

                    " . ($notification->expectations ? "
                    <div class='expectations-box'>
                        <div class='info-label' style='color: #1976d2;'>💡 Attentes</div>
                        <div class='info-value' style='color: #0d47a1;'>{$notification->expectations}</div>
                    </div>
                    " : "") . "

                    " . ($notification->message ? "
                    <div class='info-box'>
                        <div class='info-label'>💬 Message</div>
                        <div class='info-value'>{$notification->message}</div>
                    </div>
                    " : "") . "

                    <div style='text-align: center; margin: 30px 0;'>
                        <a href='{$contractUrl}' class='cta-button'>
                            📋 Voir le contrat
                        </a>
                    </div>

                    <p style='font-size: 13px; color: #6c757d; line-height: 1.5; margin-top: 20px;'>
                        Cette notification a été envoyée automatiquement par le système de gestion 
                        des contrats d'interfaces DIADDEM.
                    </p>
                </div>

                <div class='footer'>
                    <p style='margin: 0 0 5px 0;'>
                        <strong>DIADDEM</strong> — Gestion des Processus
                    </p>
                    <p style='margin: 0; font-size: 12px;'>
                        © " . date('Y') . " Tous droits réservés
                    </p>
                </div>
            </div>
        </body>
        </html>
        ";
    }

    /**
     * Récupérer les notifications d'un utilisateur
     */
    public function getUserNotifications(int $userId, bool $unreadOnly = false): array
    {
        $query = ContractNotification::forUser($userId)
            ->orderByDesc('created_at');

        if ($unreadOnly) {
            $query->unread();
        }

        $notifications = $query->get()->map(function ($n) {
            return [
                'id' => $n->id,
                'contract_id' => $n->contract_id,
                'output_label' => $n->output_label,
                'function_name' => $n->function_name,
                'sender_name' => $n->sender_name,
                'message' => $n->message,
                'expectations' => $n->expectations,
                'status' => $n->status,
                'is_read' => $n->status === 'read',
                'created_at' => $n->created_at->format('d/m/Y H:i'),
                'read_at' => $n->read_at?->format('d/m/Y H:i'),
            ];
        });

        return [
            'notifications' => $notifications,
            'unread_count' => ContractNotification::forUser($userId)->unread()->count()
        ];
    }

    /**
     * Marquer une notification comme lue
     */
    public function markAsRead(int $notificationId): bool
    {
        try {
            $notification = ContractNotification::findOrFail($notificationId);
            $notification->markAsRead();
            return true;
        } catch (\Exception $e) {
            Log::error('Erreur markAsRead: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Marquer toutes les notifications d'un utilisateur comme lues
     */
    public function markAllAsRead(int $userId): bool
    {
        try {
            ContractNotification::forUser($userId)
                ->unread()
                ->update([
                    'status' => 'read',
                    'read_at' => now()
                ]);
            return true;
        } catch (\Exception $e) {
            Log::error('Erreur markAllAsRead: ' . $e->getMessage());
            return false;
        }
    }
}