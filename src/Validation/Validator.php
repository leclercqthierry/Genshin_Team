<?php
declare (strict_types = 1);

namespace GenshinTeam\Validation;

/**
 * Classe Validator centralisant la validation des champs de formulaire.
 *
 * Permet de valider différents types de champs (requis, email, longueur, regex, etc.)
 * et de stocker les erreurs associées.
 *
 * @package GenshinTeam\Validation
 */
class Validator
{

    /**
     * Stocke les erreurs associées aux champs sous forme d'un tableau associatif.
     *
     * @var array<string, string>
     */
    protected array $errors = [];

    /**
     * Vérifie qu'une valeur est renseignée (non vide).
     *
     * @param string      $field   Nom du champ.
     * @param string|null $value   Valeur du champ.
     * @param string      $message Message d'erreur.
     *
     * @return void
     */
    public function validateRequired(string $field, ?string $value, string $message): void
    {
        if (null === $value || trim($value) === '') {
            $this->errors[$field] = $message;
        }
    }

    /**
     * Vérifie que la valeur correspond à un email valide.
     *
     * @param string      $field   Nom du champ.
     * @param string|null $email   Email à valider.
     * @param string      $message Message d'erreur.
     *
     * @return void
     */
    public function validateEmail(string $field, ?string $email, string $message): void
    {
        if (null === $email || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field] = $message;
        }
    }

    /**
     * Vérifie que deux valeurs correspondent (utile pour la confirmation d'un mot de passe par exemple).
     *
     * @param string      $field   Nom du champ associé à l'erreur.
     * @param string|null $value1  Première valeur.
     * @param string|null $value2  Seconde valeur.
     * @param string      $message Message d'erreur.
     *
     * @return void
     */
    public function validateMatch(string $field, ?string $value1, ?string $value2, string $message): void
    {
        if ($value1 !== $value2) {
            $this->errors[$field] = $message;
        }
    }

    /**
     * Vérifie qu'une valeur a au moins une longueur minimale donnée.
     *
     * @param string      $field   Nom du champ.
     * @param string|null $value   Valeur à vérifier.
     * @param int         $min     Longueur minimale requise.
     * @param string      $message Message d'erreur.
     *
     * @return void
     */
    public function validateMinLength(string $field, ?string $value, int $min, string $message): void
    {
        if (null === $value || strlen(trim($value)) < $min) {
            $this->errors[$field] = $message;
        }
    }

    /**
     * Vérifie que la valeur correspond à une expression régulière donnée.
     *
     * @param string      $field   Nom du champ.
     * @param string|null $value   Valeur à vérifier.
     * @param string      $pattern Expression régulière à appliquer.
     * @param string      $message Message d'erreur.
     *
     * @return void
     */
    public function validatePattern(string $field, ?string $value, string $pattern, string $message): void
    {
        if (null === $value || ! preg_match($pattern, $value)) {
            $this->errors[$field] = $message;
        }
    }

    /**
     * Indique si des erreurs ont été enregistrées.
     *
     * @return bool
     */
    public function hasErrors(): bool
    {
        return ! empty($this->errors);
    }

    /**
     * Retourne le tableau des erreurs.
     *
     * @return array<string, string>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
