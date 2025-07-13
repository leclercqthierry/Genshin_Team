/**
 * Ce script gère la validation du formulaire de réinitialisation de mot de passe.
 * Il utilise la classe Validator pour valider le champ email en temps réel.
 * Il affiche les erreurs sous le champ email et désactive le bouton de soumission si des erreurs sont présentes.
 */
import { Validator } from "./modules/Validator.js";

document.addEventListener("DOMContentLoaded", function () {
    const form = document.querySelector('form[action="forgot-password"]');
    if (!form) return;

    const emailInput = form.querySelector('input[name="email"]');
    const submitBtn = form.querySelector('button[type="submit"]');
    const errorEl = emailInput.parentNode.querySelector(".js-error");

    const validator = new Validator();
    submitBtn.disabled = true;

    function validateEmailField() {
        validator.clearErrors();
        const email = emailInput.value;

        // Validation
        validator.validateRequired(
            "email",
            email,
            "Veuillez entrer votre email.",
        );
        validator.validateEmail("email", email, "Format d’email invalide.");

        // Affichage de l’erreur
        const error = validator.getErrors().email || "";
        if (errorEl) errorEl.textContent = error;

        // Activation du bouton
        submitBtn.disabled = validator.hasErrors();
    }

    // Validation live
    emailInput.addEventListener("input", validateEmailField);
    emailInput.addEventListener("blur", validateEmailField);

    // Dernière vérification au submit
    form.addEventListener("submit", function (e) {
        validateEmailField();
        if (validator.hasErrors()) {
            e.preventDefault();
        }
    });
});
