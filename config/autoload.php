<?php
/**
 * MV - content management framework for developing internet sites and applications.
 * 
 * http://mv-framework.com
 * http://mv-framework.ru
 */

if(version_compare(phpversion(), '8.0', '<'))
	exit('To run MV framework you need PHP version 8.0 or later.');

ini_set('display_errors', 1);

if(is_file(__DIR__.'/../../../../vendor/autoload.php'))
{
	$mvIncludePath = realpath(__DIR__.'/../../../..').DIRECTORY_SEPARATOR;
	$mvIncludePath = str_replace('\\', '/', $mvIncludePath);

	$mvCorePath = realpath(__DIR__.'/../core').DIRECTORY_SEPARATOR;
}
else
{
	$mvIncludePath = str_replace('\\', '/', realpath(__DIR__.'/..')).'/';
	$mvCorePath = $mvIncludePath.'core/';
}

require_once $mvIncludePath.'config/setup.php';
require_once $mvCorePath.'datatypes/base.type.php';
require_once $mvCorePath.'datatypes/bool.type.php';
require_once $mvCorePath.'datatypes/char.type.php';
require_once $mvCorePath.'datatypes/url.type.php';
require_once $mvCorePath.'datatypes/enum.type.php';
require_once $mvCorePath.'datatypes/file.type.php';
require_once $mvCorePath.'datatypes/image.type.php';
require_once $mvCorePath.'datatypes/int.type.php';
require_once $mvCorePath.'datatypes/order.type.php';
require_once $mvCorePath.'datatypes/text.type.php';
require_once $mvCorePath.'registry.class.php';
require_once $mvCorePath.'i18n.class.php';
require_once $mvCorePath.'service.class.php';
require_once $mvCorePath.'cache.class.php';
require_once $mvCorePath.'debug.class.php';
require_once $mvCorePath.'database.class.php';
require_once $mvCorePath.'cache.class.php';
require_once $mvCorePath.'cache_media.class.php';
require_once $mvCorePath.'model_initial.class.php';
require_once $mvCorePath.'model_base.class.php';
require_once $mvCorePath.'model.class.php';
require_once $mvCorePath.'model_simple.class.php';
require_once $mvCorePath.'plugin.class.php';
require_once $mvCorePath.'log.class.php';
require_once $mvCorePath.'router.class.php';
require_once $mvCorePath.'builder.class.php';
require_once $mvCorePath.'imager.class.php';
require_once $mvCorePath.'content.class.php';
require_once $mvCorePath.'record.class.php';
require_once $mvCorePath.'paginator.class.php';

$mvConfigFiles = [
	$mvIncludePath.'config/setup.php',
	$mvIncludePath.'config/settings.php',
	$mvIncludePath.'config/models.php',
	$mvIncludePath.'config/plugins.php'
];

//Trys to get all settings at one time from cache file
if(isset($mvSetupSettings['Build']))
{
	$mvEnvCacheFile = $mvIncludePath.$mvSetupSettings['FilesPath'].'/cache/env-'.$mvSetupSettings['Build'].'.php';

	if(is_file($mvEnvCacheFile))
	{
		$cache = require_once($mvEnvCacheFile);

		//After new build we check files modification for certain period in any environment
		$check = ($cache['CheckConfigFilesUntil'] - time()) > 0;

		//Checks config files modification time if not in production environment
		if($check || $cache['Mode'] !== 'production')
		{
			$hash = Service :: getFilesModificationTimesHash($mvConfigFiles);
			$env = $mvIncludePath.'.env';

			if($hash == $cache['ConfigFilesHash'])
				if(!is_file($env) || filemtime($env) == $cache['EnvFileTime'])
					$mvSetupSettings = array_merge(['LoadedFromCache' => time()], $cache);
		}
		else
			$mvSetupSettings = array_merge(['LoadedFromCache' => time()], $cache);
	}
}

//Creating settings list if cache has not being found
if(!isset($mvSetupSettings['LoadedFromCache']))
{
	require_once $mvIncludePath.'config/settings.php';
	require_once $mvIncludePath.'config/models.php';
	require_once $mvIncludePath.'config/plugins.php';
	
	$mvSetupSettings['Models'] = $mvActiveModels;
	$mvSetupSettings['Plugins'] = $mvActivePlugins;
	$mvSetupSettings['IncludePath'] = $mvIncludePath;
	$mvSetupSettings['CorePath'] = $mvCorePath;
}

//Runs main settings storage object
$registry = Registry :: instance();

//Loads all settings into Registry to get them from any place
if(!isset($mvSetupSettings['LoadedFromCache']))
{
	Registry :: generateSettings($mvSetupSettings);
	
	$registry -> loadSettings($mvSetupSettings);
	$registry -> loadSettings($mvMainSettings);
	
	$registry -> loadEnvironmentSettings() -> checkSettingsValues() -> lowerCaseConfigNames();
	
	//Saves cache confid file (if we have .env file in root folder)
	Cache :: createMainConfigFile($mvConfigFiles);
}
else
	$registry -> loadSettings($mvSetupSettings);

$registry -> createClassesAliases();

$mvAutoloadData = [
	'models' => $mvSetupSettings['Models'],
	'models_lower' => Registry :: get('ModelsLower'),
	'plugins' => $mvSetupSettings['Plugins'],
	'plugins_lower' => Registry :: get('PluginsLower'),
	'datatypes_lower' => Registry :: get('DataTypesLower')
];

$GLOBALS['mvAutoloadData'] = $mvAutoloadData;
$GLOBALS['mvSetupSettings'] = $mvSetupSettings;

//Defines class auto loader
spl_autoload_register(function($class_name)
{	
	$mvAutoloadData = $GLOBALS['mvAutoloadData'];
	$mvSetupSettings = $GLOBALS['mvSetupSettings'];
	$class_lower = strtolower($class_name);
	
	if(strpos($class_name, 'ModelElement') !== false || strpos($class_lower, '_model_element') !== false)
	{
		$class_name = str_replace(['modelelement', '_model_element'], '', $class_lower);

		if(array_key_exists($class_name, $mvAutoloadData['datatypes_lower']))
			$class_name = $mvAutoloadData['datatypes_lower'][$class_name];
		
		require_once $mvSetupSettings['CorePath'].'datatypes/'.$class_name.'.type.php';
	}
	else if(in_array($class_lower, $mvAutoloadData['models_lower']))
		require_once $mvSetupSettings['IncludePath'].'models/'.$class_lower.'.model.php';
	else if(array_key_exists($class_lower, $mvAutoloadData['models_lower']))
		require_once $mvSetupSettings['IncludePath'].'models/'.$mvAutoloadData['models_lower'][$class_lower].'.model.php';
	else if(in_array($class_lower, $mvAutoloadData['plugins_lower']))
		require_once $mvSetupSettings['IncludePath'].'plugins/'.$class_lower.'.plugin.php';
	else if(array_key_exists($class_lower, $mvAutoloadData['plugins_lower']))
		require_once $mvSetupSettings['IncludePath'].'plugins/'.$mvAutoloadData['plugins_lower'][$class_lower].'.plugin.php';
	else if(is_file($mvSetupSettings['CorePath'].''.$class_lower.'.class.php'))
		require_once $mvSetupSettings['CorePath'].''.$class_lower.'.class.php';
});

//Sets up current localization region of the application
I18n :: instance() -> setRegion($mvSetupSettings['Region']);

//Start time for debug panel
Registry :: set('WorkTimeStart', gettimeofday());

//Error handlers functions
function errorHandlerMV($type, $message, $file, $line)
{
	$message = 'Error: '.$message.' in line '.$line.' of file ~'.Service :: removeDocumentRoot($file);
	Debug :: displayError($message, $file, $line, Registry :: onDevelopment());
}

function exceptionHandlerMV(Throwable $exception)
{
	$line = $exception -> getLine();
	$file = $exception -> getFile();

	$message = 'Exception: '.$exception -> getMessage().' in line '.$line.' of file ~'.Service :: removeDocumentRoot($file);
	Debug :: displayError($message, $file, $line);
  }

function fatalErrorHandlerMV()
{
	if(!Registry :: get('ErrorAlreadyLogged'))
		if(null !== $error = error_get_last())
		{
			$message = 'Fatal error: '.$error['message'].', in line '.$error['line'].' of file ~'.Service :: removeDocumentRoot($error['file']);
			Debug :: displayError($message, $error['file'], $error['line']);
		}
}

//Sets error handlers
set_error_handler('errorHandlerMV');
set_exception_handler('exceptionHandlerMV');
register_shutdown_function('fatalErrorHandlerMV');

//Final general settings
error_reporting(0);
ini_set('display_errors', 0);

if(isset($mvSetupSettings['HttpOnlyCookie']) && $mvSetupSettings['HttpOnlyCookie'])
	ini_set('session.cookie_httponly', 1);

session_set_cookie_params(0, $mvSetupSettings['MainPath']);

ini_set('session.use_only_cookies', 1);
ini_set('session.use_trans_sid', 0);