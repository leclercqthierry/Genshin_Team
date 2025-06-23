<h1 class="my-3.5 font-[RobotoCondensed] text-2xl font-bold">404</h1>
<div
    class="ms-3.5 me-3.5 mt-0 mb-5 flex flex-col justify-evenly gap-3.5 rounded-[20px] bg-(--bg-secondary) p-3.5"
>
<?php
    /** @var string|null $message */
    // Le message d'erreur provient potentiellement du contrôleur, sinon on affiche un message générique.
?>
<p><?php echo isset($message) ? htmlspecialchars($message) : "La page demandée n'existe pas."; ?></p>
</div>
