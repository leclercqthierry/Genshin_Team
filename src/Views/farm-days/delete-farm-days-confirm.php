<?php
    /**
     * @var array{id_farm_days: int|string, days: string} $farmDay
     * @var int|string $id
     */
?>

<h2>Confirmer la suppression</h2>
<p>
    Voulez-vous vraiment supprimer :
    <b><?php echo htmlspecialchars($farmDay['days']) ?></b> ?
</p>
<form method="post" action="delete-farm-days">
    <input type="hidden" name="delete_id" value="<?php echo $id ?>" />
    <button type="submit" name="confirm_delete" value="1">
        Oui, supprimer
    </button>
    <a href="delete-farm-days">Annuler</a>
</form>
