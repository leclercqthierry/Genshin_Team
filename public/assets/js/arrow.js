/**
 * @description Ajoute une flèche à droite des champs de saisie lorsqu'ils sont actifs
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
