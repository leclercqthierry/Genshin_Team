<?php
    /**
     * @var string                             $action
     * @var string                             $fieldName
     * @var string                             $buttonLabel
     * @var string                             $title
     * @var array<int, array<string, mixed>> $items
     * @var string $nameField
     * @var string $idField
     * @var array<string, string> $errors
     */
?>
    <h1 class="my-3.5 font-[RobotoCondensed] text-2xl font-bold uppercase text-center">
        <?php echo htmlspecialchars($title) ?>
    </h1>

    <form
        method="post"
        action=""
        class="mb-5 flex flex-col items-center justify-around gap-[15px] rounded-[20px] bg-(--bg-secondary) px-[15px] py-[30px]"
    >
        <?php if (! empty($errors['global'])): ?>
        <div class="mb-2 text-red-600">
            <?php echo htmlspecialchars($errors['global']) ?>
        </div>
        <?php endif; ?>
<?php
    $token = $_SESSION['csrf_token'] ?? '';
    if (! is_string($token)) {
        $token = ''; // fallback safe
    }
?>
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($token); ?>">

        <label
            for="<?php echo htmlspecialchars($fieldName) ?>"
            class="mb-2 block font-bold"
        >
            <?php echo htmlspecialchars($title) ?>
        </label>
        <select
            name="<?php echo htmlspecialchars($fieldName) ?>"
            id="<?php echo htmlspecialchars($fieldName) ?>"
            class="rounded border border-gray-300 bg-(--font-color) p-2 text-(--bg-primary) shadow-sm focus:ring-blue-500"
        >
            <option value="">-- Choisir --</option>
            <?php foreach ($items as $item): ?>
<?php
    $id   = $item[$idField] ?? '';
    $name = $item[$nameField] ?? '';
?>
            <option
                value="<?php echo htmlspecialchars(is_scalar($id) ? (string) $id : '') ?>"
            >
                <?php echo htmlspecialchars(is_scalar($name) ? (string) $name : '') ?> (ID:<?php echo htmlspecialchars(is_scalar($id) ? (string) $id : '') ?>)
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
