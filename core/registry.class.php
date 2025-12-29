<?php
/**
 * Singleton class that stores all application configurations and the current version of the MV core.
 * This class provides access to configuration settings throughout the application.
 * Any configuration value can be retrieved using the Registry::get('option_name') method.
 */
class Registry
{
	/**
	 * Current version of MV core and admin panel, mostly for internal needs.
	 * Works together with 'Version' setting from config/settings.php 
	 * @var float
	 */
	private static $version = 3.31;
	
	/**
	 * Instance of singleton pattern to keep only one copy of object.
	 * @var Registry
	 */
	private static $instance;
	
	/**
	 * Configuration settings from /config/ directory and .env file.
	 * @var array
	 */
	private static $settings = [];
	
	private function __construct() {}
	
	/**
	 * Creates / returns the Registry object as singleton pattern.
	 */
	static public function instance()
	{
		if(!isset(self::$instance))
			self::$instance = new self();
		
		return self::$instance;
	}

	static public function generateSettings(array &$settings_list)
	{
		if(isset($settings_list['ProjectFolder']))
			$settings_list['MainPath'] = $settings_list['ProjectFolder'];

		if(isset($settings_list['MainPath']) && $settings_list['MainPath'] !== '/')
			$settings_list['MainPath'] = '/'.preg_replace('/^\/?(.+[^\/])\/?$/', '$1', $settings_list['MainPath']).'/';

		//Some servers have trailing slash in DOCUMENT_ROOT, some not...
		$settings_list['DocumentRoot'] = preg_replace('/(\\\\|\/)$/', '', $_SERVER['DOCUMENT_ROOT']);

		//Absolute path to userfiles folder
		$settings_list['FilesPath'] = $settings_list['IncludePath'].$settings_list['FilesPath'].'/';
	
		//Absolute path to include files into admin panel, must start and end with '/'
		$settings_list['IncludeAdminPath'] = $settings_list['IncludePath'].$settings_list['AdminFolder'].'/'; 
	
		//Admin panel location path including app subfolder, must start and end with '/'
		$settings_list['AdminPanelPath'] = $settings_list['MainPath'].$settings_list['AdminFolder'].'/';

		//Setting based on http domain name
		if(isset($settings_list['DomainName'], $settings_list['MainPath'], $settings_list['AdminPanelPath']))
		{
			$settings_list['DomainName'] = preg_replace('/\/$/', '', $settings_list['DomainName']);
			$settings_list['HttpPath'] = $settings_list['DomainName'].$settings_list['MainPath'];
			$settings_list['HttpAdminPanelPath'] = $settings_list['DomainName'].$settings_list['AdminPanelPath'];
		}

		//Tries to define native server domain
		if('' !== $settings_list['ServerDomain'] = $_SERVER['SERVER_NAME'] ?? '')
		{
			$settings_list['ServerDomain'] = 'http'.(Http::isHttps() ? 's' : '').'://'.$settings_list['ServerDomain'];

			if(!isset($settings_list['DomainName']) || !$settings_list['DomainName'])
				$settings_list['DomainName'] = $settings_list['ServerDomain'];
		}
	}

	/**
	 * Checks passed values of some config settings, before application run.
	 */
	public function checkSettingsValues()
	{
		if(self::$settings['Mode'] !== 'development' && self::$settings['Mode'] !== 'production')
		{
			$message = "Settings 'APP_ENV' and 'Mode' may have 'development' or 'production' values only.";
			Debug::displayError($message);
		}

		$supported = self::$settings['SupportedRegions'];

		if(!in_array(self::$settings['Region'], $supported))
		{
			$message = "Settings 'APP_REGION' and 'Region' must have values from the list ";
			$message .= "of supported regions from config/settings.php file.";
			$message .= "<br>The actual setting name is 'SupportedRegions'.<br>";
			$message .= "Available values are: ".implode(', ', $supported);

			Debug::displayError($message);
		}

		return $this;
	}

	/**
	 * Transforms names of models and plugins to snake lower case.
	 */
	public function lowerCaseConfigNames()
	{
		$names = [];

		foreach(self::$settings['Models'] as $name)
			$names[strtolower($name)] = strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $name));

		self::$settings['ModelsLower'] = $names;

		$names = [];

		foreach(self::$settings['Plugins'] as $name)
			$names[strtolower($name)] = strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $name));

		self::$settings['PluginsLower'] = $names;

		self::$settings['DataTypesLower'] = [
			'datetime' => 'date_time',
			'multiimages' => 'multi_images',
			'manytomany' => 'many_to_many',
			'manytoone' => 'many_to_one'
		];

		return $this;
	}

	/**
	 * Transforms names of models and plugins to camel case.
	 */
	public function camelCaseModelsAndPlugins()
	{
		$names = [];

		foreach(self::$settings['Models'] as $name)
			$names[$name] = str_replace(' ', '', ucwords(str_replace('_', ' ', $name)));

		self::$settings['ModelsCamelCase'] = $names;
		
		$names = [];

		foreach(self::$settings['Plugins'] as $name)
			$names[$name] = str_replace(' ', '', ucwords(str_replace('_', ' ', $name)));

		self::$settings['PluginsCamelCase'] = $names;

		return $this;
	}

	/**
	 * Creates aliases between old and new names of core classes.
	 */
	public function createClassesAliases()
	{
		class_alias('ModelInitial', 'Model_Initial');
		class_alias('ModelBase', 'Model_Base');
		class_alias('ModelSimple', 'Model_Simple');
		class_alias('Paginator', 'Pager');
		class_alias('AdminPanel', 'Admin_Panel');
		class_alias('FlashMessages', 'Flash_Messages');

		return $this;
	}
	
	/**
	 * Loads and combines the configurations from config files into Registry object.
	 */
	public function loadSettings(array $settings_list)
	{
		self::$settings = array_merge(self::$settings, $settings_list);
	}

	/**
	 * Determines the name of actual .env file.
	 */
	public function defineEnvFileName(): string
	{
		$env = self::$settings['IncludePath'].'.env';
		
		if(!is_file($env))
		{
			$other_envs = ['.local', '.development', '.stage', '.demo', '.test', '.production'];
			
			foreach($other_envs as $variant)
				if(is_file($env.$variant))
				{
					$env .= $variant;
					break;
				}
		}

		return $env;
	}

	/**
	 * Checks if the actual .env file is available via http request.
	 */
	public function checkEnvFileViaHttp(string $file)
	{
		if(!is_file($file))
			return $this;

		$file_http = Service::getAbsoluteHttpPath('/'.basename($file));
		$context = stream_context_create(['http' => ['method' => 'HEAD']]);
		$headers = get_headers($file_http, false, $context);

		if(strpos($headers[0], '200 OK') !== false)
		{
			$message = 'The Env file "'.$file_http.'" is available via http protocol.';
			$message .= '<br>It contains sensitive data, so you need to restrict the http access for this file.';
			$message .= '<br>Usually it can be done in http server settings like .htaccess or nginx.conf files.';

			Debug::displayError($message);
		}

		return $this;
	}

	/**
	 * Takes settings from .env file and combines them with other config files settings.
	 */
	public function loadEnvironmentSettings()
	{
		$env = $this -> defineEnvFileName();
		
		if(!is_file($env))
			return $this;

		$data = parse_ini_file($env, false, INI_SCANNER_TYPED);

		if(!is_array($data))
			return $this;

		if(isset($data['APP_FOLDER']) && $data['APP_FOLDER'] !== '/')
			$data['APP_FOLDER'] = '/'.preg_replace('/^\/?(.+[^\/])\/?$/', '$1', $data['APP_FOLDER']).'/';
		
		if(isset($data['APP_DOMAIN']) && $data['APP_DOMAIN'] !== '')
			$data['APP_DOMAIN'] = preg_replace('/\/$/', '', $data['APP_DOMAIN']);

		$settings = ['Mode' => 'APP_ENV', 'TimeZone' => 'APP_TIMEZONE', 'DomainName' => 'APP_DOMAIN', 
					 'MainPath' => 'APP_FOLDER', 'SecretCode' => 'APP_TOKEN', 'Region' => 'APP_REGION',
					 'DbEngine' => 'DATABASE_ENGINE', 'DbHost' => 'DATABASE_HOST', 'DbUser' => 'DATABASE_USER', 
					 'DbPassword' => 'DATABASE_PASSWORD', 'DbName' => 'DATABASE_NAME', 
					 'EmailMode' => 'EMAIL_SENDER', 'SMTPHost' => 'EMAIL_HOST', 'SMTPPort' => 'EMAIL_PORT', 
					 'SMTPUsername' => 'EMAIL_USER', 'SMTPPassword' => 'EMAIL_PASSWORD', 'EmailFrom' => 'EMAIL_FROM'];

		foreach($settings as $old => $new)
			if(array_key_exists($new, $data) && trim($data[$new]) !== '')
				self::$settings[$old] = trim($data[$new]);
		
		self::$settings = array_merge(self::$settings, $data);

		self::$settings['AdminPanelPath'] = self::$settings['MainPath'].self::$settings['AdminFolder'].'/';
		self::$settings['HttpPath'] = self::$settings['DomainName'].self::$settings['MainPath'];
		self::$settings['HttpAdminPanelPath'] = self::$settings['DomainName'].self::$settings['AdminPanelPath'];
		self::$settings['EnvFile'] = basename($env);

		if(!Debug::isCommandLineInterface())
			$this -> checkEnvFileViaHttp($env);

		return $this;
	}

	/**
	 * Gets all initially loaded settings and also the ones added dynamicaly by coder.
	 * @return array
	 */
	static public function getAllSettings()
	{
		return self::$settings;
	}
	
	/**
	 * Returns one setting by it's key if such key exists.
	 * @return mixed
	 */
	static public function getSetting(string $key)
	{
		if($key == "SecretCode" && (!isset(self::$settings[$key]) || strlen(self::$settings[$key]) < 32))
		{
			$message = "You must specify 'APP_TOKEN' setting in .env file, or 'SecretCode' ";
			$message .= " setting in ~/config/setup.php file, at least 32 symbols.<br>";
			$message .= "You can use this random code: ".Service::strongRandomString(mt_rand(32, 40));

			Debug::displayError($message);
		}				
		else if(isset(self::$settings[$key]))
			return self::$settings[$key];
	}

	/**
	 * Returns one setting by it's key if such key exists.
	 * @param string $key name of setting
     * @param string $default value of setting if key not existed
	 * @return mixed
	 */
	static public function get(string $key, mixed $default = null)
	{
		if($key === 'SecretCode' || $key === 'APP_TOKEN')
			return self::getSetting($key);

		return isset(self::$settings[$key]) ? self::$settings[$key] : $default;
	}
	
	/**
	 * Sets one setting by it's key even if such key does not exist.
	 * @return $this
	 */
	public function setSetting(string $key, mixed $value)
	{
		self::$settings[$key] = $value;

		return $this;
	}

	/**
	 * Sets one setting by it's key even if such key does not exist.
	 */
	static public function set(string $key, mixed $value)
	{
		self::$settings[$key] = $value;
	}
	
	/**
	 * Gets one setting by it's key from common settings db table named 'settings'.
	 * @return mixed
	 */
	static public function getDatabaseSetting(string $key, mixed $default = null)
	{
		$db = Database::instance();
		$value =  $db -> getCell("SELECT `value` FROM `settings` WHERE `key`=".$db -> secure($key));

		return ($value === null || $value === false) ? $default : $value;
	}
	
	/**
	 * Saves one setting by it's key into common settings db table named 'settings'.
	 * @return mixed
	 */	
	static public function setDatabaseSetting(string $key, mixed $value)
	{
		$db = Database::instance();
		
		if(!$db -> getCount("settings", "`key`=".$db -> secure($key)))
			return $db -> query("INSERT INTO `settings`(`key`,`value`) 
			  					 VALUES(".$db -> secure($key).",".$db -> secure($value).")");
		else
			return $db -> query("UPDATE `settings` 
								 SET `value`=".$db -> secure($value)."
								 WHERE `key`=".$db -> secure($key));
	}

	/**
	 * Defines name of table for model object.
	 * @return string
	 */
	static public function defineModelTableName(string $class_name)
	{
		$class_name = strtolower($class_name);

		if(array_key_exists($class_name, self::$settings['ModelsLower']))
			return self::$settings['ModelsLower'][$class_name];

		if(in_array($class_name, ['garbage', 'log', 'users']))
			return $class_name;

		return '';
	}

	/**
	 * Defines name of table for plugin object.
	 * @return string
	 */
	static public function definePluginTableName(string $class_name)
	{
		$class_name = strtolower($class_name);

		if(array_key_exists($class_name, self::$settings['PluginsLower']))
			return self::$settings['PluginsLower'][$class_name];

		return '';
	}
	
	/**
	 * Checks in loaded configurations if the certain model is active.
	 * @param string $name name of model (class name)
	 * @return bool
	 */
	static public function checkModel(string $name)
	{
		$lower = strtolower($name);

		if(array_key_exists($lower, self::$settings['ModelsLower']))
			return true;

		if(in_array($lower, self::$settings['ModelsLower']))
			return true;

		if(in_array($lower, ['garbage', 'log', 'users']))
			return true;

		return false;
	}

	/**
	 * Checks in loaded configurations if the certain model is active.
	 * @param string $name name of model (class name)
	 * @return bool
	 */
	static public function checkPlugin(string $name)
	{
		$lower = strtolower($name);

		if(array_key_exists($lower, self::$settings['PluginsLower']))
			return true;

		if(in_array($lower, self::$settings['PluginsLower']))
			return true;
		
		return false;
	}

	/**
	 * Returns lowercased class name of model by table name.
	 * @return string
	 */
	static public function getModelClassByTable(string $table)
	{
		if(in_array($table, self::$settings['ModelsLower']))
			return array_search($table, self::$settings['ModelsLower']);


		return '';
	}
	
	/**
	 * Returns current (not initial) version of MV core.
	 * @return float|int
	 */
	static public function getVersion()
	{
		return self::$version;
	}
	
	/**
	 * Returns initial (at the download moment) version of MV core.
	 * @return float
	 */	
	static public function getInitialVersion()
	{
		return floatval(self::$settings['Version']);
	}

	/**
	 * Returns actual composer package version of MV core.
	 * @return float
	 */	
	static public function getCorePackageVersion()
	{
		//If MV was installed via composer
		$package = 'makscraft/mv-core';

		if(class_exists('\Composer\InstalledVersions'))
			if(\Composer\InstalledVersions::isInstalled($package))
				if($version = \Composer\InstalledVersions::getPrettyVersion($package))
					return $version;

		//If MV was installed manually without composer
		$decimals = self::$version * 10 - floor(self::$version * 10) > 0 ? 2 : 1;

		return number_format(self::$version, $decimals);
	}

	/**
	 * Checks if the application works on development environment.
	 * @return bool
	 */
	static public function onDevelopment()
	{
		return self::$settings['Mode'] === 'development';
	}

	/**
	 * Checks if the application works on production environment.
	 * @return bool
	 */
	static public function onProduction()
	{
		return self::$settings['Mode'] === 'production';
	}

	/**
	 * Creates list of plugins to start automatically after creation of main MV object.
	 */
	public function findAutoStartingPlugins()
	{
		self::$settings['AutoStartingPlugins'] = [];
		$old_version = self::getInitialVersion() < 3.2 ? true : false;

		foreach(self::$settings['Plugins'] as $plugin)
		{
			$auto_start = $old_version;

			if(class_exists($plugin))
			{
				$reflection = new ReflectionClass($plugin);

				if($reflection -> hasProperty('auto_start'))
				{
					$property = $reflection -> getProperty('auto_start');
    				$property -> setAccessible(true);
					$value = $property -> getDefaultValue();

					if(!$old_version)
						$auto_start = (bool) $value;
					else if($value !== null)
						$auto_start = (bool) $value;
				}
			}

			if($auto_start)
				self::$settings['AutoStartingPlugins'][strtolower($plugin)] = $plugin;
		}
	}
}