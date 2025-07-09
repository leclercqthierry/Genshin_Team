<?php
declare (strict_types = 1);

namespace GenshinTeam\Models;

use DateTime;
use GenshinTeam\Connexion\Database;
use GenshinTeam\Utils\MailSenderInterface;
use PDO;
use Psr\Log\LoggerInterface;

/**
 * Gère les opérations liées à la réinitialisation des mots de passe :
 * génération de tokens, enregistrement en base, validation et envoi d’email.
 *
 * @package GenshinTeam\Models
 */
class PasswordReset
{
    protected PDO $pdo;
    protected LoggerInterface $logger;
    protected MailSenderInterface $mailer;

    public function __construct(LoggerInterface $logger, MailSenderInterface $mailer)
    {
        $this->pdo = Database::getInstance();
        // $this->pdo    = $pdo ?? Database::getInstance();
        $this->logger = $logger;
        $this->mailer = $mailer;
    }

    /**
     * Génère un token sécurisé et envoie le lien de réinitialisation si l’email existe.
     *
     * @param string $email
     */
    public function generateResetLink(string $email): void
    {
        $token     = bin2hex(random_bytes(32));
        $expiresAt = (new DateTime())->modify('+30 minutes')->format('Y-m-d H:i:s');

        $sql = "INSERT INTO zell_password_resets (email, token, expires_at)
                    VALUES (:email, :token, :expires)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':email'   => $email,
            ':token'   => $token,
            ':expires' => $expiresAt,
        ]);

        $this->sendResetEmail($email, $token);
    }

    /**
     * Envoie un email contenant le lien de réinitialisation.
     *
     * @param string $email
     * @param string $token
     */
    protected function sendResetEmail(string $email, string $token): void
    {
        $this->mailer->sendPasswordReset($email, $token);
        $this->logger->info("Mail envoyé avec succès à $email.");
    }

    /**
     * Vérifie si un token est valide et retourne l’email associé.
     *
     * @param string $token
     * @return string|null
     */
    public function verifyToken(string $token): ?string
    {
        $sql  = "SELECT email FROM zell_password_resets WHERE token = :token AND expires_at > NOW() LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':token' => $token]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if (! is_array($result)) {
            return null;
        }
        /** @var array{email: string} $result */
        return $result['email'];
    }

    /**
     * Supprime un token de réinitialisation une fois utilisé.
     *
     * @param string $token
     */
    public function invalidateToken(string $token): void
    {
        $sql  = "DELETE FROM zell_password_resets WHERE token = :token";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':token' => $token]);
    }

    /**
     * Met à jour le mot de passe hashé de l'utilisateur.
     *
     * @param string $email
     * @param string $hashedPassword
     */
    public function updateUserPassword(string $email, string $hashedPassword): void
    {
        $sql  = "UPDATE zell_users SET password = :password WHERE email = :email";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':password' => $hashedPassword,
            ':email'    => $email,
        ]);
    }
}
