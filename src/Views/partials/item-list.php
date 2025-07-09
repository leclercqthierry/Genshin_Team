<?php
    /**
     * @var string                      $title
     * @var array<int, array{id_stat: int, name: string}> $items
     * @var string                      $nameField
     * @var string                      $idField
     * @var string                      $emptyMessage
     */

?>
<h1 class="my-3.5 font-[RobotoCondensed] text-2xl font-bold uppercase text-center">
    <?php echo htmlspecialchars($title) ?>
</h1>

<?php if (empty($items)): ?>
<p><?php echo htmlspecialchars($emptyMessage) ?></p>
<?php else: ?>
<ul class="mb-5 flex flex-col gap-2">
    <?php foreach ($items as $row): ?>
    <li
        class="flex items-center justify-between gap-2 rounded bg-gray-100 px-4 py-2"
    >
        <span class="text-(--bg-primary)">
            <?php echo htmlspecialchars((string) $row[$nameField]) ?>
        </span>
        <span class="text-xs text-(--bg-primary)">
            ID:                                                                                           <?php echo $row[$idField] ?>
        </span>
    </li>
    <?php endforeach; ?>
</ul>
<?php endif; ?>
