<?php
/**
 * Main object in admin panel. 
 * Contains object of current model, settings and other tools.
 * Checks the authorization of current administartor.
 */ 
class System
{
	/**
	 * Current user of admin panel.
	 * @var object User
	 */
	public $user;
	
	/**
	 * Current model, run in admin panel.
	 * @var object extends Model or ModelSimple
	 */
	public $model;

	/**
	 * Object to display the main menus in interface of admin panel.
	 * @var object Menu
	 */
	public $menu;
	
	/**
	 * Object with settings and configurations of the application.
	 * @var object Registry
	 */
	public $registry;
	
	/**
	 * Database manager object.
	 * @var object Database
	 */
	public $db;
	
	/**
	 * Localization and regional standarts manager.
	 * @var object I18n
	 */
	public $i18n;
	
	/**
	 * Versions manager object.
	 * @var object Versions
	 */
	public $versions;
		
	/**
	 * Internal error text.
	 * @var string
	 */
	public $error;
	
	public function __construct()
	{
		ob_start();

		if(!Service::sessionIsStarted())
			session_start();
		
		$this -> registry = Registry :: instance();
		
		$time_zone = $this -> registry -> getSetting('TimeZone');
		Registry :: set('AdminPanelEnvironment', true);
		
		if($time_zone)
			date_default_timezone_set($time_zone);
		
		$this -> db = Database :: instance(); //Manages database
		$this -> i18n = I18n :: instance();
				
		$arguments = func_get_args(); //Checks some extra params
		
		//If we at some page called by ajax
		$ajax_request = (isset($arguments[0]) && $arguments[0] == 'ajax');
		
		//Auto login with cookie
		if(!$ajax_request && !isset($_SESSION['mv']['user']['id'], $_SESSION['mv']['user']['password']))
			if(isset($_COOKIE[Login :: getAutoLoginCookieName()]))
			{
				$login = new Login();
								
				if($id = $login -> autoLogin($_COOKIE[Login :: getAutoLoginCookieName()]))
				{
					$login -> rememberUser($id); //Prolongs auto login time
					header("Location: ".$_SERVER['REQUEST_URI']);
					exit();
				}
				else
					$login -> cancelRemember();
			}
				
		if(isset($_SESSION['mv']['user']['id'], $_SESSION['mv']['user']['password']))
			$this -> user = new User($_SESSION['mv']['user']['id']);
		else if(!$ajax_request)
			$this -> backToLogin();
		
		if($ajax_request)
		{
			$region = isset($_SESSION['mv']['settings']['region']) ? $_SESSION['mv']['settings']['region'] : I18n :: defineRegion();
			$this -> i18n -> setRegion($region);
			
			return; //If it's ajax we stop further construction 
		}
		
		if(!$this -> user -> checkUserLogin())
		{
			if(isset($_COOKIE[Login :: getAutoLoginCookieName()]))
			{
				$login = new Login();
				$autologin_id = $login -> autoLogin($_COOKIE[Login :: getAutoLoginCookieName()]);

				if($autologin_id)
					$login -> rememberUser($autologin_id); //Prolongs auto login time
				else
					$login -> cancelRemember();
			}
			
			if(!isset($autologin_id) || !$autologin_id)
				$this -> backToLogin();
		}
				
		if(!isset($_SESSION['mv']['settings']))
			$_SESSION['mv']['settings'] = $this -> user -> loadSettings();
			
		if(isset($_SESSION['mv']['settings']['region']) && I18n :: checkRegion($_SESSION['mv']['settings']['region']))
			$region = $_SESSION['mv']['settings']['region'];
		else
		{
			$region = I18n :: defineRegion();
			$this -> user -> updateSetting('region', $region);
		}

		$this -> i18n -> setRegion($region);
		$this -> menu = new Menu(); //Runs object for menu building

		if(isset($arguments[0]) && $arguments[0]) //Runs module if name was passed
			$this -> runModel(strtolower($arguments[0]));
	}
	
	public function backToLogin()
	{
		$url = preg_replace("/\?.*$/", "", $_SERVER['REQUEST_URI']);
		
		if($url != $this -> registry -> getSetting("AdminPanelPath"))
		{
			$url = str_replace($this -> registry -> getSetting("AdminPanelPath"), "", $_SERVER['REQUEST_URI']);
			$search = array("&action=create", "&action=update", "&continue", "&updated", "&created", "&edit");
			
			$_SESSION['login-back-url'] = str_replace($search, "", $url);
		}
		
		header("Location: ".$this -> registry -> getSetting("AdminPanelPath")."login/");
		exit();
	}
}
