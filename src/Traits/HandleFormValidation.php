<?php
namespace GenshinTeam\Traits;

use GenshinTeam\Validation\Validator;

trait HandleFormValidation
{

    /**
     * Affiche une erreur de validation pour un champ donné, conserve les données saisies
     * et affiche le formulaire correspondant via un callback.
     *
     * Cette méthode est utile lorsque la validation échoue et que l'on souhaite
     * interrompre le flux en signalant immédiatement une erreur utilisateur.
     *
     * @param string   $field        Le nom du champ concerné par l’erreur.
     * @param mixed    $value        La valeur initialement saisie par l’utilisateur.
     * @param Validator $validator   L’objet de validation contenant les éventuelles erreurs.
     * @param string   $errorKey     La clé d’erreur à récupérer depuis le validateur.
     * @param callable $formCallback Une fonction de rappel pour afficher le formulaire adéquat.
     *
     * @return void
     */
    private function showValidationError(string $field, $value, Validator $validator, string $errorKey, callable $formCallback): void
    {
        $this->addError($field, $validator->getErrors()[$errorKey] ?? 'Erreur de validation');
        $this->setOld([$field => $value]);
        $formCallback();
    }

}
