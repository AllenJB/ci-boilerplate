<?php

/**
 * Parent for AJAX controllers.
 *
 * This controller type intentionally doesn't do load up sessions automatically so that we can have fast and frequent
 * AJAX requests without worrying about session locking.
 */
class MY_AjaxController extends MY_Controller {

    public function __construct() {
        parent::__construct();

        header('Content-Type: application/json');
    }

}
