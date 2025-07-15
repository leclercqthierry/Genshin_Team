<?php
declare (strict_types = 1);

namespace GenshinTeam\Models;

use Exception;
use GenshinTeam\Connexion\Database;
use PDO;
use Psr\Log\LoggerInterface;

/**
 * Class UserModel
 *
 * Ce modèle gère les opérations liées aux utilisateurs dans la base de données.
 * Il s'appuie sur une connexion PDO obtenue via la classe Database (qui implémente le pattern Singleton)
 * pour effectuer des opérations comme la création d'un nouvel utilisateur et la récupération d'un utilisateur
 * en fonction de son pseudo.
 *
 * @package GenshinTeam\Models
 */
class UserModel
{
    /**
     * Instance PDO pour accéder à la base de données.
     *
     * @var PDO
     */
    private PDO $pdo;

    /**
     * Logger PSR-3 utilisé pour enregistrer les erreurs liées aux opérations utilisateurs.
     *
     * @var LoggerInterface
     */
    /** @phpstan-ignore-next-line */
    private LoggerInterface $logger;

    /**
     * Constructeur.
     *
     * Établit la connexion à la base de données en récupérant l'instance PDO via la classe Database.
     *
     * @param LoggerInterface $logger Logger utilisé pour tracer les erreurs lors des opérations en base.
     *
     * @throws Exception Si la connexion à la base de données échoue.
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->pdo    = Database::getInstance();
        $this->logger = $logger;
    }

    /**
     * Enregistre un nouvel utilisateur dans la base de données.
     *
     * Prépare et exécute une requête SQL d'insertion afin de créer un nouvel utilisateur.
     *
     * @param string $nickname       Le pseudo de l'utilisateur.
     * @param string $email          L'email de l'utilisateur.
     * @param string $hashedPassword Le mot de passe préalablement hashé.
     *
     * @return bool Renvoie true en cas de succès, false sinon.
     */
    public function createUser(string $nickname, string $email, string $hashedPassword): bool
    {
        $sql  = "INSERT INTO zell_users (nickname, email, password, id_role) VALUES (:nickname, :email, :password, :id_role)";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':nickname' => $nickname,
            ':email'    => $email,
            ':password' => $hashedPassword,
            ':id_role'  => 2, // 2 correspond au rôle de Membre
        ]);
    }

    /**
     * Récupère les informations d'un utilisateur à partir de son pseudo.
     *
     * Exécute une requête SQL de sélection pour récupérer un utilisateur dont le pseudo correspond à la valeur donnée.
     * La requête est limitée à un résultat (LIMIT 1). Si aucun utilisateur n'est trouvé, la méthode renvoie null.
     *
     * @param string $nickname Le pseudo de l'utilisateur.
     *
     * @return array{id_user: int, nickname: string, email: string, password: string, id_role: int}|null les informations de l'utilisateur, ou null si aucun utilisateur n'est trouvé.
     */
    public function getUserByNickname(string $nickname): ?array
    {
        $sql  = "SELECT * FROM zell_users WHERE nickname = :nickname LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':nickname' => $nickname]);

        /** @var array{id_user: int, nickname: string, email: string, password: string, id_role: int}|null|false $user */
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        return $user !== false ? $user : null;
    }

    /**
     * Récupère les informations d'un utilisateur à partir de son email.
     *
     * Exécute une requête SQL de sélection pour récupérer un utilisateur dont l'email correspond à la valeur donnée.
     * La requête est limitée à un résultat (LIMIT 1). Si aucun utilisateur n'est trouvé, la méthode renvoie null.
     *
     * @param string $email L'email de l'utilisateur.
     *
     * @return array{id_user: int, nickname: string, email: string, password: string, id_role: int}|null les informations de l'utilisateur, ou null si aucun utilisateur n'est trouvé.
     */
    public function getUserByEmail(string $email): ?array
    {
        $sql  = "SELECT * FROM zell_users WHERE email = :email LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':email' => $email]);

        /** @var array{id_user: int, nickname: string, email: string, password: string, id_role: int}|null|false $user */
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        return $user !== false ? $user : null;
    }
}
