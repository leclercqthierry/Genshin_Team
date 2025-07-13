import { Validator } from "./modules/Validator.js";

Validator.setupForm({
    formSelector: 'form[action="add-obtaining"], form[action="edit-obtaining"]',
    inputName: "obtaining",
    actionEdit: "edit-obtaining",
    rules: [
        (field, value, v) =>
            v.validateRequired(
                field,
                value,
                "Le champ moyen d'obtention est obligatoire.",
            ),
        (field, value, v) =>
            v.validateMinLength(
                field,
                value,
                4,
                "Le moyen d'obtention doit avoir au moins 4 caractères.",
            ),
        (field, value, v) =>
            v.validatePattern(
                field,
                value,
                "^[\\p{L}\\s]+$",
                "Lettres (et accent) et espaces uniquement.",
            ),
    ],
});
