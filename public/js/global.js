function html_escape(value) {
    return $('<pre/>').text(value).html().replace(/"/g, '&quot;');
}

function real_typeof(v) {
    if (typeof(v) == "object") {
        if (v === null) return "null";
        if (v instanceof Array) return "array";
        if (v instanceof Date) return "date";
        if (v instanceof RegExp) return "regex";
        return "object";
    }

    if (typeof(v) == 'number') {
        if (isNaN(v)) {
            return 'NaN';
        }
    }

    return typeof(v);
}
