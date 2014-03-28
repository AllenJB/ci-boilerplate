<?php

/**
 * Escape a string for use inside an (inline) javascript string
 *
 * @param string $value Value to escape
 * @param bool $htmlEscape Run through html_escape()?
 * @return bool|string Escaped string
 */
function js_escape_string($value, $htmlEscape = TRUE) {
    if (is_object($value) || is_array($value)) {
        trigger_error("Parameter 0 must be a string(-like) value)", E_USER_ERROR);
        return FALSE;
    }

    $value = json_encode("". $value, JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_APOS);
    // Remove the double-quotes
    $value = substr($value, 1, -1);
    if ($htmlEscape) {
        $value = html_escape($value);
    }
    return $value;
}

/**
 * Display a boolean value as a (tick/cross) image.
 *
 * @param bool|null $value Value
 * @param string $trueText Alt text for true value
 * @param string $falseText Alt text for false value
 * @return string HTML
 */
function boolImage($value, $trueText = '', $falseText = '') {
    if ($value === NULL) {
        return '';
    }

    if ($value) {
        return '<img src="/images/icon/tick.png" alt="'. html_escape($trueText) .'" title="'. html_escape($trueText) .'" />';
    } else {
        return '<img src="/images/icon/cross.png" alt="'. html_escape($falseText) .'" title="'. html_escape($falseText) .'" />';
    }
}
