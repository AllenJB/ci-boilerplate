<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Index_Controller extends MY_FrontendController {

    public function __construct() {
        parent::__construct();

        $this->layout = 'minimal';
    }

    public function index() {
        $this->displayLayoutHead();
        $this->load->view('index/hello');
        $this->displayLayoutFoot();
    }

}
