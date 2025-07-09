<?php
declare (strict_types = 1);

namespace GenshinTeam\Validation;

/**
 * Valide des champs de formulaire en appliquant différentes règles
 * (champ requis, email valide, longueur minimale, format avec regex, etc.)
 * et stocke les erreurs éventuelles associées.
 */
class Validator
{
    /**
     * Tableau associatif des erreurs de validation par champ.
     *
     * @var array<string, string>
     */
    protected array $errors = [];

    /**
     * Vérifie qu'une valeur est renseignée et non vide.
     *
     * @param string      $field   Nom du champ.
     * @param string|null $value   Valeur saisie.
     * @param string      $message Message d'erreur en cas d'absence.
     */
    public function validateRequired(string $field, ?string $value, string $message): void
    {
        if ($value === null || trim($value) === '') {
            $this->errors[$field] = $message;
        }
    }

    /**
     * Vérifie que l'adresse email est valide.
     *
     * @param string      $field   Nom du champ.
     * @param string|null $email   Adresse email à vérifier.
     * @param string      $message Message d'erreur en cas d'email invalide.
     */
    public function validateEmail(string $field, ?string $email, string $message): void
    {
        if ($email === null || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field] = $message;
        }
    }

    /**
     * Vérifie que deux champs ont une valeur identique.
     *
     * @param string      $field   Champ sur lequel associer l'erreur.
     * @param string|null $value1  Première valeur.
     * @param string|null $value2  Valeur à comparer.
     * @param string      $message Message en cas de non-correspondance.
     */
    public function validateMatch(string $field, ?string $value1, ?string $value2, string $message): void
    {
        if ($value1 !== $value2) {
            $this->errors[$field] = $message;
        }
    }

    /**
     * Vérifie qu'une chaîne respecte une longueur minimale.
     *
     * @param string      $field   Nom du champ concerné.
     * @param string|null $value   Valeur à analyser.
     * @param int         $min     Longueur minimale requise.
     * @param string      $message Message d'erreur en cas d'insuffisance.
     */
    public function validateMinLength(string $field, ?string $value, int $min, string $message): void
    {
        if ($value === null || strlen(trim($value)) < $min) {
            $this->errors[$field] = $message;
        }
    }

    /**
     * Vérifie qu'une valeur respecte une expression régulière donnée.
     *
     * @param string      $field   Champ à valider.
     * @param string|null $value   Valeur à tester.
     * @param string      $pattern Expression régulière à utiliser.
     * @param string      $message Message d'erreur si le format ne correspond pas.
     */
    public function validatePattern(string $field, ?string $value, string $pattern, string $message): void
    {
        if ($value === null || ! preg_match($pattern, $value)) {
            $this->errors[$field] = $message;
        }
    }

    /**
     * Détermine si des erreurs de validation ont été détectées.
     *
     * @return bool True s'il existe au moins une erreur.
     */
    public function hasErrors(): bool
    {
        return ! empty($this->errors);
    }

    /**
     * Retourne les erreurs de validation enregistrées.
     *
     * @return array<string, string> Tableau des erreurs indexé par nom de champ.
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Enregistre un message d’erreur pour un champ spécifique.
     *
     * @param string $field   Le nom du champ concerné par l’erreur.
     * @param string $message Le message d’erreur à associer.
     *
     * @return void
     */
    public function setError(string $field, string $message): void
    {
        $this->errors[$field] = $message;
    }

    /**
     * Assainit une valeur en la convertissant en entier si elle est numérique,
     * sinon retourne une valeur par défaut spécifiée.
     *
     * @param mixed $value   La valeur à nettoyer.
     * @param int   $default La valeur retournée si l’entrée n’est pas numérique (par défaut : 0).
     *
     * @return int La valeur entière nettoyée ou la valeur par défaut.
     */
    public static function sanitizeValue($value, int $default = 0): int
    {
        return is_numeric($value) ? (int) $value : $default;
    }
}
