<?php
    /**
     * @var string                $title
     * @var string                $action
     * @var string                $mode
     * @var bool                  $isEdit
     * @var int|string|null       $id
     * @var array<string, string> $errors // exemple : ['global' => 'message']
     * @var array<string, mixed>  $old // exemple : ['stat' => '42'] ou autres champs
     * @var string                $fieldHtml // HTML du champ principal (input ou checkboxes) */
?>
    <h1 class="my-3.5 font-[RobotoCondensed] text-2xl font-bold uppercase">
        <?php echo htmlspecialchars($title); ?>
    </h1>

    <?php if (isset($errors['global'])): ?>
    <div
        role="alert"
        class="mb-4 rounded border border-red-500 bg-red-100 p-4 text-red-700"
    >
        <p id="error-global">
            <?php echo htmlspecialchars($errors['global']); ?>
        </p>
    </div>
    <?php endif; ?>

    <form
        method="POST"
        action="<?php echo htmlspecialchars($action); ?>"
        class="mb-5 flex flex-col items-start justify-around gap-[15px] rounded-[20px] bg-(--bg-secondary) px-[15px] py-[30px]"
    >
    <?php
        $token = $_SESSION['csrf_token'] ?? '';
        if (! is_string($token)) {
            $token = ''; // fallback safe
        }
    ?>
        <input
            type="hidden"
            name="csrf_token"
            value="<?php echo htmlspecialchars($token); ?>"
        />
        <?php if ($isEdit && isset($id)): ?>
        <input
            type="hidden"
            name="edit_id"
            value="<?php echo htmlspecialchars((string) $id); ?>"
        />
        <?php endif; ?><?php echo $fieldHtml; ?>

        <div class="mt-4 flex w-full justify-center">
            <button
                type="submit"
                class="font-(Roboto) h-8 w-[140px] rounded-[50px] border-0 bg-(--bg-primary) text-(--font-color) transition-colors duration-400 ease-in-out hover:cursor-pointer hover:bg-(--font-color) hover:text-(--bg-primary)"
            >
                <?php echo $isEdit ? 'Modifier' : 'Ajouter'; ?>
            </button>
        </div>
    </form></string,
>
