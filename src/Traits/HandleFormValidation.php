<?php
namespace GenshinTeam\Traits;

use GenshinTeam\Validation\Validator;

trait HandleFormValidation
{
    /**
     * Vérifie si une validation a échoué et gère l'erreur si nécessaire.
     *
     * @param string   $field        Champ à valider (ex: 'email')
     * @param mixed    $value        Valeur à conserver
     * @param Validator $validator   Instance du validateur
     * @param string   $errorKey     Clé de l'erreur attendue dans getErrors()
     * @param callable $formCallback Fonction d'affichage du formulaire
     *
     * @return bool true si pas d'erreur, false sinon
     */
    private function handleValidationIfError(string $field, $value, Validator $validator, string $errorKey, callable $formCallback): bool
    {
        if (! $validator->hasErrors()) {
            return true; // pas d'erreur, continue
        }

        $this->addError($field, $validator->getErrors()[$errorKey] ?? 'Erreur de validation');
        $this->setOld([$field => $value]);
        $formCallback(); // ex. showForm() ou showEditForm()
        return false;    // on interrompt le flux
    }
}
