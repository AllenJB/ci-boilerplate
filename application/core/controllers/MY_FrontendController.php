<?php

/**
 * All frontend controllers should extend this.
 */
class MY_FrontendController extends MY_Controller {

    /**
     * @var string Page layout to use - either 'normal' or 'minimal' (login page / errors)
     */
    protected $layout = "normal";

    /**
     * @var array Breadcrumbs
     */
    protected $crumbs = array();

    protected $cssFiles = array();

    protected $jsFiles = array();


    public function __construct() {
        parent::__construct();

        $this->loadSession();
        $this->loadFlashMessages();

        $this->load->helper('custom_form');
        $this->load->helper('custom_html');
        $this->load->library('date');
    }


    /**
     * Display the layout header
     * This method avoids the seperation of header and footer views, which breaks code hilighting / tag matching
     * @param array $vars Variables to pass to view
     */
    protected function displayLayoutHead(array $vars = array()) {
        $this->load->vars('crumbs', $this->crumbs);
        $this->load->vars('cssFiles', $this->cssFiles);
        $this->load->vars('jsFiles', $this->jsFiles);
        $this->load->vars('flashMessages', $this->flashMessages);

        // If no page title is set, automatically generate one from crumbs
        $pageTitle = $this->load->get_var('pageTitle');
        $crumbCount = count($this->crumbs);
        if ((strlen($pageTitle) < 1) && ($crumbCount > 0)) {
            $pageTitle = '';

            for ($i = 1; $i <= 2; $i++) {
                $j = $crumbCount - $i;
                if ($j < 0) {
                    break;
                }

                if (strlen($pageTitle)) {
                    $pageTitle .= ' :: ';
                }
                $pageTitle .= $this->crumbs[$j]['title'];
            }
            $this->load->vars('pageTitle', $pageTitle);
        }

        ob_start();
        print '<!-- HEAD START -->';
        $buffer = $this->load->view('layout/'. $this->layout, $vars, TRUE);
        $buffer = str_replace(array("\r\n", "\r"), "\n", $buffer);
        $buffer = explode("\n", $buffer);
        foreach ($buffer as &$line) {
            if (trim($line) == '<!-- TEMPLATE_MARKER: Content -->') {
                break;
            }
            print $line;
        }
        print '<!-- HEAD END -->';
        $this->output->append_output(ob_get_contents());
        @ob_end_clean();
        return;
    }


    /**
     * Display the layout footer
     * This method avoids the seperation of header and footer views, which breaks code hilighting / tag matching
     * @param array $vars Variables to pass to view
     */
    protected function displayLayoutFoot(array $vars = array()) {
        ob_start();
        print '<!-- FOOT START -->';
        $buffer = $this->load->view('layout/'. $this->layout, $vars, TRUE);
        $buffer = str_replace(array("\r\n", "\r"), "\n", $buffer);
        $buffer = explode("\n", $buffer);
        $printing = FALSE;
        foreach ($buffer as &$line) {
            if (trim($line) == '<!-- TEMPLATE_MARKER: Content -->') {
                $printing = TRUE;
            }

            if ($printing) {
                print $line;
            }
        }
        print '<!-- FOOT END -->';
        $this->output->append_output(ob_get_contents());
        @ob_end_clean();
        return;
    }

    protected function addCrumb($title, $url = NULL, $icon = NULL) {
        $this->crumbs[] = array(
            'title' => $title,
            'url' => $url,
            'icon' => $icon,
        );
    }


    protected function setSidebar($sidebar) {
        $this->load->vars(array(
            'showSidebar' => TRUE,
            'sidebar' => $sidebar,
        ));
    }


    protected function setActiveSection($section) {
        $this->load->vars('activeSection', $section);
    }


    protected function setActiveSubsection($section) {
        $this->load->vars('activeSubsection', $section);
    }


    protected function setActiveSubsubsection($section) {
        $this->load->vars('activeSubsubsection', $section);
    }


    protected function addCssFile($filename) {
        $this->cssFiles[] = array('filename' => $filename);
    }

    protected function addJsFile($filename) {
        $this->jsFiles[] = array('filename' => $filename);
    }

}
