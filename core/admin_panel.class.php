<?php
/**
 * Main class in Admin Panel, manages authorized user and common settings.
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

    /**
	 * Versions manager object.
	 * @var object Versions
	 */
	public $versions;

    public function __construct(User $user = null)
    {
        ob_start();
        
        Registry::set('AdminPanelEnvironment', true);
        
        if(!Service::sessionIsStarted())
            session_start();

        Session::start('admin_panel');
        
        if(Session::get('settings') === null)
            Session::set('settings', []);

        if($time_zone = Registry::get('TimeZone'))
            date_default_timezone_set($time_zone);                

        if(is_object($user))
            $this -> setUser($user);
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

        Session::start('admin_panel');
        
        if(Session::get('settings') == [])
            Session::set('settings', $user -> loadSettings());

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
            $view = trim(str_replace(['.', '/', '\\'], '', $view));
            $this -> view = $view;
            $view = 'view-'.$view.'.php';
        }        
        else if($service = Http::fromGet('service', ''))
        {
            $service = trim(str_replace(['..', '/', '\\'], '', $service));
            $this -> view = $service;
            $view = 'service/view-'.$service.'.php';
        }
        else if($model = Http::fromGet('model', ''))
        {
            $action = Http::fromGet('action', '');
            $actions = ['index', 'create', 'update', 'simple'];
            
            if(in_array($action, $actions) && Registry::checkModel($model))
            {
                $this -> view = $action;
                $view = 'model/view-'.$action.'.php';
            }
        }
        else if($ajax = Http::fromGet('ajax', ''))
        {
            $this -> view = $ajax = trim(str_replace(['.', '/', '\\', '_'], '', $ajax));
            $view = $ajax.'.php';
        }
        else if($custom = Http::fromGet('custom', ''))
        {
            $this -> view = $custom = trim(str_replace(['.', '/', '\\'], '', $custom));
            $view = $custom.'.php';
        }

        if(Http::fromGet('ajax') && (Http::isAjaxRequest() || $ajax === 'upload-editor'))
            $file = Registry::get('IncludeAdminPath').'ajax/'.$view;
        else if(isset($custom))
            $file = Registry::get('IncludePath').'customs/adminpanel/'.$view;
        else
            $file = Registry::get('IncludeAdminPath').'views/'.$view;

        if(is_file($file))
            return $file;
        else
        {
            $this -> view = '404';

            Http::sendStatusCodeHeader(404);
            return Registry::get('IncludeAdminPath').'views/view-404.php';
            exit();
        }
    }

    /**
     * Generates regulat CSRF token to use in forms.
     */
    public function createCSRFToken(): string
	{
		$token = $_SERVER["REMOTE_ADDR"].$_SERVER["HTTP_USER_AGENT"];
		$token .= $this -> user -> getField("login").$this -> user -> getField("password");
        $token .= Registry::get('SecretCode');
		
		return Service::createHash($token, "random");
	}

    /**
     * Returns the current limit for pagination.
     * @return int limit value
     */
    static public function getPaginationLimit(): int
    {
        Session::start('admin_panel');
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
        Session::start('admin_panel');
        $settings = Session::get('settings');

        return array_key_exists($key, $settings) ? $settings[$key] : null;
    }

    public function updateUserSessionSetting(string $key, mixed $value)
    {
        Session::start('admin_panel');
        $settings = Session::get('settings');
        $settings[$key] = $value;
        Session::set('settings', $settings);

        $this -> user -> updateSetting($key, $value);

        return $settings;
    }

    public function getModelSessionSetting(string $model, string $key)
    {
        Session::start('admin_panel');
        $settings = Session::get('settings');
        $settings[$model] ??= [];

        return array_key_exists($key, $settings[$model]) ? $settings[$model][$key] : null;
    }

    public function updateModelSessionSetting(string $model, string $key, mixed $value)
    {
        Session::start('admin_panel');
        $settings = Session::get('settings');
        $settings[$model] ??= [];
        $settings[$model][$key] = $value;
        
        Session::set('settings', $settings);
        $this -> user -> saveSettings($settings);

        return $settings;
    }

    public function defineCurrentUserRegion()
    {
        $region = $this -> getUserSessionSetting('user-region') ?? '';

        if(!I18n::checkRegion($region))
            $region = I18n::defineRegion();
        
        I18n::setRegion($region);

        return $this;
    }

    public function displayInternalError(string $error_key = '')
	{
		$error_key = $error_key === '' ? 'error-occurred' : $error_key;
		$interal_error_text = I18n::locale($error_key);
		
        Http::sendStatusCodeHeader(404);
        include Registry::get('IncludeAdminPath').'controls/internal-error.php';
        exit();
    }

    public function runVersions(object $model)
	{
		$this -> versions = new Versions($model -> getModelClass(), $model -> getId());
		$this -> versions -> setLimit($model -> getVersionsLimit());
		
		return $this;
	}
	
	public function passVersionContent(object $model)
	{
		$model -> read($this -> versions -> load());
		
		return $this;
	}

    public function allowDeleteRecord($model, $id)
    {
        $error = '';
        $arguments = [];

        if(!$model -> checkRecordById($id))
            $error = 'error-wrong-record';
        else if($model -> getModelClass() == 'users' && intval($id) == 1)
            $error = 'no-delete-root';

        if($model_class = $model -> setId($id) -> checkForChildren())
            if(Registry::checkModel($model_class))            
            {
                $error = 'no-delete-model';
                $arguments['module'] = (new $model_class) -> getName();
            }
            else
                $error = 'no-delete-parent';

        
        return $error ? I18n::locale($error, $arguments) : '';
    }

    public function displayWarningMessages()
	{
        Session::start('admin_panel');

        if(true === Session::get('hide-warnings'))
            return;

		$message = [];
		
		if(!Router::isLocalHost() && Registry::onDevelopment())
			$message[] = I18n::locale("warning-development-mode");
			
		$root_password = Database::instance() -> getCell("SELECT `password` FROM `users` WHERE `id`='1'");
			
		if(!Router::isLocalHost() && Service::checkHash("root", $root_password))
			$message[] = I18n::locale("warning-root-password");

		$logs_folder = Registry::get("IncludePath")."log/";
		
		if(is_dir($logs_folder) && !is_writable($logs_folder))
			$message[] = I18n::locale("warning-logs-folder");
			
		$files_folders = array("", "files/", "images/", "models/", "tmp/", "tmp/filemanager/");
		$files_root = Registry::get("FilesPath");
		
		foreach($files_folders as $folder)
			if(is_dir($files_root.$folder) && !is_writable($files_root.$folder))
			{
				$message[] = I18n::locale("warning-userfiles-folder");
				break;
			}
        
		if(count($message))
		{
			$html = "<div id=\"admin-system-warnings\">\n";

			foreach($message as $string)
				$html .= "<p>".$string."</p>\n";
   
			return $html."<span id=\"hide-system-warnings\">".I18n::locale("hide")."</span>\n</div>\n";
		}
		else
            Session::set('hide-warnings', true);
	}

    public function checkSessionAuthorization()
    {
        Session::start('admin_panel');
        $auth = Session::get('user');

        if(!$auth || !isset($auth['id']))
            return false;

        $row = Database::instance() -> getRow("SELECT * FROM `users` WHERE `id`='".intval($auth['id'])."'");

        if(!$row['active'] && $row['id'] != 1)
            return false;

        if(md5($row['password']) != $auth['password'])
            return false;
        
        return $row['id'];
    }

    public function checkCookieAuthorization()
    {
        $key = Login::getAutoLoginCookieName();
        
        if(!$cookie = Http::getCookie($key))
            return false;

        return (new Login) -> autoLogin($cookie);
    }

    public function checkAnyAuthorization()
    {
        return $this -> checkSessionAuthorization() || $this -> checkCookieAuthorization();
    }

    static public function getAdminPanelSettingCacheValue(string $container, string $key): mixed
    {
        $cell = Registry::getDatabaseSetting($container);

        if($cell === null || $cell === false)
            return null;

        $cell = @unserialize($cell);

        if(is_array($cell) && isset($cell['until'], $cell['build'], $cell['data']) && is_array($cell['data']))
            if(time() < $cell['until'] && $cell['build'] == Registry::get('Build'))
                if(isset($cell['data'][$key]))
                    return $cell['data'][$key];
        
        return null;
    }

    static public function saveAdminPanelSettingCacheValue(string $container, string $key, mixed $value, int $time): void
    {
        $cell = Registry::getDatabaseSetting($container);

        if(!$cell)
            $cell = ['data' => [], 'until' => time() + $time, 'build' => Registry::get('Build')];
        else
            $cell = @unserialize($cell);

        if(!is_array($cell) || !isset($cell['until'], $cell['build']) || time() >= $cell['until'] || $cell['build'] != Registry::get('Build'))
            $cell = ['data' => [], 'until' => time() + $time, 'build' => Registry::get('Build')];

        $cell['data'][$key] = $value;

        Registry::setDatabaseSetting($container, serialize($cell));
    }
}