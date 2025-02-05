<?php
/**
 * Manages users and their access rights in the admin panel.
 * Core class for verifying user authorization within the MV admin area.
 * Also controls user permissions for accessing MV modules.
 */
class User
{
	/**
	 * Object with settings.
	 * @var object Registry
	 */ 
	public $registry;
   
	/**
	 * Database manager object.
	 * @var object Database
	 */ 
	public $db;

	/**
	 * UserSession object to control the session.
	 * @var object UserSession
	 */  
	public $session;
	
	/**
	 * Current SQL table name.
	 * @var string
	 */ 
	private const TABLE = 'users';
	
	/**
	 * Table with users rights.
	 * @var string
	 */ 
	private const RIGHTS_TABLE = 'users_rights';
	
	/**
	 * All data related to current user (login, name, ...)
	 * @var array
	 */ 
	private $content;
	
	/**
	 * Id of current user.
	 * @var int
	 */ 
	private $id;
	
	/**
	 * Error message text.
	 * @var string
	 */
	private $error;

	/**
	 * List of current user's rights.
	 * @var array
	 */ 
	private $rights = [];
	
	/**
	 * Sets tables and needed objects, also gets the users rights and starts session.
	 */
	public function __construct($id)
	{
		$this -> registry = Registry::instance(); //Langs and settings
		$this -> db = DataBase::instance(); //Manages database

		 //Current user's data
		$this -> content = $this -> db -> getRow("SELECT * FROM `".self::TABLE."` 
		                                          WHERE `id`=".intval($id));
		
		if(!isset($this -> content['id']) || !$this -> content['id'])
		{
			$this -> content = null;
			return;
		}
											
		$this -> id = $this -> content['id']; //Sets user id
		
		//Gets user's rights in amdin panel
		$this -> rights = $this -> db -> getAll("SELECT * FROM `".self::RIGHTS_TABLE."` 
		                                         WHERE `user_id`='".$this -> id."'");
		
		//Changes the format of rights for the class methods
		$this -> rights = Users::arrangeRights($this -> rights);
		
		if($this -> id)  //Object to control the session for this user	
			$this -> session = new UserSession($this -> id);

		Session::start('admin_panel');
		Session::set('settings', $this -> loadSettings());
	}
	
	public function getContent() { return $this -> content; }
	public function getId() { return $this -> id; }
 	public function getField($field) { return $this -> content[$field]; }
 	public function getError() { return $this -> error; }

	static public function updateLoginData($login, $password)
 	{
		Session::start('admin_panel');
        $auth = Session::get('user');

 		$auth['login'] = $login;
 		
 		if($password)
 		{
 			$password = (Registry::getInitialVersion() >= 2.2) ? $password : md5($password);
 			$auth['password'] = md5($password);
 		}

		 Session::set('user', $auth);
 	}
 	
 	public function saveSettings($settings)
 	{
 		$settings = base64_encode(json_encode($settings));
 		
 		$this -> db -> query("UPDATE `".self::TABLE."` 
 							  SET `settings`=".$this -> db -> secure($settings)." 
 							  WHERE `id`='".$this -> id."'");
 	}
 	
 	public function loadSettings()
 	{
 		$data = $this -> db -> getCell("SELECT `settings` 
 										FROM `".self::TABLE."`  
 										WHERE `id`='".$this -> id."'");
 		
		return json_decode(base64_decode(strval($data)), true);
 	}
 	
 	public function updateSetting($key, $value)
 	{
 		$settings = $this -> loadSettings();
 		$settings[$key] = $value;
 		$this -> saveSettings($settings);
 		
 		return $this;
 	}
 	
	/**
	 * Checks if the user's credential for the modele exists.
	 */
	public function checkModelRights($module, $right): bool
	{
		//Root user has access to any module othe users must have rights via policy
		if($this -> id == 1) 
			return true;
			
		$all_modules = array_merge(array_keys(Registry::get('ModelsLower')), 
								  ["users", "log", "garbage", "file_manager"]);
		
		$module = strtolower($module);
		
		if(!isset($this -> rights[$module]) || !in_array($module, $all_modules))
			return false;
				
		return (bool) $this -> rights[$module][$right];
	}

	/**
	 * Check the rights inside the any amdin panel page related to module (edit, create, ...) 
	 * and redirects if no rights.
	 */
	public function extraCheckModelRights($module, $right)
	{
		if(!$this -> checkModelRights($module, $right))
		{
			$this -> error = I18n::locale("error-no-rights");
            include $this -> registry -> getSetting("IncludeAdminPath")."controls/internal-error.php";
		}
	}
	
	public function checkModelRightsJS($module, $right, $href)
	{
		if($this -> checkModelRights($module, $right))
    		return $href;
		else
			return "javascript:$.modalWindow.open(mVobject.locale('no_rights'), {css_class: 'alert'});";
	}
	
	public function getUserSkin()
	{
		$path = $this -> registry -> getSetting("IncludeAdminPath")."interface/skins/";
		Session::start('admin_panel');
        $settings = Session::get('settings');
		
		if(isset($settings['skin']))
			if($settings['skin'] == 'none')
				return 'none';
			else if(is_dir($path.$settings['skin']) && is_file($path.$settings['skin']."/skin.css"))
				return $settings['skin'];
	}
	
	public function getAvailableSkins()
	{
		$skins = ['mountains'];
		$path = $this -> registry -> getSetting("IncludeAdminPath")."interface/skins/";
		
		$folders = scandir($path);
		
		foreach($folders as $folder)
			if(!preg_match("/^\./", $folder) && $folder != 'mountains' && $folder != 'default')
				$skins[] = $folder;

		$skins[] = 'none';
				
		return $skins;
	}
	
	public function setUserSkin($name) 
	{
		$path = $this -> registry -> getSetting("IncludeAdminPath")."interface/skins/";
		
		if((is_dir($path.$name) && is_file($path.$name."/skin.css")) || $name == "none")
		{
			Session::start('admin_panel');
			$settings = Session::get('settings');
			$settings['skin'] = $name;
			Session::set('settings', $settings);

			$this -> updateSetting("skin", $name);
			
			return 1;
		}		
	}
	
	public function displayUserSkinSelect()
	{
		Session::start('admin_panel');
		$settings = Session::get('settings');

		$path = $this -> registry -> getSetting("IncludeAdminPath")."interface/skins/";
		$html = "<select name=\"admin_panel_skin\" id=\"user-settings-skin-select\">\n";
		$folders = array("none") + scandir($path);
		
		foreach($folders as $folder)
			if(!preg_match("/^\./", $folder))
			{
				$selected = '';
				
				if(Http::fromPost('admin_panel_skin') == $folder)
					$selected = ' selected="selected"';
				else if(empty($_POST) && isset($settings['skin']) && $settings['skin'] == $folder)
					$selected = ' selected="selected"';
				
				$html .= "<option".$selected." value=\"".$folder."\">".$folder."</option>\n";
			}
		
		return $html."</select>\n";
	}
}