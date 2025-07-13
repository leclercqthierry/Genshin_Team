import { Validator } from "./modules/Validator.js";

document.addEventListener("DOMContentLoaded", function () {
    const form = document.querySelector('form[action="reset-password"]');
    if (!form) return;

    const inputs = {
        password: form.querySelector('input[name="password"]'),
        confirmPassword: form.querySelector('input[name="confirm-password"]'),
    };

    const touched = {
        password: false,
        confirmPassword: false,
    };

    const submitBtn = form.querySelector('button[type="submit"]');
    submitBtn.disabled = true;

    const validator = new Validator();

    function runValidation(fieldName) {
        validator.clearErrors();

        const password = inputs.password.value;
        const confirmPassword = inputs.confirmPassword.value;

        // Validation ciblée
        switch (fieldName) {
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
                    "Confirmation obligatoire.",
                );
                validator.validateMatch(
                    "confirm-password",
                    password,
                    confirmPassword,
                    "Les mots de passe ne correspondent pas.",
                );
                break;
        }

        // Affichage de l'erreur ciblée
        const error =
            validator.getErrors()[
                fieldName === "confirmPassword"
                    ? "confirm-password"
                    : "password"
            ];
        const input = inputs[fieldName];
        const errorEl = input?.parentNode?.querySelector(".js-error");
        if (errorEl) errorEl.textContent = error || "";

        // Activation du bouton seulement si tout est touché et valide
        submitBtn.disabled =
            Object.entries(touched).some(([field, value]) => !value) ||
            validator.hasErrors();
    }

    // Validation live
    Object.entries(inputs).forEach(([fieldName, input]) => {
        input.addEventListener("input", () => runValidation(fieldName));
        input.addEventListener("blur", () => runValidation(fieldName));
    });

    // Validation finale au submit
    form.addEventListener("submit", function (e) {
        runValidation("password");
        runValidation("confirmPassword");
        if (validator.hasErrors()) {
            e.preventDefault();
        }
    });
});
