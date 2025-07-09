<?php
    /**
     * @var array{
     *   global?: string,
     *   nickname?: string,
     *   email?: string,
     *   password?: string,
     *   confirm-password?: string
     * } $errors
     */

    /**
     * @var array{
     *   nickname?: string,
     *   email?: string
     * } $old
     */
?>
<h1 class="my-3.5 font-[RobotoCondensed] text-2xl font-bold uppercase text-center">S'inscrire</h1>

<?php if (isset($errors['global'])): ?>
    <div role="alert" class="mb-4 ms-4 me-4 rounded border border-red-500 bg-red-100 p-4 text-red-700">
        <p id="error-global"><?php echo htmlspecialchars($errors['global']); ?></p>
    </div>
<?php endif; ?>

<form
    action="register"
    method="post"
    class="mb-5 flex w-[270px] flex-col items-start justify-around gap-[15px] rounded-[20px] bg-(--bg-secondary) px-[15px] py-[30px]"
>
    <p class="mb-4 text-sm text-(--font-color)">
        Les champs précédés d'un astérisque rouge
        <sup class="text-red-500"><b>*</b></sup> sont obligatoires.
    </p>
    <div class="w-full">
        <label for="nickname">
            Pseudo <sup class="text-red-500"><b>*</b></sup>
        </label>
        <div class="relative focus-within:arrow-indicator input-wrapper">
            <input
                type="text"
                id="nickname"
                name="nickname"
                placeholder="user887"
                class="h-8 w-full rounded-[7px] border-0 bg-(--font-color) px-2.5 py-0 text-black placeholder:text-gray-600"
                value="<?php echo htmlspecialchars($old['nickname'] ?? ''); ?>"
                <?php echo isset($errors['nickname']) ? 'aria-describedby="error-nickname"' : ''; ?>
            />
            <p class="js-error mt-1 text-xs text-pink-300 min-h-4"></p>
        </div>
        <?php if (isset($errors['nickname'])): ?>
            <p id="error-nickname" class="mt-1 text-xs text-pink-300">
                <?php echo htmlspecialchars($errors['nickname']); ?>
            </p>
        <?php endif; ?>
    </div>
    <div class="w-full">
        <label for="email">
            Email <sup class="text-red-500"><b>*</b></sup>
        </label>
        <div class="relative focus-within:arrow-indicator input-wrapper">
            <input
                type="email"
                id="email"
                name="email"
                placeholder="ex: user887@gmail.com"
                class="h-8 w-full rounded-[7px] border-0 bg-(--font-color) px-2.5 py-0 text-black placeholder:text-gray-600"
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
    <div class="w-full">
        <label for="password">
            Mot de passe <sup class="text-red-500"><b>*</b></sup>
        </label>
        <div class="relative focus-within:arrow-indicator input-wrapper">
            <input
                type="password"
                id="password"
                name="password"
                placeholder="********"
                class="h-8 w-full rounded-[7px] border-0 bg-(--font-color) px-2.5 py-0 text-black placeholder:text-gray-600"
                <?php echo isset($errors['password']) ? 'aria-describedby="error-password"' : ''; ?>
            />
            <p class="js-error mt-1 text-xs text-pink-300 min-h-4"></p>
        </div>
        <?php if (isset($errors['password'])): ?>
            <p id="error-password" class="mt-1 text-xs text-pink-300">
                <?php echo htmlspecialchars($errors['password']); ?>
            </p>
        <?php endif; ?>
    </div>
    <div class="w-full">
        <label for="confirm-password">
            Confirmez votre mot de passe <sup class="text-red-500"><b>*</b></sup>
        </label>
        <div class="relative focus-within:arrow-indicator input-wrapper">
            <input
                type="password"
                id="confirm-password"
                name="confirm-password"
                placeholder="********"
                class="h-8 w-full rounded-[7px] border-0 bg-(--font-color) px-2.5 py-0 text-black placeholder:text-gray-600"
                <?php echo isset($errors['confirm-password']) ? 'aria-describedby="error-confirm-password"' : ''; ?>
            />
            <p class="js-error mt-1 text-xs text-pink-300 min-h-4"></p>
        </div>
        <?php if (isset($errors['confirm-password'])): ?>
            <p id="error-confirm-password" class="mt-1 text-xs text-pink-300">
                <?php echo htmlspecialchars($errors['confirm-password']); ?>
            </p>
        <?php endif; ?>
    </div>
    <input
        type="hidden"
        name="csrf_token"
        value="<?php if (is_string($_SESSION['csrf_token'])) {
                       echo $_SESSION['csrf_token'];
               }
               ?>"
    />
    <div class="w-full">
        <button
            type="submit"
            class="font-(Roboto) h-8 w-full rounded-[50px] border-0 bg-(--bg-primary) text-(--font-color) transition-colors duration-400 ease-in-out hover:cursor-pointer hover:bg-(--font-color) hover:text-(--bg-primary)"
        >
            Valider
        </button>
    </div>
</form>