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
        
        $session_data = ['settings'];

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
            $view = trim(str_replace(['..', '/', '\/'], '', $view));
        }        
        else if($model = Http::fromGet('model', ''))
        {
            $action = Http::fromGet('action', '');
            $actions = ['index', 'create', 'update', 'simple'];
            
            if(in_array($action, $actions) && Registry::checkModel($model))
                $this -> view = $view = 'model/view-'.$action.'.php';
        }
        
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

    public function defineCurrentUserRegion()
    {
        $region = $this -> getUserSessionSetting('region');

        if(!I18n::checkRegion($region))
            $region = I18n::defineRegion();
        
        I18n::setRegion($region);

        return $this;
    }

    public function displayInternalError(string $error_key = '')
	{
		$error_key = $error_key === '' ? 'error-occurred' : $error_key;
		$interal_error_text = I18n::locale($error_key);
		
        include Registry::get('IncludeAdminPath').'controls/internal-error.php';
        exit();
    }
}