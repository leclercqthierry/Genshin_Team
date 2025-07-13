/**
 * @description Ajoute une flèche à droite des champs de saisie lorsqu'ils sont actifs qui sera animée horizontalement
 * L'animation est déclenchée au focus et retirée au blur
 */

document.querySelectorAll(".input-wrapper").forEach((wrapper) => {
    const input = wrapper.querySelector("input");

    input.addEventListener("focus", () => {
        wrapper.classList.add("arrow-indicator");
    });

    input.addEventListener("blur", () => {
        wrapper.classList.remove("arrow-indicator");
    });
});
