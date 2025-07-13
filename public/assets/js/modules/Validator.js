export class Validator {
    constructor() {
        this.errors = {};
    }

    validateRequired(field, value, message) {
        if (value === null || value.trim() === "") {
            this.errors[field] = message;
        }
    }

    validateEmail(field, value, message) {
        const emailPattern = /^[^@\s]+@[^@\s]+\.[^@\s]+$/;
        if (value === null || !emailPattern.test(value.trim())) {
            this.errors[field] = message;
        }
    }

    validateMatch(field, value1, value2, message) {
        if (value1 !== value2) {
            this.errors[field] = message;
        }
    }

    validateMinLength(field, value, min, message) {
        if (value === null || value.trim().length < min) {
            this.errors[field] = message;
        }
    }

    validatePattern(field, value, pattern, message) {
        const regex = new RegExp(pattern);
        if (value === null || !regex.test(value)) {
            this.errors[field] = message;
        }
    }

    setError(field, message) {
        this.errors[field] = message;
    }

    hasErrors() {
        return Object.keys(this.errors).length > 0;
    }

    getErrors() {
        return this.errors;
    }

    clearErrors() {
        this.errors = {};
    }

    /**
     * Initialise la logique du formulaire : validation, état du bouton submit et affichage des erreurs.
     *
     * @param {Object} params - Paramètres de configuration.
     * @param {string} params.formSelector - Sélecteur CSS du formulaire à initialiser.
     * @param {string} params.inputName - Nom de l'input à surveiller.
     * @param {string} params.actionEdit - Valeur de l'attribut "action" indiquant le mode édition.
     * @param {Array<Function>} params.rules - Tableau de fonctions de validation. Chaque fonction reçoit (inputName, value, validator).
     *
     * @static
     * @returns {void}
     */
    static setupForm({ formSelector, inputName, actionEdit, rules }) {
        const form = document.querySelector(formSelector);
        if (!form) return;

        const isEditMode = form.getAttribute("action") === actionEdit;
        const input = form.querySelector(`input[name="${inputName}"]`);
        const submitBtn = form.querySelector('button[type="submit"]');
        const errorBox = form.querySelector(".js-error");

        const initialValue = input.value.trim();
        submitBtn.disabled = true;

        function hasChanged() {
            return input.value.trim() !== initialValue;
        }

        function hasContent() {
            return input.value.trim().length > 0;
        }

        function updateSubmitState() {
            if (isEditMode) {
                submitBtn.disabled = !(hasChanged() && hasContent());
            } else {
                submitBtn.disabled = !hasContent();
            }
            if (errorBox) errorBox.textContent = "";
        }

        function runValidation(value) {
            const validator = new Validator();
            for (const rule of rules) {
                rule(inputName, value, validator);
                if (validator.hasErrors()) break;
            }
            return validator;
        }

        form.addEventListener("submit", function (e) {
            const value = input.value.trim();
            const validator = runValidation(value);
            const errors = validator.getErrors();

            if (validator.hasErrors()) {
                e.preventDefault();
                if (errorBox) errorBox.textContent = errors[inputName] || "";
            }
        });

        input.addEventListener("input", updateSubmitState);
        input.addEventListener("focus", updateSubmitState);

        updateSubmitState();
    }
}
