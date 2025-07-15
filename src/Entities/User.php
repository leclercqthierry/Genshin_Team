<?php
declare (strict_types = 1);

namespace GenshinTeam\Entities;

/**
 * Représente un utilisateur de l’application.
 *
 * Cette classe encapsule les données de l'utilisateur sans dépendance technique (pas de PDO, pas de Logger).
 * Elle est idéale pour être utilisée dans le domaine métier ou dans les vues.
 */
class User
{
    private int $id;
    private string $nickname;
    private string $email;
    private string $password;
    private int $role;

    /**
     * @param int    $id     Identifiant unique de l'utilisateur
     * @param string $nickname  Pseudo ou nom d'utilisateur
     * @param string $email  Adresse email de l'utilisateur
     * @param string $password  Mot de passe de l'utilisateur
     * @param int    $role     Rôle de l'utilisateur (1: admin, 2: utilisateur)
     */
    public function __construct(int $id, string $nickname, string $email, string $password, int $role = 2)
    {
        $this->id       = $id;
        $this->nickname = $nickname;
        $this->email    = $email;
        $this->password = $password;
        $this->role     = $role;
    }

    /**
     * Obtient l'identifiant unique de l'utilisateur.
     *
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Obtient l'adresse email de l'utilisateur.
     *
     * @return string
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * Obtient le nickname de l'utilisateur
     *
     * @return string
     */
    public function getNickname(): string
    {
        return $this->nickname;
    }

    /**
     * Obtient le mot de passe de l'utilisateur
     *
     * @return string
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     * Obtient le role de l'utilisateur
     *
     * @return int
     */
    public function getRole(): int
    {
        return $this->role;
    }

    /**
     * Crée une instance de User à partir d'un tableau de données.
     *
     * @param array{id_user: int, nickname: string, email: string, password: string, id_role: int} $data
     * @return self
     */
    public static function fromDatabase(array $data): self
    {
        return new self(
            $data['id_user'],
            $data['nickname'],
            $data['email'],
            $data['password'],
            $data['id_role']
        );
    }

}
