/**
 * Validation du formulaire de connexion
 * @description Ce script valide le formulaire de connexion en s'assurant que les champs pseudo et mot de passe sont remplis.
 * Il utilise la classe Validator pour valider les champs et activer/désactiver le bouton de soumission.
 */
import { Validator } from "./modules/Validator.js";

// On attend que le DOM soit chargé avant d'ajouter les écouteurs d'événements
// pour éviter les erreurs de sélection d'éléments non présents.
document.addEventListener("DOMContentLoaded", function () {
    const form = document.querySelector('form[action="login"]');
    if (!form) return;

    const nicknameInput = form.querySelector('input[name="nickname"]');
    const passwordInput = form.querySelector('input[name="password"]');
    const submitBtn = form.querySelector('button[type="submit"]');

    const validator = new Validator();

    function checkFilled() {
        validator.clearErrors();

        // On valide juste que les champs ne sont pas vides
        validator.validateRequired("nickname", nicknameInput.value, "");
        validator.validateRequired("password", passwordInput.value, "");

        // Le bouton est activé uniquement si les deux champs sont remplis
        submitBtn.disabled = validator.hasErrors();
    }

    // On observe la saisie en live
    nicknameInput.addEventListener("input", checkFilled);
    passwordInput.addEventListener("input", checkFilled);

    // Initialisation à l’arrivée sur le formulaire
    checkFilled();
});
