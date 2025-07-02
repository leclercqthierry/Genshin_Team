<?php
namespace GenshinTeam\Models;

/**
 * Interface définissant les opérations CRUD de base pour un modèle.
 *
 * Chaque implémentation doit gérer :
 * - l’ajout d’une valeur simple (souvent un nom, libellé ou statistique)
 * - la modification d’un enregistrement existant
 * - la récupération d’un ou plusieurs enregistrements
 * - la suppression d’un enregistrement par son identifiant
 */
interface CrudModelInterface
{
    /**
     * Ajoute une nouvelle valeur dans la table.
     *
     * @param string $value Valeur principale à insérer
     * @return bool True si succès, false en cas d'échec
     */
    public function add(string $value): bool;

    /**
     * Met à jour la valeur d’un enregistrement existant.
     *
     * @param int $id Identifiant à mettre à jour
     * @param string $value Nouvelle valeur
     * @return bool True si la mise à jour est réussie
     */
    public function update(int $id, string $value): bool;

    /**
     * Récupère tous les enregistrements disponibles.
     *
     * @return array<array<string, mixed>> Liste des lignes sous forme de tableaux associatifs
     */
    public function getAll(): array;

    /**
     * Récupère un enregistrement unique par son ID.
     *
     * @param int $id Identifiant de la ligne
     * @return array<string, mixed>|null Tableau associatif ou null si non trouvé
     */
    public function get(int $id): ?array;

    /**
     * Supprime un enregistrement par son ID.
     *
     * @param int $id Identifiant de la ligne à supprimer
     * @return bool True si la suppression a réussi
     */
    public function delete(int $id): bool;
}
