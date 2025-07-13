/**
 * @description Animation pour la liste d'éléments
 * Chaque élément de la liste apparaîtra avec une animation de fondu et de translation avec un léger délai pour créer un effet de cascade
 * L'animation est déclenchée au chargement de la page
 */
document.addEventListener("DOMContentLoaded", function () {
    const list = document.querySelector("ul.mb-5");
    if (!list) return;

    const items = Array.from(list.children);

    // On cache tous les items au départ
    items.forEach((item) => {
        item.style.opacity = "0";
        item.style.transform = "translateY(10px)";
        item.style.transition = "opacity 0.4s ease, transform 0.4s ease";
    });

    // Puis on les affiche un à un avec un décalage
    items.forEach((item, index) => {
        setTimeout(() => {
            item.style.opacity = "1";
            item.style.transform = "translateY(0)";
        }, index * 150); // délai de 150ms entre chaque
    });
});
