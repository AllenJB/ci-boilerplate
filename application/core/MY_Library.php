<?php

/**
 * @property nativesession $session
 * @property CI_Input $input
 * @property CI_Output $output
 * @property CI_Loader $load
 */
class MY_Library {

    public function __construct() {

    }

    /**
     * __get
     *
     * Allows libraries to access CI's loaded classes using the same
     * syntax as controllers.
     *
     * @param	string
     * @access private
     */
    public function &__get($key) {
        $CI =& get_instance();
        return $CI->$key;
    }

}
