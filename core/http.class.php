<?php
/**
 * Class for processing http requests and responses.
 */
class Http
{
    /**
     * Checks if the current request is GET.
     * @return bool
     */
    static public function isGetRequest(): bool
    {
        return strtolower($_SERVER['REQUEST_METHOD']) === 'get';
    }

    /**
     * Checks if the current request is POST.
     * @return bool
     */
    static public function isPostRequest(): bool
    {
        return strtolower($_SERVER['REQUEST_METHOD']) === 'post';
    }
    
    /**
     * Checks if the current request is GET or POST and also has an 'X-Requested-With' header.
     * @param string $method optional, method type ('get' / 'post')
     * @param bool $exit optional, if we need to run exit() function when it's not an ajax request
     * @return bool
     */
    static public function isAjaxRequest(string $method = '', bool $exit = false): bool
    {
        $headers = array_keys(getallheaders());
        $method = $method === '' ? $method : strtolower($method);
        $check = true;

        if($method === 'get' && !self::isGetRequest())
            $check = false;
        else if($method === 'post' && !self::isPostRequest())
            $check = false;

        if($check)
            $check = in_array('x-requested-with', $headers) || in_array('X-Requested-With', $headers);

        if(!$check && $exit)
        {
            header($_SERVER['SERVER_PROTOCOL'].' 404 Not Found');
            exit();
        }
        
        return $check;
    }

    /**
     * Returns raw request data from php://input
     * @param bool $as_array optional, to return result as array (from json)
     * @return string
     */
    static public function getRawRequestData(bool $as_array = false)
    {
        $data = file_get_contents('php://input');

        return $as_array ? json_decode($data, true) : $data;
    }

    /**
     * Sends http header and json data, created from passed array.
     * @param array $json data for json output
     * @param mixed $flags optional, php json flag(s) constant(s)
     */
    static public function responseJson(array $json = [], $flags = 0): void
    {
        $json = json_encode($json, $flags);

        header('Content-Type: application/json');
        echo $json;
        exit();
    }

    /**
     * Sends http header and passed xml data.
     * @param string $xml xml string for output
     */
    static public function responseXml(string $xml): void
    {
        if(strpos($xml, '<?xml version=') === false)
            $xml = "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n".$xml;

        header('Content-Type: application/xml');
        echo trim($xml);
        exit();
    }

    /**
     * Sends http header and passed palin text data.
     * @param string $text text string for output
     */
    static public function responseText(string $text): void
    {
        header('Content-Type: text/plain');
        echo trim($text);
        exit();
    }

    /**
     * Sends http header and passed HTML data.
     * @param string $html text string for output
     */
    static public function responseHtml(string $html): void
    {
        header('Content-Type: text/html');
        echo trim($html);
        exit();
    }

    /**
	 * Checks if the current connection is https.
	 * @return bool
	 */
    static public function isHttps()
    {
        return Router::isHttps();
    }

    /**
	 * Checks if the current host is localhost.
	 * @return bool
	 */
    static public function isLocalHost()
    {
        return Router::isLocalHost();
    }

    /**
	 * Sets one cookie with passed parametares.
     * @param string $key name of cookie
     * @param string $value value of cookie
     * @param string $params extra cookie parameters
	 */
    static public function setCookie(string $key, string $value, array $params = []): void
    {
        $expires = $params['expires'] ?? 0;
        $path = $params['path'] ?? Registry::get('MainPath');
        $domain = $params['domain'] ?? '';
        $http_only = Registry::get('HttpOnlyCookie') ?? true;

        setcookie($key, $value, $expires, $path, $domain, self::isHttps(), $http_only);
    }

    /**
     * Gets one cookie by key from COOKIE global array.
     * @param string $key name of cookie
     * @param string $default value of cookie if key not existed
     */
    static public function getCookie(string $key, mixed $default = null): mixed
    {
        return isset($_COOKIE, $_COOKIE[$key]) ? trim($_COOKIE[$key]) : $default;
    }
	
	/**
     * Gets one value by key from GET global array.
     * @param string $key name of parameter
     * @param string $default value of parameter if key not existed
     */
    static public function fromGet(string $key, mixed $default = null): mixed
    {
        return isset($_GET, $_GET[$key]) ? trim($_GET[$key]) : $default;
    }

    /**
     * Gets one value by key from POST global array.
     * @param string $key name of parameter
     * @param string $default value of parameter if key not existed
     */
    static public function fromPost(string $key, mixed $default = null): mixed
    {
        return isset($_POST, $_POST[$key]) ? trim($_POST[$key]) : $default;
    }

    /**
     * Gets one value by key from REQUEST global array.
     * @param string $key name of parameter
     * @param string $default value of parameter if key not existed
     */
    static public function fromRequest(string $key, mixed $default = null): mixed
    {
        return isset($_REQUEST, $_REQUEST[$key]) ? trim($_REQUEST[$key]) : $default;
    }

    /**
     * Checks if the current request has passed keys.
     */
    static public function requestHas(...$keys): bool
    {
        foreach($keys as $key)
            if(!array_key_exists($key, $_REQUEST))
                return false;

        return true;
    }

    /**
     * Reloads current URL with optional GET parameters passed.
     * Sends 302 status header before.
     */
    static public function reload(string $get_params = '')
    {
        $url = str_replace('?'.$_SERVER['QUERY_STRING'], '', $_SERVER['REQUEST_URI']);
        $url = preg_replace('/\/$/', '', $url);

        if($get_params !== '')
            $url .= '?'.str_replace('?', '', $get_params);
        
        self::sendStatusCodeHeader(302);        
        header('Location: '.$url);
        exit();
    }

    /**
     * Executes redirect to passed url with status code.
     */
    static public function redirect(string $url, int $status = 302)
    {
        if(!preg_match('/^(\/|https?:)/', $url))
            $url = Registry::get('MainPath').$url;
        else if($url === '/')
            $url = Registry::get('MainPath');

        self::sendStatusCodeHeader($status);
        header('Location: '.$url);
        exit();
    }

    /**
     * Sends status code HTTP header, according to current protocol.
     */
    static public function sendStatusCodeHeader(int $status, bool $exit = false)
    {
        $headers = [
            100 => 'Continue',
            101 => 'Switching Protocols',
            102 => 'Processing',
            200 => 'OK',
            201 => 'Created',
            202 => 'Accepted',
            203 => 'Non-Authoritative Information',
            204 => 'No Content',
            205 => 'Reset Content',
            206 => 'Partial Content',
            207 => 'Multi-Status',
            300 => 'Multiple Choices',
            301 => 'Moved Permanently',
            302 => 'Found',
            303 => 'See Other',
            304 => 'Not Modified',
            305 => 'Use Proxy',
            306 => 'Switch Proxy',
            307 => 'Temporary Redirect',
            400 => 'Bad Request',
            401 => 'Unauthorized',
            402 => 'Payment Required',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            406 => 'Not Acceptable',
            407 => 'Proxy Authentication Required',
            408 => 'Request Timeout',
            409 => 'Conflict',
            410 => 'Gone',
            411 => 'Length Required',
            412 => 'Precondition Failed',
            413 => 'Request Entity Too Large',
            414 => 'Request-URI Too Long',
            415 => 'Unsupported Media Type',
            416 => 'Requested Range Not Satisfiable',
            417 => 'Expectation Failed',
            418 => 'I\'m a teapot',
            422 => 'Unprocessable Entity',
            423 => 'Locked',
            424 => 'Failed Dependency',
            425 => 'Unordered Collection',
            426 => 'Upgrade Required',
            449 => 'Retry With',
            450 => 'Blocked by Windows Parental Controls',
            500 => 'Internal Server Error',
            501 => 'Not Implemented',
            502 => 'Bad Gateway',
            503 => 'Service Unavailable',
            504 => 'Gateway Timeout',
            505 => 'HTTP Version Not Supported',
            506 => 'Variant Also Negotiates',
            507 => 'Insufficient Storage',
            509 => 'Bandwidth Limit Exceeded',
            510 => 'Not Extended'
        ];

        if(array_key_exists($status, $headers))
        {
            if(isset($_SERVER['SERVER_PROTOCOL']))
                header($_SERVER['SERVER_PROTOCOL'].' '.$status.' '.$headers[$status]);

            if($exit)
                exit();
        }
    }
}