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
		
		if(!isset($_SESSION['mv']['security']) && $this -> securityNeedScan())
			$_SESSION['mv']['security']['threats'] = $this -> securityScanFiles();
		
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
	
	public function ajaxRequestCheck()
	{
		if(!isset($_SERVER['HTTP_X_REQUESTED_WITH']))
			return false;
		
		if(isset($_SESSION['mv']['user']['id'], $_SESSION['mv']['user']['password']))
			$this -> user = new User($_SESSION['mv']['user']['id']);
		
		if(!is_object($this -> user) || !$this -> user -> checkUserLogin())
		{
			$autologin = false;
				
			if(isset($_COOKIE[Login :: getAutoLoginCookieName()]))
			{
				$login = new Login();
				$autologin = $login -> autoLogin($_COOKIE[Login :: getAutoLoginCookieName()]);
		
				if($autologin)
				{
					$login -> rememberUser($autologin);
					
					if(isset($_SESSION['mv']['user']['id'], $_SESSION['mv']['user']['password']))
						$this -> user = new User($_SESSION['mv']['user']['id']);
					else
						return false;
				}
				else
					$login -> cancelRemember();
			}
			
			return $autologin;
		}
		
		return true;
	}
	
	public function displayWarningMessages()
	{
		if(isset($_SESSION['mv']['closed-warnings']) && $_SESSION['mv']['closed-warnings'])
			return;
			
		$message = [];
		$router = new Router();
		
		if(!Router :: isLocalHost() && Registry :: onDevelopment())
			$message[] = I18n :: locale("warning-development-mode");
			
		$root_password = $this -> db -> getCell("SELECT `password` FROM `users` WHERE `id`='1'");
			
		if(!$router -> isLocalHost() && Service :: checkHash("root", $root_password))
			$message[] = I18n :: locale("warning-root-password");

		$logs_folder = $this -> registry -> getSetting("IncludePath")."log/";
		
		if(is_dir($logs_folder) && !is_writable($logs_folder))
			$message[] = I18n :: locale("warning-logs-folder");
			
		$files_folders = array("", "files/", "images/", "models/", "tmp/", "tmp/filemanager/");
		$files_root = $this -> registry -> getSetting("FilesPath");
		
		foreach($files_folders as $folder)
			if(is_dir($files_root.$folder) && !is_writable($files_root.$folder))
			{
				$message[] = I18n :: locale("warning-userfiles-folder");
				break;
			}
		
		if(isset($_SESSION['mv']['security']['threats']) && count($_SESSION['mv']['security']['threats']))
			foreach($_SESSION['mv']['security']['threats'] as $threat)
				$message[] = I18n :: locale('warning-dangerous-code')." ".$threat;
		
		if(count($message))
		{
			$html = "<div id=\"admin-system-warnings\">\n";

			foreach($message as $string)
				$html .= "<p>".$string."</p>\n";
   
			return $html."<span id=\"hide-system-warnings\">".I18n :: locale("hide")."</span>\n</div>\n";
		}
		else
			$_SESSION['mv']['closed-warnings'] = true;
	}
	
	public function getToken()
	{
		$token = $_SESSION['mv']['user']['token'].$_SERVER["REMOTE_ADDR"].$_SERVER["HTTP_USER_AGENT"];
		$token .= $this -> user -> getField("login").$this -> user -> getField("password");
		
		return Service :: createHash($token, "random");
	}
	
	public function securityNeedScan()
	{
		$found = intval($this -> registry -> getDatabaseSetting('security_scan_found'));
		$date = $this -> registry -> getDatabaseSetting('security_scan_date');
		
		if($found || !$date)
			return true;
		
		$date = I18n :: formatDate($date, "d-m-Y H:i:s");
		
		if(time() - strtotime($date) > 86400)
			return true;
	}
	
	public function securityReadFolder($folder)
	{
		$root_path = $this -> registry -> getSetting('IncludePath');
		$descriptor = @opendir($folder);
		$result = [];
		
		if(!$descriptor)
			return false;

		$skip = $this -> registry -> getSetting('SkipSecurityScan');

		if(is_array($skip))
			foreach($skip as $directory)
		 		if($directory && $root_path.$directory."/" == $folder)
		 			return [];
		
		while(false !== ($file = readdir($descriptor)))
		{
			if($file == "." || $file == ".." || strpos($file, ".") === 0)
				continue;
			
			if(is_file($folder.$file) && Service :: getExtension($file) == "php")
				$result[] = $folder.$file;
			else if(is_dir($folder.$file) && $folder.$file != $root_path."userfiles")
			{
				$result[] = $folder.$file."/";
				$subfolder = $this -> securityReadFolder($folder.$file."/");
				
				if(is_array($subfolder) && count($subfolder))
					$result = array_merge($result, $subfolder);
			}
		}
		
		return $result;
	}
	
	public function securityScanFiles()
	{
		$root_path = $this -> registry -> getSetting('IncludePath');
		$files_system = $this -> securityReadFolder($root_path);
		$dangerous_files = [];
				
		foreach($files_system as $file)
		{
			if(!is_file($file))
				continue;
			
			$content = trim(file_get_contents($file));
			$found = $this -> securityFindFunctionCall($content);
			
			if(count($found))
				$dangerous_files[] = Service :: removeFileRoot($file)." -> ".implode(", ", $found);
		}
		
		$this -> registry -> setDatabaseSetting('security_scan_date', I18n :: getCurrentDateTime("SQL"));
		$this -> registry -> setDatabaseSetting('security_scan_found', count($dangerous_files));
		
		return $dangerous_files;
	}
	
	public function securityFindFunctionCall($content)
	{
		$functions = ["phpinfo", "system", "exec", "shell_exec", "create_function", "eval", "assert", "base64_decode"];
		$result = [];
		
		foreach($functions as $target)
		{
			if($target == "system")
			{
				$number = preg_match_all("/\W".$target."\(/ui", $content, $matches);
				$safe = preg_match_all("/\Wnew\s+".$target."\(/ui", $content, $matches);
				
				if($number > $safe)
					$result[] = $target."()";
			}
			else if($target == "base64_decode")
			{
				if(preg_match("/\Wbase64_decode\(\\\$_/ui", $content))
					$result[] = $target."()";
			}
			else if(preg_match("/\W".$target."\(/ui", $content))
				$result[] = $target."()";
		}
		
		return $result;
	}
}
