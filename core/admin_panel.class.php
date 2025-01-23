<?php

use function PHPSTORM_META\elementType;

/**
 * Class is under construction ...
 */
class AdminPanel
{
    /**
	 * Current user object of admin panel with credentials and settings.
	 * @var User
	 */ 
	public $user;

    /**
     * Current view template in admin panes.
     * @var string
     */
    private $view = '';

    /**
     * Available limits for pagination in admin panel, for all modules.
     */
    public const PAGINATION_LIMITS = [5, 10, 15, 20, 30, 50, 100, 200, 300, 500];

    public function __construct(User $user = null)
    {
        Registry::set('AdminPanelEnvironment', true);
        
        if(!Service::sessionIsStarted())
            session_start();

        Session::start('adminpanel');
        
        $session_data = ['settings', 'flash_messages', 'flash_parameters'];

        foreach($session_data as $key)
            if(Session::get($key) === null)
                Session::set($key, []);

        if(is_object($user))
        {
            $this -> setUser($user);

            if(Session::get('settings') == [])
                Session::set('settings', $user -> loadSettings());
        }
    }

    /**
     * Returns current view name form GET.
     * @return string
     */
    public function getCurrentView()
    {
        return $this -> view;
    }

    /**
     * Sets current user objec in admin panel.
     * @param User $user object of current admin panel user
     * @return self
     */
    public function setUser(User $user)
	{ 
		$this -> user = $user;
		return $this;
	}

    /**
     * Looks for the view file to include, based on GET data.
     * @return string view file name to show
     */
    public function defineRequestedView(): string
    {
        $view = '';

        if(empty($_GET))
        {
            $view = 'view-index.php';
            $this -> view = 'index';
        }
        else if($view = Http::fromGet('view', ''))
        {
            $this -> view = $view;
            $view = 'view-'.$view.'.php';
        }

        $view = trim(str_replace(['..', '/', '\/'], '', $view));
        $file = Registry::get('IncludeAdminPath').'views/'.$view;

        if(is_file($file))
            return $file;
        else
        {
            $this -> view = '404';
            return Registry::get('IncludeAdminPath').'views/view-404.php';
        }
    }

    /**
     * 
     */
    public function createCSRFToken()
	{
		$token = $_SERVER["REMOTE_ADDR"].$_SERVER["HTTP_USER_AGENT"];
		$token .= $this -> user -> getField("login").$this -> user -> getField("password");
        $token .= Registry::get('SecretCode');
		
		return Service :: createHash($token, "random");
	}

    /**
     * Returns the current limit for pagination.
     * @return int limit value
     */
    static public function getPaginationLimit(): int
    {
        Session::start('adminpanel');
        $limit = Session::get('settings')['paginator-limit'] ?? 10;

        return in_array($limit, self::PAGINATION_LIMITS) ? $limit : 10;
    }

    /**
     * Saves the provided pagination limit into user session and user settings in database.
     * @return bool success flag
     */
    public function savePaginationLimit(mixed $limit): bool
    {
        $limit = intval($limit);

        if(!in_array($limit, self::PAGINATION_LIMITS))
            return false;

        $this -> updateUserSessionSetting('paginator-limit', $limit);

        return true;
    }

    public function getUserSessionSetting(string $key)
    {
        Session::start('adminpanel');
        $settings = Session::get('settings');

        return array_key_exists($key, $settings) ? $settings[$key] : null;
    }

    public function updateUserSessionSetting(string $key, mixed $value)
    {
        Session::start('adminpanel');
        $settings = Session::get('settings');
        $settings[$key] = $value;
        Session::set('settings', $settings);

        $this -> user -> updateSetting($key, $value);
    }

    public function addFlashMessage(string $type, string $message)
    {
        $_SESSION['mv']['flash_messages'][$type] ??= [];
        $_SESSION['mv']['flash_messages'][$type][] = $message;

        return $this;
    }

    public function displayAndClearFlashMessages(): string
    {
        $html = '';

        foreach($_SESSION['mv']['flash_messages'] as $type => $messages)
        {
            $html .= "<div class=\"flash-message ".$type."\">\n";

            foreach($messages as $message)
                $html .= "<div>".$message."</div>\n";

            $html .= "</div>\n";
        }

        $_SESSION['mv']['flash_messages'] = [];

        return $html;
    }

    static public function addFlashParameter(string $key, mixed $value)
    {
        if(is_numeric($value) || is_string($value) || is_array($value))
            $_SESSION['mv']['flash_parameters'][$key] = $value;
    }

    static public function getFlashParameter(string $key): mixed
    {
        return $_SESSION['mv']['flash_parameters'][$key] ?? null;
    }

    static public function clearFlashParameters()
    {
        $_SESSION['mv']['flash_parameters'] = [];
    }
}