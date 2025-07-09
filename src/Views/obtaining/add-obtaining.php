<?php
/** @var array{obtaining?: string} $old */
/** @var array<string, string> $errors */

$isEdit    = $isEdit ?? false;
$title     = $isEdit ? 'Éditer un moyen d\'obtention' : 'Ajouter un moyen d\'obtention';
$action    = $isEdit ? 'edit-obtaining' : 'add-obtaining';
$fieldHtml = '
    <p class="mb-4 text-sm text-(--font-color)">
        Entrez un nouveau moyen d\'obtention&nbsp;:
        <sup class="text-red-500"><b>*</b></sup>
    </p>
    <div class="form-group flex flex-col gap-2 w-full">
        <label for="obtaining" class="text-(--font-color)">Moyen d\'obtention :</label>
        <div class="relative focus-within:arrow-indicator input-wrapper">
            <input
                type="text"
                id="obtaining"
                name="obtaining"
                value="' . (isset($old["obtaining"]) ? htmlspecialchars((string) $old["obtaining"]) : "") . '"
            class="rounded border-gray-300 shadow-sm focus:ring-blue-500 p-2 w-full text-sm bg-(--font-color) text-(--bg-primary)"
            >
            <p class="js-error mt-1 text-xs text-pink-300 min-h-4 text-center"></p>
        </div>' .
    (isset($errors["obtaining"]) ? '<p id="error-obtaining" class="mt-1 text-xs text-pink-300 text-center">' . htmlspecialchars($errors["obtaining"]) . '</p>' : '') .
    '</div>
';
include __DIR__ . '/../partials/add-item.php';
