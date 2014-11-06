<?php

function isChecked($key, &$record, $value = true)
{
    if (! array_key_exists($key, $record)) {
        return '';
    }
    return (($record[$key] == $value) ? ' checked="checked" ' : '');
}

function formValue($key, $record, $default = '')
{
    if (!array_key_exists($key, $record)) {
        return html_escape($default);
    }
    $value = $record[$key];
    if (is_object($value) && ($value instanceof DateTime)) {
        $value = $value->format('Y-m-d');
    }
    return html_escape($value);
}


function selectOptions(array $options, $selectedItem = null)
{
    foreach ($options as $value => $label) {
        $selected = ($selectedItem == $value ? ' selected="selected" ' : '');

        echo "<option value=\"" . html_escape($value) . "\" {$selected}>" . html_escape($label) . "</option>";
    }
}
