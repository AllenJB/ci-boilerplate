<?php  if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * Drop-in replacement for CI_Session that uses native PHP sessions
 *
 * Partially based on the CI 3.0 sessions code
 */
class NativeSession {

    public $sess_time_to_update = 300;

    protected $now = 0;

    /**
     * Initialization parameters
     *
     * @var    array
     */
    public $params = array();

    /**
     * Current driver in use
     *
     * @var    string
     */
    protected $current = null;

    protected $CI = null;

    // ------------------------------------------------------------------------

    const FLASHDATA_KEY = 'flash';
    const FLASHDATA_NEW = ':new:';
    const FLASHDATA_OLD = ':old:';
    const FLASHDATA_EXP = ':exp:';
    const EXPIRATION_KEY = '__expirations';
    const TEMP_EXP_DEF = 300;


    function __construct(array $params = array()) {
        $this->now = time();

        $this->CI =& get_instance();

        // No sessions under CLI
        if ($this->CI->input->is_cli_request()) {
            return;
        }

        log_message('debug', 'CI_Session Class Initialized');

        // Save a copy of parameters in case drivers need access
        $this->params = $params;

        $this->initialize();

        // Delete 'old' flashdata (from last request)
        $this->_flashdata_sweep();

        // Mark all new flashdata as old (data will be deleted before next request)
        $this->_flashdata_mark();


        log_message('debug', 'CI_Session routines successfully run');
    }


    protected function initialize() {
        // Get config parameters
        $config = array();
        $prefs = array('sess_cookie_name', 'sess_expire_on_close', 'sess_expiration', 'sess_match_ip', 'sess_match_useragent', 'sess_time_to_update', 'cookie_prefix', 'cookie_path', 'cookie_domain', 'cookie_secure', 'cookie_httponly');

        foreach ($prefs as $key) {
            $config[$key] = (array_key_exists($key, $this->params) ? $this->params[$key] : $this->CI->config->item($key));
        }

        // Set session name, if specified
        if ($config['sess_cookie_name']) {
            // Differentiate name from cookie driver with '_id' suffix
            $name = $config['sess_cookie_name'] . '_id';
            if ($config['cookie_prefix']) {
                // Prepend cookie prefix
                $name = $config['cookie_prefix'] . $name;
            }
            session_name($name);
        }

        // Set expiration, path, and domain
        $expire = 7200;
        $path = '/';
        $domain = '';
        $secure = (bool)$config['cookie_secure'];
        $http_only = (bool)$config['cookie_httponly'];

        if ($config['sess_expiration'] !== false) {
            // Default to 2 years if expiration is "0"
            $expire = ($config['sess_expiration'] == 0) ? (60 * 60 * 24 * 365 * 2) : $config['sess_expiration'];
        }

        if ($config['cookie_path']) {
            // Use specified path
            $path = $config['cookie_path'];
        }

        if ($config['cookie_domain']) {
            // Use specified domain
            $domain = $config['cookie_domain'];
        }

        session_set_cookie_params($config['sess_expire_on_close'] ? 0 : $expire, $path, $domain, $secure, $http_only);

        // Start session, closing any existing session first
        if (session_id() !== '') {
            @session_write_close();
        }
        session_start();

        // Check session expiration, ip, and agent
        $now = time();
        $destroy = false;
        if (isset($_SESSION['last_activity']) && (($_SESSION['last_activity'] + $expire) < $now OR $_SESSION['last_activity'] > $now)) {
            // Expired - destroy
            $destroy = true;
        } elseif ($config['sess_match_ip'] === true && isset($_SESSION['ip_address']) && $_SESSION['ip_address'] !== $this->CI->input->ip_address()) {
            // IP doesn't match - destroy
            $destroy = true;
        } elseif ($config['sess_match_useragent'] === true && isset($_SESSION['user_agent']) && $_SESSION['user_agent'] !== trim(substr($this->CI->input->user_agent(), 0, 50))) {
            // Agent doesn't match - destroy
            $destroy = true;
        }

        // Destroy expired or invalid session
        if ($destroy) {
            // Clear old session and start new
            $this->sess_destroy();
            session_start();
        }

        // Check for update time
        if ($config['sess_time_to_update'] && isset($_SESSION['last_activity']) && ($_SESSION['last_activity'] + $config['sess_time_to_update']) < $now) {
            // Changing the session ID amidst a series of AJAX calls causes problems
            if (!$this->CI->input->is_ajax_request()) {
                // Regenerate ID, but don't destroy session
                $this->sess_regenerate(false);
            }
        }

        // Set activity time
        $_SESSION['last_activity'] = $now;

        // Set matching values as required
        if ($config['sess_match_ip'] === true && !isset($_SESSION['ip_address'])) {
            // Store user IP address
            $_SESSION['ip_address'] = $this->CI->input->ip_address();
        }

        if ($config['sess_match_useragent'] === true && !isset($_SESSION['user_agent'])) {
            // Store user agent string
            $_SESSION['user_agent'] = trim(substr($this->CI->input->user_agent(), 0, 50));
        }

        // Make session ID available
        $_SESSION['session_id'] = session_id();
    }


    /**
     * Regenerates session id
     */
    public function sess_regenerate() {
        session_regenerate_id(true);
        $_SESSION['regenerated'] = time();
        $_SESSION['last_activity'] = time();
    }


    public function sess_create() {
        if (session_id() === '') {
            session_start();
        }

        $_SESSION['last_activity'] = time();
    }

    /**
     * Destroys the session and erases session storage
     */
    public function sess_destroy() {
        unset($_SESSION);
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 42000, '/');
        }
        session_destroy();
    }


    public function sess_update() {
        if (($_SESSION['last_activity'] + $this->sess_time_to_update) >= $this->now) {
            return;
        }
        $_SESSION['last_activity'] = time();
        $this->sess_regenerate();
    }


    /**
     * Reads given session attribute value
     */
    public function userdata($item, $default = FALSE) {
        if ($item == 'session_id') { //added for backward-compatibility
            return session_id();
        }

        if (!array_key_exists($item, $_SESSION)) {
            // FALSE value keeps compatibility with CI_Session
            return $default;
        }

        return $_SESSION[$item];
    }


    public function all_userdata() {
        return $_SESSION;
    }


    /**
     * Sets session attributes to the given values
     */
    public function set_userdata($newdata = array(), $newval = '') {
        $_SESSION['last_activity'] = time();

        if (is_string($newdata)) {
            $newdata = array($newdata => $newval);
        }

        if (count($newdata) > 0) {
            foreach ($newdata as $key => $val) {
                $_SESSION[$key] = $val;
            }
        }
    }

    /**
     * Erases given session attributes
     */
    public function unset_userdata($newdata = array()) {
        $_SESSION['last_activity'] = time();

        if (is_string($newdata)) {
            $newdata = array($newdata => '');
        }

        if (count($newdata) > 0) {
            foreach ($newdata as $key => $val) {
                unset($_SESSION[$key]);
            }
        }
    }


    /**
     * Fetch all flashdata
     *
     * @return    array    Flash data array
     */
    public function all_flashdata() {
        $out = array();

        // loop through all userdata
        foreach ($this->all_userdata() as $key => $val) {
            // if it contains flashdata, add it
            if (strpos($key, self::FLASHDATA_KEY . self::FLASHDATA_OLD) !== false) {
                $key = str_replace(self::FLASHDATA_KEY . self::FLASHDATA_OLD, '', $key);
                $out[$key] = $val;
            }
        }
        return $out;
    }

    /**
     * Add or change flashdata, only available until the next request
     *
     * @param    mixed $newdata Item name or array of items
     * @param    string $newval Item value or empty string
     * @return    void
     */
    public function set_flashdata($newdata = array(), $newval = '') {
        $_SESSION['last_activity'] = time();

        // Wrap item as array if singular
        if (is_string($newdata)) {
            $newdata = array($newdata => $newval);
        }

        // Prepend each key name and set value
        if (count($newdata) > 0) {
            foreach ($newdata as $key => $val) {
                $flashdata_key = self::FLASHDATA_KEY . self::FLASHDATA_NEW . $key;
                $this->set_userdata($flashdata_key, $val);
            }
        }
    }


    /**
     * Keeps existing flashdata available to next request.
     *
     * @param    mixed $key Item key(s)
     * @return    void
     */
    public function keep_flashdata($key) {
        $_SESSION['last_activity'] = time();

        if (is_array($key)) {
            foreach ($key as $k) {
                $this->keep_flashdata($k);
            }

            return;
        }

        // 'old' flashdata gets removed. Here we mark all flashdata as 'new' to preserve it from _flashdata_sweep()
        // Note the function will return NULL if the $key provided cannot be found
        $old_flashdata_key = self::FLASHDATA_KEY . self::FLASHDATA_OLD . $key;
        $value = $this->userdata($old_flashdata_key);

        $new_flashdata_key = self::FLASHDATA_KEY . self::FLASHDATA_NEW . $key;
        $this->set_userdata($new_flashdata_key, $value);
    }


    /**
     * Fetch a specific flashdata item from the session array
     *
     * @param    string $key Item key
     * @param mixed $default Default return value if no value found
     * @return    string
     */
    public function flashdata($key, $default = FALSE) {
        $_SESSION['last_activity'] = time();
        // Prepend key and retrieve value
        $flashdata_key = self::FLASHDATA_KEY . self::FLASHDATA_OLD . $key;
        return $this->userdata($flashdata_key, $default);
    }

    // ------------------------------------------------------------------------


    /**
     * Identifies flashdata as 'old' for removal
     * when _flashdata_sweep() runs.
     *
     * @return    void
     */
    protected function _flashdata_mark() {
        foreach ($this->all_userdata() as $name => $value) {
            $parts = explode(self::FLASHDATA_NEW, $name);
            if (count($parts) === 2) {
                $new_name = self::FLASHDATA_KEY . self::FLASHDATA_OLD . $parts[1];
                $this->set_userdata($new_name, $value);
                $this->unset_userdata($name);
            }
        }
    }


    /**
     * Removes all flashdata marked as 'old'
     *
     * @return    void
     */
    protected function _flashdata_sweep() {
        $userdata = $this->all_userdata();
        foreach (array_keys($userdata) as $key) {
            if (strpos($key, self::FLASHDATA_OLD)) {
                $this->unset_userdata($key);
            }
        }
    }


    public function sess_read() {
        trigger_error("Unimplemented", E_USER_NOTICE);
    }

    public function sess_write() {
        trigger_error("Unimplemented", E_USER_NOTICE);
    }

}
