<?php
$isEdit = $isEdit ?? false;
$jours  = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'];
$title  = $isEdit ? 'Éditer les jours de farm' : 'Ajouter des jours de farm';
$action = $isEdit ? 'edit-farm-days' : 'add-farm-days';

$fieldHtml = '
    <p class="mb-4 text-sm text-(--font-color)">
        Cochez les jours de farm souhaités&nbsp;:
        <sup class="text-red-500"><b>*</b></sup>
    </p>
    <div class="form-group flex flex-col gap-2 w-full">';

$oldDays = (isset($old) && is_array($old) && isset($old['days']) && is_array($old['days'])) ? $old['days'] : [];
foreach ($jours as $jour) {
    $checked = in_array($jour, $oldDays, true) ? 'checked' : '';
    $fieldHtml .= '
        <label class="flex items-center gap-2 text-(--font-color)">
            <input
                type="checkbox"
                name="days[]"
                value="' . $jour . '"
                class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500"
                ' . $checked . '
            />
            ' . $jour . '
        </label>';
}
$errorDay = (isset($errors) && is_array($errors) && isset($errors['days']) && is_string($errors['days'])) ? $errors['days'] : '';
$fieldHtml .=
    ($errorDay !== '' ? '<p id="error-day" class="mt-1 text-xs text-pink-300 text-center">' . htmlspecialchars($errorDay) . '</p>' : '') .
    '</div>';
include __DIR__ . '/../partials/add-item.php';
