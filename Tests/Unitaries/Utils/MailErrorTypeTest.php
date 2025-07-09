<?php
declare (strict_types = 1);

use GenshinTeam\Utils\MailErrorType;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires pour l’enum MailErrorType.
 *
 * Ces tests valident que chaque cas de l'enum retourne bien un message utilisateur explicite
 * à travers la méthode getMessage().
 *
 * @covers \GenshinTeam\Utils\MailErrorType
 */
class MailErrorTypeTest extends TestCase
{
    /**
     * Vérifie que le message pour une erreur de connexion SMTP est correct.
     */
    public function testSmtpConnectMessage(): void
    {
        $this->assertSame(
            "Connexion au serveur d'envoi impossible. Vérifiez la configuration SMTP.",
            MailErrorType::SMTP_CONNECT->getMessage()
        );
    }

    /**
     * Vérifie que le message pour une adresse email invalide est correct.
     */
    public function testInvalidAddressMessage(): void
    {
        $this->assertSame(
            "L'adresse email fournie est invalide.",
            MailErrorType::INVALID_ADDRESS->getMessage()
        );
    }

    /**
     * Vérifie que le message pour une erreur lors de l’envoi d’un message est correct.
     */
    public function testMessageSendFailure(): void
    {
        $this->assertSame(
            "Le message n'a pas pu être envoyé. Veuillez réessayer.",
            MailErrorType::MESSAGE_SEND->getMessage()
        );
    }

    /**
     * Vérifie que le message générique pour une erreur inconnue est correct.
     */
    public function testUnknownErrorMessage(): void
    {
        $this->assertSame(
            "Une erreur inattendue est survenue lors de l'envoi du mail.",
            MailErrorType::UNKNOWN->getMessage()
        );
    }
}
