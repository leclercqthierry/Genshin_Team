/**
 * Ce script gère l'activation du bouton de soumission
 * dans le formulaire d'ajout/édition des jours de farm.
 * Il est actif uniquement si au moins une case est cochée
 * et, en plus, en mode édition, si une modification a été faite.
 */
document.addEventListener("DOMContentLoaded", function () {
    let form = document.querySelector(
        'form[action="add-farm-days"], form[action="edit-farm-days"]',
    );

    console.log(form);
    if (!form) return;

    const isEditMode = form.getAttribute("action") === "edit-farm-days";

    const checkboxes = form.querySelectorAll('input[type="checkbox"]');
    const submitBtn = form.querySelector('button[type="submit"]');

    // Par défaut, toujours désactivé
    submitBtn.disabled = true;

    let initialState = Array.from(checkboxes).map((cb) => cb.checked);

    function hasChanged() {
        return Array.from(checkboxes).some(
            (cb, i) => cb.checked !== initialState[i],
        );
    }

    function hasChecked() {
        return Array.from(checkboxes).some((cb) => cb.checked);
    }

    function updateSubmitState() {
        if (isEditMode) {
            // En édition → bouton actif uniquement si modification ET au moins une case cochée
            submitBtn.disabled = !(hasChanged() && hasChecked());
        } else {
            // En ajout → bouton actif dès qu'une case est cochée
            submitBtn.disabled = !hasChecked();
        }
    }

    // Écoute des interactions
    checkboxes.forEach((cb) => {
        cb.addEventListener("change", updateSubmitState);
    });

    // Vérifie l'état initial
    updateSubmitState();
});
