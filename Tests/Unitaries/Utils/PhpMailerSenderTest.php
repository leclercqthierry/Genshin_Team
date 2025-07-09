<?php

declare (strict_types = 1);

use GenshinTeam\Utils\PhpMailerSender;
use PHPMailer\PHPMailer\PHPMailer;
use PHPUnit\Framework\TestCase;

/**
 * Service d’envoi d’email de réinitialisation de mot de passe basé sur PHPMailer.
 *
 * Cette implémentation utilise une configuration SMTP locale pour envoyer un lien de réinitialisation
 * contenant un token sécurisé à l’adresse spécifiée. Elle est conçue pour être injectée via l’interface
 * MailSenderInterface et permettre une substitution facile pour les tests.
 *
 * @package GenshinTeam\Utils
 */
class PhpMailerSenderTest extends TestCase
{
    /**
     * Vérifie que la méthode sendPasswordReset configure correctement un objet PHPMailer
     * et appelle les méthodes nécessaires à l’envoi de l’email.
     *
     * Ce test utilise un double partiel de PHPMailer pour s’assurer que :
     * - l’adresse destinataire est bien ajoutée via addAddress()
     * - l’expéditeur est bien défini via setFrom()
     * - l’envoi est déclenché via send()
     *
     * Le contenu du corps du message ou la configuration SMTP ne sont pas testés ici.
     *
     * @return void
     */
    public function testSendPasswordResetConfiguresAndSendsEmail(): void
    {
        if (! defined('BASE_URL')) {
            define('BASE_URL', 'http://localhost');
        }

        $to    = 'user@example.com';
        $token = 'abc123token';

        // On remplace PHPMailer par un double partiel
        $mockMailer = $this->getMockBuilder(PHPMailer::class)
            ->onlyMethods(['send', 'addAddress', 'setFrom'])
            ->disableOriginalConstructor()
            ->getMock();

        // On vérifie que les méthodes critiques sont bien appelées
        $mockMailer->expects($this->once())->method('send');
        $mockMailer->expects($this->once())->method('addAddress')->with($to);
        $mockMailer->expects($this->once())->method('setFrom')->with('no-reply@genshinteam.com', 'Support');

        $sender = new PhpMailerSender($mockMailer);
        $sender->sendPasswordReset($to, $token);
    }
}
