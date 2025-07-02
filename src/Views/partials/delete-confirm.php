<?php
    /**
     * Vue partagée de confirmation de suppression.
     *
     * @var string $title        Titre du bloc (ex: "Confirmer la suppression")
     * @var string $itemLabel    Libellé de l'élément à supprimer (ex: "statistique", "jour de farm")
     * @var string $itemName     Nom ou valeur à afficher (ex: $stat['name'], $farmDay['days'])
     * @var string $action       Action du formulaire (ex: "delete-stat", "delete-farm-days")
     * @var string $fieldName    Nom du champ caché pour l'ID (ex: "delete_id")
     * @var int|string $id       Valeur de l'ID
     * @var string $cancelUrl    URL pour le bouton "Annuler"
     */
?>
<h2 class="my-3.5 font-[RobotoCondensed] text-2xl font-bold uppercase">
    <?php echo htmlspecialchars($title) ?>
</h2>
<div
    class="mb-5 flex w-[270px] flex-col items-center justify-around gap-[15px] rounded-[20px] bg-(--bg-secondary) px-[15px] py-[30px] md:w-auto"
>
    <p>
        Voulez-vous vraiment supprimer :
        <b><?php echo htmlspecialchars($itemName) ?></b>
        <?php if (! empty($itemLabel)): ?>
            (<?php echo htmlspecialchars($itemLabel) ?>)
        <?php endif; ?>
        ?
    </p>
    <form
        method="post"
        action="<?php echo htmlspecialchars($action) ?>"
        class="flex items-center justify-center gap-3"
    >
        <input type="hidden" name="<?php echo htmlspecialchars($fieldName) ?>" value="<?php echo htmlspecialchars((string) $id) ?>" />
        <?php
            $token = $_SESSION['csrf_token'] ?? '';
            if (! is_string($token)) {
                $token = ''; // fallback safe
            }
        ?>
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($token); ?>">
        <button
            type="submit"
            name="confirm_delete"
            value="1"
            class="font-(Roboto) flex h-8 min-w-[108px] items-center justify-center rounded-[50px] border-0 bg-(--bg-primary) text-(--font-color) transition-colors duration-400 ease-in-out hover:cursor-pointer hover:bg-(--font-color) hover:text-(--bg-primary)"
        >
            Oui, supprimer
        </button>
        <a
            href="<?php echo htmlspecialchars($cancelUrl) ?>"
            class="font-(Roboto) flex h-8 min-w-[108px] items-center justify-center rounded-[50px] border-0 bg-(--bg-primary) text-(--font-color) transition-colors duration-400 ease-in-out hover:cursor-pointer hover:bg-(--font-color) hover:text-(--bg-primary)"
            >Annuler</a
        >
    </form>
</div>