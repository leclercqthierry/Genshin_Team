import { Validator } from "./modules/Validator.js";

Validator.setupForm({
    formSelector: 'form[action="add-stat"], form[action="edit-stat"]',
    inputName: "stat",
    actionEdit: "edit-stat",
    rules: [
        (field, value, v) =>
            v.validateRequired(
                field,
                value,
                "Le champ statistique est obligatoire.",
            ),
        (field, value, v) =>
            v.validateMinLength(
                field,
                value,
                2,
                "La statistique doit avoir au moins 2 caractères.",
            ),
        (field, value, v) =>
            v.validatePattern(
                field,
                value,
                "^[\\w\\s%+]+$",
                "Lettres, chiffres, espaces, % ou + uniquement.",
            ),
    ],
});
