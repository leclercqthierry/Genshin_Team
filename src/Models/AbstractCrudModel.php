<?php
declare (strict_types = 1);

namespace GenshinTeam\Models;

use PDO;
use Psr\Log\LoggerInterface;

/**
 * Modèle CRUD abstrait générique pour les entités simples.
 *
 * Les classes filles doivent définir :
 * - $table : nom de la table SQL
 * - $idField : nom du champ de l'identifiant primaire
 * - $nameField : champ principal manipulé (souvent un nom ou une valeur descriptive)
 */
abstract class AbstractCrudModel implements CrudModelInterface
{
    protected PDO $pdo;

    protected LoggerInterface $logger;

    /** @var string Nom de la table (défini dans la classe fille) */
    protected string $table;

    /** @var string Nom de la colonne représentant l'identifiant */
    protected string $idField;

    /** @var string Nom de la colonne manipulée (ex : nom, libellé, etc.) */
    protected string $nameField;

    /**
     * @param PDO $pdo Instance PDO injectée
     * @param LoggerInterface $logger Logger PSR-3 injecté
     */
    public function __construct(PDO $pdo, LoggerInterface $logger)
    {
        $this->pdo    = $pdo;
        $this->logger = $logger;
    }

    /**
     * Ajoute un nouvel enregistrement dans la table.
     *
     * @param string $name Valeur à insérer dans le champ $nameField
     * @return bool True si succès, False sinon (avec log)
     */
    public function add(string $name): bool
    {
        try {
            $stmt = $this->pdo->prepare(
                "INSERT INTO {$this->table} ({$this->nameField}) VALUES (:name)"
            );
            $result = $stmt->execute(['name' => $name]);

            if (! $result) {
                $this->logger->error("Échec de l'insertion dans {$this->table}");
            }

            return $result;
        } catch (\Throwable $e) {
            $this->logger->error($e->getMessage());
            return false;
        }
    }

    /**
     * Récupère tous les enregistrements triés par $nameField.
     *
     * @return array<array<string, mixed>> Tableau associatif des lignes ou tableau vide en cas d'échec
     */
    public function getAll(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM {$this->table} ORDER BY {$this->nameField}");

        if ($stmt === false) {
            $this->logger->error("Échec de récupération des données dans {$this->table}");
            return [];
        }

        /** @var array<array<string, mixed>> $result */
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $result;

    }

    /**
     * Récupère un enregistrement par son ID.
     *
     * @param int $id Identifiant de l'enregistrement
     * @return array<string, mixed>|null Données de la ligne ou null si non trouvé
     */
    public function get(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM {$this->table} WHERE {$this->idField} = :id"
        );
        $stmt->execute(['id' => $id]);

        /** @var array<string, mixed>|false $row */
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return is_array($row) ? $row : null;
    }

    /**
     * Met à jour la valeur du champ principal pour un ID donné.
     *
     * @param int $id Identifiant à modifier
     * @param string $name Nouvelle valeur
     * @return bool True si mise à jour effectuée avec succès
     */
    public function update(int $id, string $name): bool
    {
        $stmt = $this->pdo->prepare(
            "UPDATE {$this->table} SET {$this->nameField} = :name WHERE {$this->idField} = :id"
        );

        return $stmt->execute([
            'name' => $name,
            'id'   => $id,
        ]);
    }

    /**
     * Supprime un enregistrement par son identifiant.
     *
     * @param int $id Identifiant à supprimer
     * @return bool True si suppression réussie
     */
    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare(
            "DELETE FROM {$this->table} WHERE {$this->idField} = :id"
        );

        return $stmt->execute(['id' => $id]);
    }
}
