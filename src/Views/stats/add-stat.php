<?php
/** @var array{stat?: string} $old */
/** @var array<string, string> $errors */

$isEdit    = $isEdit ?? false;
$title     = $isEdit ? 'Éditer une statistique' : 'Ajouter une statistique';
$action    = $isEdit ? 'edit-stat' : 'add-stat';
$fieldHtml = '
    <p class="mb-4 text-sm text-(--font-color)">
        Entrez une nouvelle statistique&nbsp;:
        <sup class="text-red-500"><b>*</b></sup>
    </p>
    <div class="form-group flex flex-col gap-2 w-full">
        <label for="stat" class="text-(--font-color)">Stat :</label>
        <div class="relative focus-within:arrow-indicator input-wrapper">
            <input
                type="text"
                id="stat"
                name="stat"
                value="' . (isset($old["stat"]) ? htmlspecialchars((string) $old["stat"]) : "") . '"
            class="rounded border-gray-300 shadow-sm focus:ring-blue-500 p-2 w-full text-sm bg-(--font-color) text-(--bg-primary)"
            >
            <p class="js-error mt-1 text-xs text-pink-300 min-h-4"></p>
        </div>' .
    (isset($errors["stat"]) ? '<p id="error-stat" class="mt-1 text-xs text-pink-300 text-center">' . htmlspecialchars($errors["stat"]) . '</p>' : '') .
    '</div>
';
include __DIR__ . '/../partials/add-item.php';
