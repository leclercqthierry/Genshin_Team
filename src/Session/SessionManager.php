<?php
declare (strict_types = 1);

namespace GenshinTeam\Session;

/**
 * Gestionnaire de session HTTP.
 *
 * Cette classe encapsule les opérations standard de gestion de session PHP,
 * telles que l'ouverture, la lecture, l'écriture, la destruction et la récupération de données utilisateur.
 *
 * @package GenshinTeam\Session
 */
class SessionManager
{
    public function __construct()
    {
        $this->start();
    }

    /**
     * Démarre la session si elle n'est pas déjà active.
     *
     * @return void
     */
    public function start(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
    }

    /**
     * Vérifie si la session est actuellement active.
     *
     * @return bool True si la session est démarrée, false sinon.
     */
    public function isStarted(): bool
    {
        return session_status() === PHP_SESSION_ACTIVE;
    }

    /**
     * Récupère une valeur depuis la session.
     *
     * @param string $key     Clé de la variable de session.
     * @param mixed  $default Valeur de repli si la clé n'existe pas.
     *
     * @return mixed La valeur associée à la clé ou la valeur par défaut.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    /**
     * Enregistre une valeur dans la session.
     *
     * @param string $key   Clé sous laquelle la valeur sera stockée.
     * @param mixed  $value Valeur à stocker.
     *
     * @return void
     */
    public function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    /**
     * Supprime une entrée de la session.
     *
     * @param string $key Clé à supprimer.
     *
     * @return void
     */
    public function remove(string $key): void
    {
        unset($_SESSION[$key]);
    }

    /**
     * Vide toutes les données de la session en cours.
     *
     * @return void
     */
    public function clear(): void
    {
        $_SESSION = [];
    }

    /**
     * Détruit la session en cours, si elle est démarrée.
     *
     * @return void
     */
    public function destroy(): void
    {
        if ($this->isStarted()) {
            session_destroy();
            $_SESSION = [];

        }
    }
}
