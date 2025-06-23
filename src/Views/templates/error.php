<?php
    /**
     * @var array<string, string> $data
     */
    // En effet, pour cette vue, la valeur sera toujours un message d'erreur donc une chaîne
?>
<h1 class="my-3.5 font-[RobotoCondensed] text-2xl font-bold">Erreur</h1>
<div
    class="ms-3.5 me-3.5 mt-0 mb-5 flex flex-col justify-evenly gap-3.5 rounded-[20px] bg-(--bg-secondary) p-3.5"
>
    <p><?php echo $data['content']; ?></p>
</div>
