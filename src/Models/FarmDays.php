<?php
declare (strict_types = 1);

namespace GenshinTeam\Models;

use PDO;
use Psr\Log\LoggerInterface;

/**
 * Gère les opérations CRUD liées à la table zell_farm_days.
 */
class FarmDays
{
    private PDO $pdo;
    private LoggerInterface $logger;

    /**
     * Constructeur du modèle FarmDays.
     *
     * @param PDO $pdo Instance PDO connectée à la base de données.
     * @param LoggerInterface $logger Logger pour journaliser les erreurs.
     */
    public function __construct(PDO $pdo, LoggerInterface $logger)
    {
        $this->pdo    = $pdo;
        $this->logger = $logger;
    }

    /**
     * Ajoute un jour de farm dans la table.
     *
     * @param string $days Nom du jour à ajouter (ex. "Lundi").
     * @return bool True si l'insertion réussit, false sinon.
     */
    public function add(string $days): bool
    {
        try {
            $stmt   = $this->pdo->prepare('INSERT INTO zell_farm_days (days) VALUES (:days)');
            $result = $stmt->execute(['days' => $days]);

            if (! $result) {
                // Journaliser une erreur si l'exécution échoue
                $this->logger->error('Échec de l\'insertion dans zell_farm_days');
            }

            return $result;
        } catch (\Throwable $e) {
            // Journaliser les exceptions inattendues
            $this->logger->error($e->getMessage());
            return false;
        }
    }

    /**
     * Récupère tous les enregistrements de jours de farm.
     *
     * @return array<int, array{id_farm_days: int, days: string}> Liste associative des lignes.
     */
    public function getAll(): array
    {
        $stmt = $this->pdo->query('SELECT * FROM zell_farm_days');

        if ($stmt === false) {
            $this->logger->error('Échec de récupération des jours de farm');
            return [];
        }

        /** @var array<int, array{id_farm_days: int, days: string}> $result */
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $result;
    }

    /**
     * Récupère un jour de farm spécifique via son ID.
     *
     * @param int $id_farm_days Identifiant unique du jour.
     * @return array{id_farm_days: int, days: string}|null Données du jour ou null si introuvable.
     */
    public function get(int $id_farm_days): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM zell_farm_days WHERE id_farm_days = :id_farm_days');
        $stmt->execute(['id_farm_days' => $id_farm_days]);

        /** @var array{id_farm_days: int, days: string}|false $row */
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return is_array($row) ? $row : null;
    }

    /**
     * Met à jour un jour de farm existant.
     *
     * @param int $id_farm_days ID du jour à mettre à jour.
     * @param string $days Nouveau nom du jour.
     * @return bool True si la mise à jour a réussi.
     */
    public function update(int $id_farm_days, string $days): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE zell_farm_days SET days = :days WHERE id_farm_days = :id_farm_days'
        );

        return $stmt->execute([
            'days'         => $days,
            'id_farm_days' => $id_farm_days,
        ]);
    }

    /**
     * Supprime un jour de farm.
     *
     * @param int $id_farm_days ID du jour à supprimer.
     * @return bool True si la suppression réussit.
     */
    public function delete(int $id_farm_days): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM zell_farm_days WHERE id_farm_days = :id_farm_days');
        return $stmt->execute(['id_farm_days' => $id_farm_days]);
    }
}
