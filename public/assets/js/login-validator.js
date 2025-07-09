/**
 * Validation du formulaire de connexion
 * @description Ce script valide le formulaire de connexion en s'assurant que les champs pseudo et mot de passe sont remplis.
 * Il affiche des messages d'erreur si les champs sont vides.
 */

document.addEventListener("DOMContentLoaded", function () {
    const form = document.querySelector('form[action="login"]');
    if (!form) return;

    form.addEventListener("submit", function (e) {
        // Nettoyage des anciens messages d'erreur
        form.querySelectorAll(".js-error").forEach(
            (el) => (el.textContent = ""),
        );

        let hasError = false;

        // Validation du pseudo
        const nickname = form.querySelector('input[name="nickname"]');
        if (!nickname.value.trim()) {
            showError(nickname, "Veuillez renseigner votre pseudo.");
            hasError = true;
        }

        // Validation du mot de passe
        const password = form.querySelector('input[name="password"]');
        if (!password.value.trim()) {
            showError(password, "Veuillez renseigner votre mot de passe.");
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
