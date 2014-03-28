<?php

/**
 * Entends the standard CI_Loader so that we can manipulate the view path (this is used for subdomain handling)
 */
class MY_Loader extends CI_Loader {

    public function __construct() {
        parent::__construct();
    }


    public function setViewPath($path) {
        $path = rtrim($path, '/') .'/';
        $this->_ci_view_paths = array(APPPATH.$path => TRUE);
    }

}
