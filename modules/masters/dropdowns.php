<?php
/**
 * Masters → Dropdowns
 *
 * Manage extra values for form dropdowns (Business Type, Lead Source, etc.).
 * Built-in options stay fixed; only custom options can be removed.
 * Permission: masters.dropdowns (Admin by default).
 */
$pageTitle = 'Dropdowns';
$currentPage = 'dropdowns';
require_once __DIR__ . '/../../includes/functions.php';
require_perm('masters.dropdowns');
ensure_dropdown_options_schema();

$labels = dropdown_option_field_labels();
$fieldKeys = dropdown_option_field_keys();

// Which dropdown list are we editing?
$fieldKey = trim((string) ($_GET['field'] ?? $_POST['field_key'] ?? 'business_type'));
if (!in_array($fieldKey, $fieldKeys, true)) {
    $fieldKey = 'business_type';
}

$selfUrl = base_url('modules/masters/dropdowns.php');
$errors = [];

// Handle add / remove
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = strtolower(trim((string) ($_POST['action'] ?? '')));
    $fieldKey = trim((string) ($_POST['field_key'] ?? $fieldKey));
    if (!in_array($fieldKey, $fieldKeys, true)) {
        $errors[] = 'Invalid dropdown field.';
        $fieldKey = 'business_type';
    }

    // Add a custom option to the selected field
    if (!$errors && $action === 'add') {
        $value = trim((string) ($_POST['option_value'] ?? ''));
        $note = trim((string) ($_POST['reason_note'] ?? ''));
        if ($note === '') {
            $note = 'Added from Masters → Dropdowns';
        }
        $result = save_custom_dropdown_option($fieldKey, $value, $note);
        if (empty($result['ok'])) {
            $errors[] = $result['error'] ?? 'Could not add option.';
        } else {
            flash(
                'success',
                !empty($result['exists'])
                    ? 'Already exists: ' . ($result['value'] ?? $value)
                    : 'Added: ' . ($result['value'] ?? $value),
                'create'
            );
            redirect($selfUrl . '?field=' . urlencode($fieldKey));
        }
    }

    // Remove one custom option (built-in values cannot be deleted)
    if (!$errors && $action === 'delete') {
        $id = (int) ($_POST['option_id'] ?? 0);
        $result = delete_custom_dropdown_option($id);
        if (empty($result['ok'])) {
            $errors[] = $result['error'] ?? 'Could not remove option.';
        } else {
            // Use action "edit" so the toast title is "Updated", not "Inactive"
            flash('success', 'Removed: ' . ($result['value'] ?? ''), 'edit');
            redirect($selfUrl . '?field=' . urlencode($fieldKey));
        }
    }
}

$standard = standard_options_for($fieldKey);
$customRows = custom_dropdown_option_rows($fieldKey);
$fieldLabel = $labels[$fieldKey] ?? $fieldKey;

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="card masters-dd">
    <h2>Dropdown options</h2>
    <p class="step-note">
        1) Choose a field &nbsp; 2) Type a new option and click Add.
        New values appear on Prospects, Customers, Onboarding, and Follow-ups forms.
        Grey rows are built-in (cannot delete). White rows are custom (Remove allowed).
    </p>

    <?php if ($errors): ?>
        <div class="alert alert-error">
            <?php foreach ($errors as $err): ?>
                <div><?= e($err) ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Field picker -->
    <form method="get" action="<?= e($selfUrl) ?>" class="masters-dd-pick js-no-loader">
        <label for="field">Which dropdown?</label>
        <select id="field" name="field" onchange="this.form.submit()">
            <?php foreach ($labels as $key => $label): ?>
                <option value="<?= e($key) ?>" <?= $fieldKey === $key ? 'selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
        </select>
    </form>

    <!-- Add custom option -->
    <form method="post" action="<?= e($selfUrl) ?>?field=<?= e(urlencode($fieldKey)) ?>" class="masters-dd-add js-no-loader">
        <input type="hidden" name="action" value="add">
        <input type="hidden" name="field_key" value="<?= e($fieldKey) ?>">
        <label for="option_value">Add to <?= e($fieldLabel) ?></label>
        <div class="masters-dd-add-row">
            <input id="option_value" type="text" name="option_value" maxlength="100" required
                   value="<?= e($_POST['option_value'] ?? '') ?>"
                   placeholder="New option name" autocomplete="off">
            <button type="submit" class="btn">Add</button>
        </div>
    </form>

    <!-- Current options for this field -->
    <div class="masters-dd-list-wrap">
        <div class="masters-dd-list-head"><?= e($fieldLabel) ?> list</div>
        <ul class="masters-dd-list">
            <?php foreach ($standard as $opt): ?>
                <li class="is-fixed">
                    <span><?= e($opt) ?></span>
                    <em>fixed</em>
                </li>
            <?php endforeach; ?>
            <?php foreach ($customRows as $row): ?>
                <li>
                    <span><?= e($row['option_value']) ?></span>
                    <form method="post" action="<?= e($selfUrl) ?>?field=<?= e(urlencode($fieldKey)) ?>" class="js-no-loader"
                          onsubmit="return confirm('Remove this option?');">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="field_key" value="<?= e($fieldKey) ?>">
                        <input type="hidden" name="option_id" value="<?= (int) $row['id'] ?>">
                        <button type="submit" class="link-danger">Remove</button>
                    </form>
                </li>
            <?php endforeach; ?>
            <?php if (!$standard && !$customRows): ?>
                <li class="is-empty">No options</li>
            <?php endif; ?>
        </ul>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
