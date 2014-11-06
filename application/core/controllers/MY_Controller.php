<?php

/**
 * @property NativeSession $session
 * @property Profile $profile
 * @property MY_Loader $load
 * @property MY_Router $router
 */
class MY_Controller extends CI_Controller
{

    /**
     * @var bool Is authentication required to use this controller?
     * Only a few controllers such as auth (which handles the initial login) will ever need to touch this
     */
    protected $authRequired = true;

    /**
     * @var array Flash Messages - see addFlashMesage / saveFlashMessages.
     */
    protected $flashMessages = array();

    protected $sessionCookieName = null;


    public function __construct()
    {
        parent::__construct();

        if (strlen($this->router->getSubdomainDir())) {
            $this->load->setViewPath('subdomains/' . $this->router->getSubdomainDir() . '/views/');
        }

        $this->load->library('profile');
    }


    public function loadSession()
    {
        // In some specific contexts (for example, the oauth service), we want to change the session cookie used
        if ($this->sessionCookieName !== null) {
            $this->config->set_item('sess_cookie_name', $this->sessionCookieName);
        }
        $settings = array('sess_cookie_name' => $this->config->item('sess_cookie_name'));
        $this->load->library('nativesession', $settings, 'session');
    }


    protected function redirect($uri, $method = 'location', $code = '302')
    {
        if (is_object($this->session)) {
            $this->saveFlashMessages();
        }
        $this->load->helper('url_helper');
        redirect($uri, $method, $code);
        exit();
    }


    protected function saveFlashMessages()
    {
        if (! is_object($this->session)) {
            trigger_error("Sessions not loaded", E_USER_ERROR);
            return;
        }

        $this->session->set_flashdata('FlashMessages', $this->flashMessages);
    }


    /**
     * @param string $msg Message
     * @param string $type Message type: error, warning, success or info
     */
    protected function addFlashMessage($msg, $type)
    {
        if (! is_object($this->session)) {
            trigger_error("Sessions not loaded", E_USER_ERROR);
            return;
        }

        $validTypes = array('error', 'warning', 'success', 'info');
        $type = strtolower($type);
        if (! in_array($type, $validTypes)) {
            trigger_error("Invalid flash message type specified: {$type}", E_USER_ERROR);
            return;
        }
        if (! array_key_exists($type, $this->flashMessages)) {
            $this->flashMessages[$type] = array();
        }
        $this->flashMessages[$type][] = $msg;
    }


    protected function loadFlashMessages()
    {
        if (! is_object($this->session)) {
            trigger_error("Sessions not loaded", E_USER_ERROR);
            return;
        }

        // Initialise Flash Messages
        $this->flashMessages = $this->session->flashdata('FlashMessages');
        if (! is_array($this->flashMessages)) {
            $this->flashMessages = array();
        }
        $this->load->vars('flashMessages', $this->flashMessages);
    }


    /**
     * Trigger an error if remaining memory is low.
     *
     * Designed for use in cases where we're doing something that could cause us to run out of memory and is likely
     * to not leave enough memory for the shutdown handler to run
     *
     * @param string $threshold Threshold value - either in bytes or in php.ini allowed values
     * @return boolean Memory below threshold?
     */
    protected function checkMemoryUsage($threshold = '10M')
    {
        $rmem = memory_get_usage(true);
        $memlimit = $this->ini2Bytes(ini_get('memory_limit'));
        $remainingBytes = $memlimit - $rmem;

        $thresholdBytes = $this->ini2Bytes($threshold);
        if ($remainingBytes < $thresholdBytes) {
            trigger_error("Low memory warning: " . number_format($remainingBytes) . " bytes remaining");
        }
        return ($remainingBytes < $thresholdBytes);
    }


    protected function ini2Bytes($val)
    {
        $val = trim($val);
        $last = strtolower($val[strlen($val) - 1]);
        switch ($last) {
            // The 'G' modifier is available since PHP 5.1.0
            case 'g':
                $val *= 1024;
            case 'm':
                $val *= 1024;
            case 'k':
                $val *= 1024;
        }

        return $val;
    }
}
