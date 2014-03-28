<?php

function isChecked($key, &$record, $value = TRUE) {
    if (!array_key_exists($key, $record)) {
        return '';
    }
    return (($record[$key] == $value) ? ' checked="checked" ' : '');
}

function formValue($key, $record, $default = '') {
    if (!array_key_exists($key, $record)) {
        return $default;
    }
    return html_escape($record[$key]);
}


function selectOptions(array $options, $selectedItem = NULL) {
    foreach ($options as $value => $label) {
        $selected = ($selectedItem == $value ? ' selected="selected" ' : '');

        echo "<option value=\"". html_escape($value) ."\" {$selected}>". html_escape($label) ."</option>";
    }
}
