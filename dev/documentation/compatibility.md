Compatibility Shims
===================

In some cases shims are provided to provide forwards compaitbility for features found in newer PHP versions that may
not always be present.

* json_last_error_msg()
    * Set up in `application/third_party/compat.php`
* password_hash() / password_verify()
    * Set up in `public/index.php` via composer
