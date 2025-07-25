/**
 * Ce fichier gère la validation du formulaire d'inscription.
 * Il utilise la classe Validator pour valider les champs du formulaire.
 * Chaque champ est validé à la fois lors de la saisie et à la soumission du formulaire.
 * Les erreurs sont affichées en temps réel sous chaque champ.
 * Le bouton de soumission est désactivé tant que des erreurs sont présentes ou que des champs requis ne sont pas remplis.
 */
import { Validator } from "./modules/Validator.js";

document.addEventListener("DOMContentLoaded", function () {
    const form = document.querySelector('form[action="register"]');
    if (!form) return;

    const inputs = {
        nickname: form.querySelector('input[name="nickname"]'),
        email: form.querySelector('input[name="email"]'),
        password: form.querySelector('input[name="password"]'),
        confirmPassword: form.querySelector('input[name="confirm-password"]'),
    };

    const touched = {
        nickname: false,
        email: false,
        password: false,
        confirmPassword: false,
    };

    const submitBtn = form.querySelector('button[type="submit"]');
    submitBtn.disabled = true;

    const validator = new Validator();

    /**
     * Fonction de validation des champs du formulaire.
     * @param {string} fieldName - Le nom du champ à valider.
     */
    function runValidation(fieldName) {
        validator.clearErrors();

        const nickname = inputs.nickname.value;
        const email = inputs.email.value;
        const password = inputs.password.value;
        const confirmPassword = inputs.confirmPassword.value;

        // Valider uniquement le champ passé en paramètre
        switch (fieldName) {
            case "nickname":
                touched.nickname = true;
                validator.validateRequired(
                    "nickname",
                    nickname,
                    "Pseudo requis.",
                );
                validator.validatePattern(
                    "nickname",
                    nickname,
                    "^\\w{4,}$",
                    "Votre pseudo doit contenir au moins 4 caractères alphanumériques sans espaces ni caractères spéciaux (sauf underscore)!.",
                );
                break;

            case "email":
                touched.email = true;
                validator.validateRequired("email", email, "Email requis.");
                validator.validateEmail("email", email, "Email invalide.");
                break;

            case "password":
                touched.password = true;
                validator.validateRequired(
                    "password",
                    password,
                    "Mot de passe requis.",
                );
                validator.validatePattern(
                    "password",
                    password,
                    "^(?=.*[a-z])(?=.*[A-Z])(?=.*\\d)(?=.*[@$!%*?&]).{12,}$",
                    "'Le mot de passe doit contenir au moins un nombre, une lettre majuscule, une minuscule, un caractère spécial et comporter au moins 12 caractères'",
                );
                break;

            case "confirmPassword":
                touched.confirmPassword = true;
                validator.validateRequired(
                    "confirm-password",
                    confirmPassword,
                    "Confirmation requise.",
                );
                validator.validateMatch(
                    "confirm-password",
                    password,
                    confirmPassword,
                    "Les mots de passe ne correspondent pas.",
                );
                break;
        }

        // Affichage de l'erreur uniquement pour le champ en question
        const error =
            validator.getErrors()[
                fieldName === "confirmPassword" ? "confirm-password" : fieldName
            ];
        const input = inputs[fieldName];
        const errorEl = input?.parentNode?.querySelector(".js-error");
        if (errorEl) errorEl.textContent = error || "";

        // Mise à jour du bouton submit
        submitBtn.disabled =
            Object.entries(touched).some(([field, value]) => !value) ||
            validator.hasErrors();
    }

    Object.entries(inputs).forEach(([fieldName, input]) => {
        input.addEventListener("input", () => runValidation(fieldName));
        input.addEventListener("blur", () => runValidation(fieldName));
    });

    // Validation finale au submit
    form.addEventListener("submit", function (e) {
        runValidation();
        if (validator.hasErrors()) {
            e.preventDefault();
        }
    });
});
