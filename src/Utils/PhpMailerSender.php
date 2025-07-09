<?php
declare (strict_types = 1);

namespace GenshinTeam\Utils;

use PHPMailer\PHPMailer\PHPMailer;

/**
 * Implémentation concrète de MailSenderInterface utilisant PHPMailer.
 *
 * Ce service prépare et envoie un email de réinitialisation de mot de passe à l'utilisateur cible,
 * en construisant un lien contenant le token sécurisé fourni.
 *
 * Configuration SMTP par défaut :
 * - Host : 192.168.0.24
 * - Port : 1025 (serveur local de type Papercut, MailHog, etc.)
 * - Auth désactivée
 *
 * @package GenshinTeam\Utils
 */
class PhpMailerSender implements MailSenderInterface
{

    private ?PHPMailer $mailer;

    public function __construct(?PHPMailer $mailer = null)
    {
        $this->mailer = $mailer;
    }

    /**
     * Envoie un email contenant le lien de réinitialisation de mot de passe à l'adresse spécifiée.
     *
     * @param string $to    Adresse email du destinataire
     * @param string $token Token sécurisé à inclure dans le lien de réinitialisation
     *
     * @throws \PHPMailer\PHPMailer\Exception Si l'envoi du mail échoue
     */
    public function sendPasswordReset(string $to, string $token): void
    {
        $mail = $this->mailer ?? new PHPMailer(true); // utilise le mock s’il est fourni

        $link    = BASE_URL . "/reset-password?token=" . urlencode($token);
        $message = "Bonjour,\n\nCliquez sur le lien suivant pour réinitialiser votre mot de passe :\n" . BASE_URL . "/reset-password?token=$token\n\nCe lien est valable 30 minutes.";

        $mail->isSMTP();
        $mail->Host     = '192.168.0.24';
        $mail->Port     = 1025;
        $mail->SMTPAuth = false;
        $mail->setFrom('no-reply@genshinteam.com', 'Support');
        $mail->addAddress($to);
        $mail->isHTML(false);
        $mail->Subject = "Réinitialisation de mot de passe";
        $mail->Body    = $message;

        $mail->send();
    }
}
