<?php

/**
 * REST Client library
 */
class Rest {

    /**
     * @var null|string Cookie storage location
     */
    protected $cookieDir = NULL;

    /**
     * @var null|string Accept HTTP header for the request
     */
    protected $accept = NULL;

    /**
     * @var null|string Content-Type HTTP header for the request
     */
    protected $contentType = NULL;

    /**
     * @var null|string Last request error
     */
    protected $lastError = NULL;

    /**
     * @var null|array curl_geptopt info for the last request
     */
    protected $lastRequestInfo = NULL;

    /**
     * @var null|array HTTP Headers for the last response
     */
    protected $lastResponseHeaders = NULL;


    /**
     * @var null|string Complete last response
     */
    protected $lastResponse = NULL;


    protected $lastRequestData = NULL;


    protected $caBundle = NULL;

    protected $errorLogFh = NULL;

    protected $connectTimeout = 10;

    protected $timeout = 30;


    /**
     * Constructor
     *
     * Sets the default cookie directory (is the DATA_DIR constant is set)
     */
    public function __construct() {
        if (defined('DATA_DIR')) {
            $this->cookieDir = DATA_DIR .'/curl/cookies';
        }
    }


    /**
     * Set the Accept header for subsequent requests
     * @param string $mimetype Mime Type
     */
    public function setAccept($mimetype) {
        $this->accept = $mimetype;
    }


    /**
     * Set the Content-Type header for subsequent requests
     * @param string $mimeType Mime Type
     */
    public function setContentType($mimeType) {
        $this->contentType = $mimeType;
    }

    public function setCABundle($file) {
        $this->caBundle = $file;
    }


    public function setConnectTimeout($secs) {
        $this->connectTimeout = $secs;
    }

    public function setTimeout($secs) {
        $this->timeout = $secs;
    }


    /**
     * Initialize a curl object with our default option set
     * @return resource Curl handle
     */
    protected function initCurl() {
        $ch = curl_init();
        // 2013-09-30 Curl seems to be broken on live servers
        // Using VERIFYHOST 2 AND a caBundle works fine on my dev box, but not on live.
//        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        if ($this->caBundle !== NULL) {
            curl_setopt($ch, CURLOPT_CAINFO, $this->caBundle);
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        if ($this->cookieDir !== NULL) {
            curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookieDir);
        }
        curl_setopt($ch, CURLOPT_HEADER, TRUE);
        curl_setopt($ch, CURLINFO_HEADER_OUT, TRUE);
        curl_setopt($ch, CURLOPT_VERBOSE, TRUE);
        if (defined('CURLOPT_CERTINFO')) {
            curl_setopt($ch, CURLOPT_CERTINFO, TRUE);
        }
        if (!is_resource($this->errorLogFh)) {
            $this->errorLogFh = fopen(DATA_DIR .'/curl/error.log', 'w+');
        }
        curl_setopt($ch, CURLOPT_STDERR, $this->errorLogFh);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->connectTimeout);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);

        return $ch;
    }


    /**
     * Set the headers for the curl request.
     *
     * We don't do this as part of init because we want to do it as the very last thing to override
     * any other headers that may have been set.
     *
     * @param resource $ch Curl handle
     */
    protected function setCurlHeaders(&$ch) {
        $headers = array (
            'Expect:'
        );
        if ($this->accept !== NULL) {
            $headers[] = 'Accept: '. $this->accept;
        }
        if ($this->contentType !== NULL) {
            $headers[] = 'Content-Type: '. $this->contentType;
        }
        if (count($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
    }


    /**
     * Reset the last response values
     */
    protected function reset() {
        $this->lastError = NULL;
        $this->lastRequestInfo = NULL;
        $this->lastResponseHeaders = NULL;
        $this->lastResponse = NULL;
        $this->lastRequestData = NULL;
    }


    /**
     * Execute a curl request and process the response.
     *
     * @param resource $ch Curl handle
     * @return bool|mixed Response body, or FALSE on error
     */
    protected function curlExecute(&$ch) {
        $this->setCurlHeaders($ch);

        $page = curl_exec($ch);
        $error = curl_error($ch);
        $this->lastRequestInfo = curl_getinfo($ch);
        $this->lastResponse = $page;

        curl_close($ch);

        if (strlen($error) > 0) {
            $this->lastError = $error;
            return FALSE;
        }

        $responseParts = explode("\r\n\r\n", $page);
        $headers = explode("\r\n", $responseParts[0]);

        $this->lastResponseHeaders = array();
        foreach ($headers as $header) {
            $headerParts = explode(':', $header, 2);

            if (count($headerParts) != 2) {
                if (strpos($header, 'HTTP/') === 0) {
                    $this->lastResponseHeaders['http'] = $header;
                    continue;
                }
                trigger_error("Continued headers are currently unhandled", E_USER_NOTICE);
                continue;
            }

            $key = $headerParts[0];
            $value = $headerParts[1];

            if (!array_key_exists($key, $this->lastResponseHeaders)) {
                $this->lastResponseHeaders[$key] = $value;
                continue;
            }

            if (!is_array($this->lastResponseHeaders[$key])) {
                $this->lastResponseHeaders[$key] = array ($this->lastResponseHeaders[$key]);
            }
            $this->lastResponseHeaders[$key][] = $value;
        }

        return $responseParts[1];
    }


    /**
     * Perform a GET request
     *
     * @param string $url Request URL
     * @return bool|mixed Response, or FALSE on error
     */
    public function restGet($url) {
        $this->reset();

        $ch = $this->initCurl();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');

        return $this->curlExecute($ch);
    }


    /**
     * Perform a POST request
     *
     * @param string $url URL
     * @param array|string $postFields POST data to send
     * @return bool|mixed Response, or FALSE on error
     */
    public function restPost($url, $postFields = NULL) {
        $this->reset();
        $this->lastRequestData = $postFields;

        $ch = $this->initCurl();
        curl_setopt($ch, CURLOPT_URL, $url);
//        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        // Despite that fact that it's specifically allowed in the documentation, and works fine on my dev box,
        // passing arrays through here doesn't appear to always work, particularly if they're multi-dimensional
        // So we force use of a URL encoded string instead
        if (is_array($postFields)) {
            $postFields = http_build_query($postFields);
        }
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);

        return $this->curlExecute($ch);
    }


    /**
     * Perform a PUT request
     *
     * @param string $url URL
     * @param null|string $body Data to PUT
     * @return bool|mixed Response, or FALSE on error
     */
    public function restPut($url, $body = NULL) {
        $this->reset();
        $this->lastRequestData = $body;

        $fp = fopen('php://temp/maxmemory:256000', 'w');
        if (!$fp) {
            trigger_error("Unable to write body to memory", E_USER_ERROR);
            return FALSE;
        }
        fwrite($fp, $body);
        fseek($fp, 0);
        $stat = fstat($fp);

        $ch = $this->initCurl();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_PUT, TRUE);
        curl_setopt($ch, CURLOPT_INFILE, $fp);
        curl_setopt($ch, CURLOPT_INFILESIZE, $stat['size']);

        return $this->curlExecute($ch);
    }


    /**
     * Perform a DELETE request
     *
     * @param string $url URL
     * @return bool|mixed Response, or FALSE on error
     */
    public function restDelete($url) {
        $this->reset();

        $ch = $this->initCurl();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');

        return $this->curlExecute($ch);
    }


    /**
     * Return the last request error message
     *
     * @return null|string Error message
     */
    public function getLastError() {
        return $this->lastError;
    }


    /**
     * Return the HTTP code for the last response
     * @return null|string HTTP error code
     */
    public function getResponseHttpCode() {
        if (!is_array($this->lastRequestInfo)) {
            return NULL;
        }

        return $this->lastRequestInfo['http_code'];
    }


    /**
     * Return the HTTP Content-Type for the last response
     *
     * @return null|string HTTP content type
     */
    public function getResponseContentType() {
        if (!is_array($this->lastRequestInfo)) {
            return NULL;
        }

        return $this->lastRequestInfo['content_type'];
    }


    /**
     * Return the response headers for the last response
     *
     * This is returned as an associative array.
     * If the same header occurs multiple times, the value will be an array of all values.
     *
     * @return array|null HTTP Headers
     */
    public function getResponseHeaders() {
        return $this->lastResponseHeaders;
    }


    public function getLastResponse() {
        return $this->lastResponse;
    }

    public function getLastRequestInfo() {
        return $this->lastRequestInfo;
    }


    public function getLastRequestData() {
        return $this->lastRequestData;
    }

}
