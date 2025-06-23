/**
 * Menu burger
 * @description Gère l'affichage du menu burger sur mobile
 */

/* Déclaration des constantes */
const menuToggle = document.getElementById("menu-toggle");
const menu = document.getElementById("menu");

// Ce sont les 3 barres du menu burger
const bar1 = document.getElementById("bar1");
const bar2 = document.getElementById("bar2");
const bar3 = document.getElementById("bar3");

menuToggle.addEventListener("click", function () {
    menu.classList.toggle("hidden");
    menu.classList.toggle("flex");

    // Animation du bouton burger en croix en utilisant les classes Tailwind CSS
    bar1.classList.toggle("rotate-45");
    bar1.classList.toggle("translate-y-2");
    bar3.classList.toggle("-rotate-45");
    bar3.classList.toggle("-translate-y-2");
    bar2.classList.toggle("opacity-0");
});
