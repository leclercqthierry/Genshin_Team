<?php
    /**
     * @var array{day?: string, global?: string} $errors
     * @var array{days?: list<string>} $old
     * @var string $mode
     * @var bool $isEdit
     * @var array<int, string> $jours
     * @var int|string|null $id
     */

    // Liste des jours sélectionnables
    $jours  = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'];
    $isEdit = ($mode === 'edit');

?>

<h1 class="my-3.5 font-[RobotoCondensed] text-2xl font-bold uppercase"><?php echo $isEdit ? 'Éditer les jours de farm' : 'Ajouter des jours de farm' ?></h1>

<?php
    // Affichage d'une erreur générale si elle existe
    if (isset($errors['global'])):
?>
    <div role="alert" class="mb-4 rounded border border-red-500 bg-red-100 p-4 text-red-700">
        <p id="error-global"><?php echo htmlspecialchars($errors['global']); ?></p>
    </div>
<?php endif; ?>

<?php //Formulaire pour sélectionner les jours de "farm" ?>
<form
    method="POST"
    action="<?php echo $isEdit ? 'edit-farm-days' : 'add-farm-days' ?>"
    class="mb-5 flex flex-col items-start justify-around gap-[15px] rounded-[20px] bg-(--bg-secondary) px-[15px] py-[30px]"
>
    <?php if ($isEdit && isset($id)): ?>
        <input type="hidden" name="edit_id" value="<?php echo htmlspecialchars((string) $id) ?>">
    <?php endif; ?>

    <p class="mb-4 text-sm text-(--font-color)">
        Cochez les jours de farm souhaités&nbsp;:
        <sup class="text-red-500"><b>*</b></sup>
    </p>

    <div class="form-group flex flex-col gap-2 w-full">
        <?php
            // Boucle sur chaque jour pour créer une checkbox
            foreach ($jours as $jour):
        ?>
            <label class="flex items-center gap-2 text-(--font-color)">
                <input
                    type="checkbox"
                    name="days[]"
                    value="<?php echo $jour; ?>"
                    class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500"
                    <?php
                        // Pré-coche les cases si le jour était sélectionné précédemment
                        if (! empty($old['days']) && in_array($jour, $old['days'], true)) {
                            echo 'checked';
                        }
                    ?>
                />
                <?php echo $jour; ?>
            </label>
        <?php endforeach; ?>
<?php
    // Affiche un message d'erreur spécifique aux jours
    if (isset($errors['day'])):
?>
            <p id="error-day" class="mt-1 text-xs text-pink-300">
                <?php echo htmlspecialchars($errors['day']); ?>
            </p>
        <?php endif; ?>
    </div>

    <div class="flex w-full justify-center mt-4">
        <button
            type="submit"
            class="font-(Roboto) h-8 w-[140px] rounded-[50px] border-0 bg-(--bg-primary) text-(--font-color) transition-colors duration-400 ease-in-out hover:cursor-pointer hover:bg-(--font-color) hover:text-(--bg-primary)"
        >
        <?php echo $isEdit ? 'Modifier' : 'Ajouter' ?>

        </button>
    </div>
</form>