<?php

/**
 * Index controller for admin.{ROOT_DOMAIN}
 */
class Index_Controller extends MY_FrontendController
{

    public function __construct()
    {
        parent::__construct();
    }


    public function index()
    {
        $this->displayLayoutHead();
        $this->load->view('index');
        $this->displayLayoutFoot();
    }
}
