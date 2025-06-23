<?php
    /** @var array<string, string> $errors */
    /** @var array<string, string> $old */
?>

<h1 class="my-3.5 font-[RobotoCondensed] text-2xl font-bold">Connexion</h1>

<?php if (isset($errors['global'])): ?>
    <div role="alert" class="mb-4 rounded border border-red-500 bg-red-100 p-4 text-red-700">
        <p id="error-global"><?php echo htmlspecialchars($errors['global']); ?></p>
    </div>
<?php endif; ?>

<form
    method="POST"
    action="login"
    class="mb-5 flex w-[270px] flex-col items-start justify-around gap-[15px] rounded-[20px] bg-(--bg-secondary) px-[15px] py-[30px]"
>
    <p class="mb-4 text-sm text-(--font-color)">
        Les champs précédés d'un astérisque rouge
        <sup class="text-red-500"><b>*</b></sup> sont obligatoires.
    </p>

    <div class="form-group">
        <label for="nickname">
            Pseudo <sup class="text-red-500"><b>*</b></sup>:
        </label>
        <input
            type="text"
            name="nickname"
            id="nickname"
            placeholder="user887"
            class="h-8 w-full rounded-[7px] border-0 bg-(--font-color) px-2.5 py-0 placeholder:text-gray-600 text-black"
            value="<?php echo htmlspecialchars($old['nickname'] ?? ''); ?>"
            <?php echo isset($errors['nickname']) ? 'aria-describedby="error-nickname"' : ''; ?>
        />
        <?php if (isset($errors['nickname'])): ?>
            <p id="error-nickname" class="mt-1 text-xs text-pink-300">
                <?php echo htmlspecialchars($errors['nickname']); ?>
            </p>
        <?php endif; ?>
    </div>

    <div class="form-group">
        <label for="password">
            Mot de passe <sup class="text-red-500"><b>*</b></sup>:
        </label>
        <input
            type="password"
            name="password"
            id="password"
            placeholder="********"
            class="h-8 w-full rounded-[7px] border-0 bg-(--font-color) px-2.5 py-0 placeholder:text-gray-600 text-black"
            <?php echo isset($errors['password']) ? 'aria-describedby="error-password"' : ''; ?>
        />
        <?php if (isset($errors['password'])): ?>
            <p id="error-password" class="mt-1 text-xs text-pink-300">
                <?php echo htmlspecialchars($errors['password']); ?>
            </p>
        <?php endif; ?>
    </div>

    <input type="hidden" name="csrf_token" value="<?php if (is_string($_SESSION['csrf_token'])) {
                                                          echo $_SESSION['csrf_token'];
                                                  }
                                                  ?>">

    <a href="#" class="block text-(--font-color) hover:text-gray-400">
        mot de passe oublié
    </a>

    <div class="flex w-full justify-between">
        <a
            href="register"
            class="font-(Roboto) flex h-8 w-[108px] items-center justify-center rounded-[50px] border-0 bg-(--bg-primary) text-(--font-color) transition-colors duration-400 ease-in-out hover:cursor-pointer hover:bg-(--font-color) hover:text-(--bg-primary)"
        >
            S'inscrire
        </a>
        <button
            type="submit"
            class="font-(Roboto) h-8 w-[108px] rounded-[50px] border-0 bg-(--bg-primary) text-(--font-color) transition-colors duration-400 ease-in-out hover:cursor-pointer hover:bg-(--font-color) hover:text-(--bg-primary)"
        >
            Se connecter
        </button>
    </div>
</form>