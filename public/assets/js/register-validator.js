/**
 * Validation du formulaire d'inscription (register)
 * Affiche les erreurs sous chaque champ, en cohérence avec la validation PHP.
 */

document.addEventListener("DOMContentLoaded", function () {
    const form = document.querySelector('form[action="register"]');
    if (!form) return;

    form.addEventListener("submit", function (e) {
        // Nettoyage des anciens messages d'erreur
        form.querySelectorAll(".js-error").forEach(
            (el) => (el.textContent = ""),
        );

        let hasError = false;

        // Champs
        const nickname = form.querySelector('input[name="nickname"]');
        const email = form.querySelector('input[name="email"]');
        const password = form.querySelector('input[name="password"]');
        const confirmPassword = form.querySelector(
            'input[name="confirm-password"]',
        );

        // Pseudo requis
        if (!nickname.value.trim()) {
            showError(nickname, "Le champ pseudo est obligatoire.");
            hasError = true;
        } else if (!/^\w{4,}$/.test(nickname.value)) {
            showError(
                nickname,
                "Votre pseudo doit contenir au moins 4 caractères alphanumériques sans espaces ni caractères spéciaux (sauf underscore)!",
            );
            hasError = true;
        }

        // Email requis
        if (!email.value.trim()) {
            showError(email, "Le champ email est obligatoire.");
            hasError = true;
        } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value)) {
            showError(email, "L'email n'est pas valide.");
            hasError = true;
        }

        // Mot de passe requis
        if (!password.value.trim()) {
            showError(password, "Le champ mot de passe est obligatoire.");
            hasError = true;
        } else if (
            !/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{12,}$/.test(
                password.value,
            )
        ) {
            showError(
                password,
                "Le mot de passe doit contenir au moins un nombre, une lettre majuscule, une minuscule, un caractère spécial et comporter au moins 12 caractères",
            );
            hasError = true;
        }

        // Confirmation requise
        if (!confirmPassword.value.trim()) {
            showError(confirmPassword, "La confirmation est obligatoire.");
            hasError = true;
        } else if (password.value !== confirmPassword.value) {
            showError(
                confirmPassword,
                "Les mots de passe ne correspondent pas.",
            );
            hasError = true;
        }

        if (hasError) {
            e.preventDefault();
        }
    });

    function showError(input, message) {
        let placeholder = input.parentNode.querySelector(".js-error");
        if (placeholder) {
            placeholder.textContent = message;
        }
    }
});
