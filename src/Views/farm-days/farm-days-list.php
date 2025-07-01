<?php
    /**
     * @var array<int, array{id_farm_days: int|string, days: string}> $farmDays
     */
?>

<h1 class="my-3.5 font-[RobotoCondensed] text-2xl font-bold uppercase">
    Liste des jours de farm
</h1>

<?php if (empty($farmDays)): ?>
<p>Aucun jour de farm enregistré.</p>
<?php else: ?>
<ul class="mb-5 flex flex-col gap-2">
    <?php foreach ($farmDays as $row): ?>
    <li
        class="flex items-center justify-between gap-2 rounded bg-gray-100 px-4 py-2"
    >
        <span class="text-(--bg-primary)"
            ><?php echo htmlspecialchars($row['days']) ?></span
        >
        <span class="text-xs text-(--bg-primary)"
            >ID:                 <?php echo $row['id_farm_days'] ?></span
        >
    </li>
    <?php endforeach; ?>
</ul>
<?php endif; ?>
