<?php
    /**
     * @var array{ email?: string, global?: string } $errors
     * @var array{ email?: string } $old
     */
?>

<h1 class="my-3.5 font-[RobotoCondensed] text-2xl font-bold uppercase">Mot de passe oublié</h1>

<?php if (isset($errors['global'])): ?>
    <div role="alert" class="mb-4 rounded border border-red-500 bg-red-100 p-4 text-red-700">
        <p id="error-global"><?php echo htmlspecialchars($errors['global']); ?></p>
    </div>
<?php endif; ?>

<?php if (isset($success)): ?>
    <div role="status" class="mb-4 rounded border p-4">
        <?php /** @var string $success */?>
        <p><?php echo htmlspecialchars($success); ?></p>
    </div>
<?php endif; ?>

<form
    method="POST"
    action="forgot-password"
    class="mb-5 flex w-[270px] flex-col items-start justify-around gap-[15px] rounded-[20px] bg-(--bg-secondary) px-[15px] py-[30px]"
>
    <p class="mb-4 text-sm text-(--font-color)">
        Veuillez saisir votre adresse email pour recevoir un lien de réinitialisation.
    </p>

    <div class="w-full">
        <label for="email">
            Email <sup class="text-red-500"><b>*</b></sup>:
        </label>
        <div class="relative focus-within:arrow-indicator input-wrapper">
            <input
                type="email"
                name="email"
                id="email"
                placeholder="votre@email.com"
                class="h-8 w-full rounded-[7px] border-0 bg-(--font-color) px-2.5 py-0 placeholder:text-gray-600 text-black"
                value="<?php echo htmlspecialchars($old['email'] ?? ''); ?>"
                <?php echo isset($errors['email']) ? 'aria-describedby="error-email"' : ''; ?>
            />
            <p class="js-error mt-1 text-xs text-pink-300 min-h-4"></p>
        </div>

        <?php if (isset($errors['email'])): ?>
            <p id="error-email" class="mt-1 text-xs text-pink-300">
                <?php echo htmlspecialchars($errors['email']); ?>
            </p>
        <?php endif; ?>
    </div>

    <?php
        /** @var string|null $csrf */
        $csrf = $_SESSION['csrf_token'] ?? '';
    ?>
    <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">

    <div class="mt-4 flex w-full justify-between">
        <a
            href="login"
            class="font-(Roboto) flex h-8 w-[108px] items-center justify-center rounded-[50px] border-0 bg-(--bg-primary) text-(--font-color) transition-colors duration-400 ease-in-out hover:cursor-pointer hover:bg-(--font-color) hover:text-(--bg-primary)"
        >
            Se connecter
        </a>
        <button
            type="submit"
            class="font-(Roboto) h-8 w-[108px] rounded-[50px] border-0 bg-(--bg-primary) text-(--font-color) transition-colors duration-400 ease-in-out hover:cursor-pointer hover:bg-(--font-color) hover:text-(--bg-primary)"
        >
            Envoyer le lien
        </button>
    </div>
</form>
