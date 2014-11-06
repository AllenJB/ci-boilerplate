<?php

/**
 * - Controller class names now have a _Controller suffix, which prevents some namespace collisions;
 * - Support for subdomains with seperated controllers / views;
 * - Support for infinite subdirectories
 */
class MY_Router extends CI_Router
{

    protected $controllerSuffix = '_Controller';

    protected $subdomainDir = null;

    protected $subdomain = null;


    public function fetch_class()
    {
        return $this->class . $this->controllerSuffix;
    }


    public function controller_name()
    {
        if (strstr($this->class, $this->controllerSuffix)) {
            return str_replace($this->controllerSuffix, '', $this->class);
        }
        return $this->class;
    }


    /**
     * If the subdomain is not 'www', it is appended to the front of the segments array
     *
     * @param array $segments
     */
    protected function _set_request($segments = array())
    {
        if (array_key_exists('HTTP_HOST', $_SERVER)) {
            $domain = str_replace(ROOT_DOMAIN, '', $_SERVER['HTTP_HOST']);
            $domainParts = explode('.', $domain);
            $subdomain = array_shift($domainParts);

            if (strlen($subdomain) && ($subdomain != 'www')) {
                $this->subdomain = $subdomain;
                if (preg_match('/^c[0-9a-f]+$/', $subdomain)) {
                    $this->subdomainDir = 'clients';
                } else {
                    $this->subdomainDir = $subdomain;
                }
            }
        }

        parent::_set_request($segments);
    }


    /**
     * Returns the actual subdomain being used (if not 'www')
     * Note that this may differ from the subdomain directory.
     *
     * @return null|string
     */
    public function getSubdomain()
    {
        return $this->subdomain;
    }


    /**
     * Returns the subdomain directory being used
     * Note that this may differ from the actual subdomain
     *
     * @return null
     */
    public function getSubdomainDir()
    {
        return $this->subdomainDir;
    }


    /**
     * @param $segments
     * @return array|null 2 element array containing controller and method
     */
    protected function _validate_request($segments)
    {
        if (count($segments) == 0) {
            return $segments;
        }

        $controllerPath = APPPATH . 'controllers/';
        if (strlen($this->subdomainDir)) {
            if (file_exists(APPPATH . 'subdomains/' . $this->subdomainDir) && is_dir(
                    APPPATH . 'subdomains/' . $this->subdomainDir
                )
            ) {
                $controllerPath = APPPATH . 'subdomains/' . $this->subdomainDir . '/controllers/';
            } else {
                show_404($_SERVER['REQUEST_URI']);
            }
        }

        // Does the requested controller exist in the root folder?
        if (file_exists($controllerPath . $segments[0] . '.php')) {
            return $segments;
        }

        // Is the controller in a sub-folder?
        $subdir = '';
        $subdirSegments = 0;
        while (count($segments)) {
            $segment = array_shift($segments);

            if (($segment == '..') || ($segment == '.')) {
                show_error('The URI you submitted has disallowed characters.', 400);
                return null;
            }

            if (! is_dir($controllerPath . $subdir . $segment)) {
                array_unshift($segments, $segment);
                break;
            }

            $subdir .= $segment . '/';
            $subdirSegments++;
        }
        if (strlen($subdir)) {
            $this->set_directory($subdir);
        }

        if (count($segments) > 0) {
            // Note to self: This line is correct because fetch_directory() will give the correct path
            if (file_exists(APPPATH . 'controllers/' . $this->fetch_directory() . $segments[0] . '.php')) {
                $class = array_shift($segments);
                $method = 'index';
                if (count($segments) > 0) {
                    $method = array_shift($segments);
                }
                array_unshift($segments, $method);
                array_unshift($segments, $class);

                $this->set_class($class);
                $this->set_method($method);
                return $segments;
            }

            show_404($_SERVER['REQUEST_URI']);
            return null;
        }

        // Check for default controller
        $defaultParts = explode('/', $this->default_controller);
        $defaultClass = $defaultParts[0];
        $defaultMethod = 'index';
        if (count($defaultParts) == 2) {
            $defaultMethod = $defaultParts[1];
        }

        if (file_exists(APPPATH . 'controllers/' . $this->fetch_directory() . $defaultClass . '.php')) {
            $this->set_class($defaultClass);
            $this->set_method($defaultMethod);
            return array($defaultClass, $defaultMethod);
        }

        // 404
        if (! empty($this->routes['404_override'])) {
            $x = explode('/', $this->routes['404_override']);

            $this->set_directory('');
            $this->set_class($x[0]);
            $this->set_method(isset($x[1]) ? $x[1] : 'index');

            return $x;
        } else {
            $this->set_directory('');
            show_404($_SERVER['REQUEST_URI']);
        }
        return null;
    }


    public function set_directory($dir)
    {
        $this->directory = rtrim($dir, '/') . '/';
    }


    public function fetch_directory()
    {
        $dir = rtrim($this->directory, '/') . '/';
        if (strlen($this->subdomainDir)) {
            if (file_exists(APPPATH . 'subdomains/' . $this->subdomainDir) && is_dir(
                    APPPATH . 'subdomains/' . $this->subdomainDir
                )
            ) {
                $dir = '/../subdomains/' . $this->subdomainDir . '/controllers/' . rtrim($this->directory, '/') . '/';
            } else {
                show_404($_SERVER['REQUEST_URI']);
            }
        }
        return $dir;
    }
}
