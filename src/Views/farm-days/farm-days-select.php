<?php
    /**
     * @var string $action      // ex: 'edit-farm-days' ou 'delete-farm-days'
     * @var string $fieldName   // ex: 'edit_id' ou 'delete_id'
     * @var string $buttonLabel // ex: 'Éditer' ou 'Supprimer'
     * @var string $title       // Titre du formulaire
     * @var array<int, array{id_farm_days: int|string, days: string}> $farmDays // Liste des jours de farm
     * @var array<string, string> $errors // Tableau des erreurs, ex: ['global' => 'message d’erreur']
     */

?>
<h1
    class="my-3.5 text-center font-[RobotoCondensed] text-2xl font-bold uppercase"
>
    <?php echo htmlspecialchars($title) ?>
</h1>

<?php if (! empty($errors['global'])): ?>
    <div role="alert" class="mb-4 rounded border border-red-500 bg-red-100 p-4 text-red-700">
        <?php echo htmlspecialchars($errors['global']); ?>
    </div>
<?php endif; ?>

<form
    method="post"
    action="<?php echo htmlspecialchars($action) ?>"
    class="mb-5 flex flex-col items-center justify-around gap-[15px] rounded-[20px] bg-(--bg-secondary) px-[15px] py-[30px]"
>
    <select
        name="<?php echo htmlspecialchars($fieldName) ?>"
        required
        class="rounded border border-gray-300 bg-(--font-color) p-2 text-(--bg-primary) shadow-sm focus:ring-blue-500"
    >
        <?php foreach ($farmDays as $row): ?>
        <option value="<?php echo $row['id_farm_days'] ?>">
            <?php echo htmlspecialchars($row['days']) ?>
        </option>
        <?php endforeach; ?>
    </select>
    <button
        type="submit"
        class="font-(Roboto) h-8 w-[140px] rounded-[50px] border-0 bg-(--bg-primary) text-(--font-color) transition-colors duration-400 ease-in-out hover:cursor-pointer hover:bg-(--font-color) hover:text-(--bg-primary)"
    >
        <?php echo htmlspecialchars($buttonLabel) ?>
    </button>
</form>
