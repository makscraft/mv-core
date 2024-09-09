<?php
use Composer\Script\Event;

/**
 * Class for installation of the framework via composer CLI.
 */
class Installation
{
    /**
     * Current instance of the class.
     */
    protected static $instance;

    /**
     * Creates the current installation instance for directory.
     */
    static public function instance(array $params = [])
    {
        if(static :: $instance === null)
            static :: $instance = [
                'directory' => realpath($params['directory'] ?? __DIR__.'/../../../..'),
                'package' => $params['package'] ?? '',
                'boot' => false
            ];

        return static :: $instance;
    }

    /**
     * Starts MV framework environment.
     */
    static public function boot()
    {
        if(static :: $instance['boot'] === true)
            return Registry :: instance() -> loadEnvironmentSettings();

        $registry = Registry :: instance();

        require_once static :: $instance['directory'].'/config/setup.php';
        require_once static :: $instance['directory'].'/config/settings.php';
        require_once static :: $instance['directory'].'/config/models.php';
        require_once static :: $instance['directory'].'/config/plugins.php';

        $mvSetupSettings['BootFromCLI'] = true;
        $mvSetupSettings['IncludePath'] = str_replace('\\', '/', static :: $instance['directory']).'/';
        $mvSetupSettings['CorePath'] = __DIR__.DIRECTORY_SEPARATOR;
        $mvSetupSettings['Models'] = $mvActiveModels;
        $mvSetupSettings['Plugins'] = $mvActivePlugins;

        Registry :: generateSettings($mvSetupSettings);

        $registry -> loadSettings($mvMainSettings);
        $registry -> loadSettings($mvSetupSettings);
        $registry -> loadEnvironmentSettings();
        $registry -> lowerCaseConfigNames();
        $registry -> createClassesAliases();

        static :: $instance['boot'] = true;
    }

    //Heplers

    /**
     * Displays CLI prompt message and retutns the answer.
     * @return string answer from user
     */
    static public function typePrompt(string $message)
    {
        echo PHP_EOL.$message.': ';

        $stdin = fopen('php://stdin', 'r');
        $answer = trim(fgets($stdin));
        fclose($stdin);
        
        return $answer;
    }

    /**
     * Displays promopt message and checks the result value, which must be equal to one of the choices.
     * @return mixed selected value or null if no choices
     */
    static public function typePromptWithCoices(string $message, array $choices): mixed
    {
        if(count($choices) === 0)
        {
            self :: displayErrorMessage('You must provide choices array for message "'.$message.'"');
            return null;
        }

        do{
            $answer = self :: typePrompt($message);

            if(in_array($answer, $choices))
                return $answer;
        }
        while(true);
    }

    /**
     * Displays CLI error message with red background.
     */
    static public function displayErrorMessage(string $message)
    {
        echo "\033[41m\r\n\r\n ".$message." \r\n \033[0m".PHP_EOL.PHP_EOL;
    }

    /**
     * Displays CLI green colored success message (without background).
     */
    static public function displaySuccessMessage(string $message)
    {
        echo "\033[92m".$message." \033[0m".PHP_EOL;
    }

    /**
     * Displays CLI success message with green background.
     */
    static public function displayDoneMessage(string $message)
    {
        echo "\033[42m\r\n\r\n \033[30m".$message." \r\n \033[0m".PHP_EOL.PHP_EOL;
    }

    /**
     * Opens .env file and puts param into it.
     */
    static public function setEnvFileParameter(string $key, string $value)
    {
        $env_file = static :: $instance['directory'].'/.env';
        $env = file_get_contents($env_file);

        $env = preg_replace('/'.$key.'=[\/\w]*/ui', $key.'='.trim($value), $env);
        file_put_contents($env_file, $env);
    }

    /**
     * Removes directory recursively.
     */
    static public function removeDirectory(string $directory)
    {
        if($directory == '/' || strpos($directory, '..') !== false)
            return;

        if(is_dir($directory))
        { 
            $objects = scandir($directory);

            foreach($objects as $object)
            {
                if($object != '.' && $object != '..')
                { 
                    if(is_dir($directory.DIRECTORY_SEPARATOR.$object) && !is_link($directory.DIRECTORY_SEPARATOR.$object))
                        self :: removeDirectory($directory.DIRECTORY_SEPARATOR.$object);
                    else
                        unlink($directory. DIRECTORY_SEPARATOR.$object); 
                } 
            }
          
            rmdir($directory);
        } 
    }

    /**
     * Copies the directory recursively.
     */
    static public function copyDirectory(string $from, string $to)
    {
        if($from == '/' || strpos($from, '..') !== false || strpos($to, '..') !== false)
            return;

        Filemanager :: createDirectory($to);
        
        $objects = scandir($from);

        foreach($objects as $object)
        {
            if($object != '.' && $object != '..')
            {
                $one_from = $from.DIRECTORY_SEPARATOR.$object;
                $one_to = $to.DIRECTORY_SEPARATOR.$object;

                if(is_dir($one_from))
                    self :: copyDirectory($one_from, $one_to);
                else
                    copy($one_from, $one_to);
            }
        }
    }    

    //Installation process

    /**
     * Post "composer dump-autoload" event.
     */
    static public function postAutoloadDump(Event $event)
    {
        
    }

    /**
     * Post "composer install / update" event.
     */
    static public function postUpdate(Event $event)
    {
        static :: moveAdminPanelDirectory();
        Cache :: emptyCacheDirectory();
        self :: displaySuccessMessage(' - Cache directory has been cleared.');
    }

    /**
     * Final configuration at the end of "composer create-project" command.
     */
    static public function finish()
    {
        self :: instance();
        self :: configureDirectory();
        self :: generateSecurityToken();

        self :: changeAutoloaderString('/index.php');
        self :: displaySuccessMessage(' - index.php file has been configurated.');
        self :: moveAdminPanelDirectory();
        self :: checkAndSetDirectoriesPermissions();

        if(true === self :: configureDatabase())
            self :: displayFinalInstallationMessage();
    }

    /**
     * Adds 'vendor/autoload.php' to file.
     */
    static public function changeAutoloaderString(string $file)
    {
        $file = realpath(static :: $instance['directory'].$file);

        if(!file_exists($file))
            return;

        $code = file_get_contents($file);
        $code = str_replace('config/autoload.php', 'vendor/autoload.php', $code);

        file_put_contents($file, $code);
    }

    /**
     * Configures project subfolder (if the application is located not at the domain root).
     * Modifies .env and .htaccess files.
     */
    static public function configureDirectory()
    {
        $directory = '';

        do{
            $folder = self :: typePrompt('Please type the name of project subdirectory or press Enter to skip [default is /]');
            $folder = trim($folder);
            $folder = $folder === '' ? '/' : $folder;
            $error = '';
            
            if(!preg_match('/^[\/\w\-]+$/', $folder))
                $error = 'Error! You need to enter the project subdirectory name like /my/application/ or myapp (or simply /).';

            if(!$error && !preg_match('/^\//', $folder))
                $folder = '/'.$folder;
    
            if(!$error && !preg_match('/\/$/', $folder))
                $folder = $folder.'/';

            $back = static :: $instance['directory'].preg_replace('/[\w\-]+/', '..', $folder);
            $back = realpath($back);
            
            if(!$error)
            {
                if(!is_dir(realpath($back.$folder)))
                    $error = 'Error! Project directory does not exist: ';
                else if(realpath($back.$folder) !== realpath(static :: $instance['directory']))
                    $error = 'Error! Not suitable project subdirectory: ';

                if($error)
                {
                    if(strtoupper(substr(PHP_OS, 0, 3)) === 'WIN')
                        $folder = str_replace('/', '\\', $folder);

                    $error .= $back.$folder;
                }
            }

            if($error)
                self :: displayErrorMessage($error);
            else
            {
                $directory = $folder;
                break;
            }
        }
        while(true);

        if($directory !== '' && $directory !== '/')
        {
            $htaccess_file = static :: $instance['directory'].'/.htaccess';
            $htaccess = file_get_contents($htaccess_file);
            $htaccess = preg_replace('/RewriteBase\s+\/[\/\w]*/', 'RewriteBase '.$directory, $htaccess);
            file_put_contents($htaccess_file, $htaccess);

            self :: displaySuccessMessage(' - .htaccess file has been configurated.');
        }

        self :: setEnvFileParameter('APP_FOLDER', $directory);
        self :: displaySuccessMessage(' - .env file has been configurated.');
    }

    /**
     * Generates secret token for application in .env file.
     */
    static public function generateSecurityToken()
    {
        $value = Service :: strongRandomString(40);
        self :: setEnvFileParameter('APP_TOKEN', $value);
        self :: displaySuccessMessage(' - Security token has been generated.');   
    }

    /**
     * Creates a copy of admin panel at the root directory of the application.
     * Removes old directory.
     * Checks actual admin directory name in config/setup.php
     */
    static public function moveAdminPanelDirectory()
    {
        self :: instance();
        self :: boot();
        
        $from = realpath(__DIR__.'/../adminpanel');
        $to = Registry :: get('IncludePath').Registry :: get('AdminFolder');

        if(!is_dir($from))
            return;

        self :: removeDirectory($to);
        self :: copyDirectory($from, $to);
        self :: displaySuccessMessage(' - Admin panel folder has been moved.');
    }

    /**
     * Sets directories permissions depending on OS.
     */
    static public function checkAndSetDirectoriesPermissions()
    {
        $directories = ['log', 'userfiles', 'userfiles/tmp', 'userfiles/database/sqlite'];
        $root = Registry :: get('IncludePath');
        $all = [];
        
        foreach($directories as $directory)
        {
            if(!is_dir($root.$directory))
                continue;

            $all[] = $root.$directory;

            if($directory === 'userfiles' || $directory === 'userfiles/tmp')
                foreach(scandir($root.$directory) as $subdir)
                    if($subdir !== '.' && $subdir !== '..' && is_dir($root.$directory.'/'.$subdir))
                        $all[] = $root.$directory.'/'.$subdir;
        }

        $all = array_unique($all);

        if(PHP_OS_FAMILY === 'Darwin')
        {
            foreach($all as $directory)            
                chmod($directory, 0777);

            $sqlite = $root.'userfiles/database/sqlite/database.sqlite';
            
            if(is_file($sqlite))
                chmod($sqlite, 0777);

            self :: displaySuccessMessage(' - MacOS directories and files permissions have been set.');
        }        
    }

    /**
     * Final message after successfull installation.
     */
    static public function displayFinalInstallationMessage()
    {
        Installation :: instance(['directory' => __DIR__.'/..']);
        $env = parse_ini_file(static :: $instance['directory'].DIRECTORY_SEPARATOR.'.env');

        $message = "Installation complete, now you can open your MV application in browser.".PHP_EOL;
        $message .= " MV start page http://yourdomain.com".preg_replace('/\/$/', '', $env['APP_FOLDER']).PHP_EOL;
        $message .= " Admin panel location http://yourdomain.com".$env['APP_FOLDER']."adminpanel";

        self :: displayDoneMessage($message);
    }    

    //Database confuguration

    /**
     * Runs PDO object according to db settings.
     */
    static public function runPdo(): ?PDO
    {
        $env_file = static :: $instance['directory'].'/.env';
        $env = parse_ini_file($env_file);

        if($env['DATABASE_ENGINE'] !== 'mysql' && $env['DATABASE_ENGINE'] !== 'sqlite')
        {
            self :: displayErrorMessage('Undefined database engine in parameter DATABASE_ENGINE in .env file.');
        }

        if($env['DATABASE_ENGINE'] == 'mysql')
        {
            $pdo = new PDO("mysql:host=".$env['DATABASE_HOST'].";dbname=".$env['DATABASE_NAME'], 
                                                                          $env['DATABASE_USER'], 
                                                                          $env['DATABASE_PASSWORD'], [
                                    PDO :: MYSQL_ATTR_INIT_COMMAND => "SET NAMES \"UTF8\""
                            ]);

            $pdo -> setAttribute(PDO :: MYSQL_ATTR_USE_BUFFERED_QUERY, true);
        }
        else if($env['DATABASE_ENGINE'] == 'sqlite')
        {
            $path = '/userfiles/database/sqlite/database.sqlite';
            $file = static :: $instance['directory'].$path;
            $file = realpath($file);

            if(!is_file($file))
            {
                self :: displayErrorMessage('SQLite database file no found: ~'.$path);
                return null;
            }
            else if(!is_writable(dirname($file)))
            {
                self :: displayErrorMessage('Please make directory with sqlite file writable: '.dirname($file));
                return null;
            }

            $pdo = new PDO("sqlite:".$file);
        }

        return $pdo;
    }

    /**
     * Database initial configuration.
     */
    static public function configureDatabase()
    {
        $message = 'Please select database driver [mysql / sqlite]';
        $driver = self :: typePromptWithCoices($message, ['mysql', 'sqlite']);
        $db_host = PHP_OS_FAMILY === 'Darwin' ? '127.0.0.1' : 'localhost';

        self :: setEnvFileParameter('DATABASE_ENGINE', $driver);
        self :: setEnvFileParameter('DATABASE_HOST', $driver === 'mysql' ? $db_host : '');

        if($driver === 'sqlite')
        {
            self :: configureDatabaseSQLite();
            return true;
        }
        else if($driver === 'mysql')
            self :: displaySuccessMessage(' - Now please fill database settings for MySQL in .env file and run "composer database" in your project directory.');
    }

    /**
     * Mysql initial configuration.
     */
    static public function configureDatabaseMysql()
    {
        $env = parse_ini_file(static :: $instance['directory'].'/.env');
        $keys = ['DATABASE_HOST', 'DATABASE_USER', 'DATABASE_NAME'];

        foreach($keys as $key)
            if(!isset($env[$key]) || trim($env[$key]) === '')
            {
                self :: displayErrorMessage('Please fill "'.$key.'" parameter in .env file.');
                return;
            }

        $pdo = self :: runPdo();
        $query = $pdo -> prepare('SHOW TABLES');
        $query -> execute();
        $tables = $query -> fetchAll(PDO :: FETCH_COLUMN);
    
        if(is_array($tables) && in_array('versions', $tables))
            self :: displaySuccessMessage(' - MySQL initial dump has been already imported before.');
        else
        {        
            $dump_file = static :: $instance['directory'].'/userfiles/database/mysql-dump.sql';

            if(true === self :: loadMysqlDump($dump_file, $pdo))
                self :: displaySuccessMessage(' - MySQL initial dump has been imported.');
        }

        self :: setRootUserLogin($pdo);

        self :: displayDoneMessage('MySQL database has been successfully configurated.');

        if(static :: $instance['package'] === '')
            self :: displayFinalInstallationMessage();
    }

    /**
     * Sqlite initial configuration.
     */
    static public function configureDatabaseSQLite()
    {
        self :: setRootUserLogin(self :: runPdo());
        self :: displayDoneMessage('SQLite database has been successfully configurated.');
    }

    /**
     * Imports mysql dump into db.
     */
    static public function loadMysqlDump(string $dump_file, PDO $pdo)
    {
        $sql = '';
        $lines = file($dump_file);
        
        foreach($lines as $line)
        {
            if(substr($line, 0, 2) == '--' || $line == '')
                continue;
            
            $sql .= $line;
            
            if(substr(trim($line), -1, 1) == ';')
            {
                try
                {
                    $pdo -> query($sql);
                } 
                catch(Exception $error)
                {
                    print_r($error -> getMessage());
                    exit();
                }
                
                $sql = '';
            }
        }

        return true;
    }

    //Root user login data

    /**
     * Updates root user login and password in databese.
     */
    static public function setRootUserLogin(PDO $pdo)
    {
        $query = $pdo -> prepare("SELECT COUNT(*) FROM `users`");
        $query -> execute();
        $total = $query -> fetch(PDO::FETCH_NUM)[0];

        $query = $pdo -> prepare("SELECT * FROM `users` WHERE `id`='1'");
        $query -> execute();
        $row = $query -> fetch(PDO::FETCH_ASSOC);

        if($total > 1)
        {
            self :: displaySuccessMessage(' - Database has been already configurated.');
            return;
        }

        $login = $password = '';

        do{
            $login = self :: typePrompt('Please setup your login for MV admin panel');

            if(strlen($login) > 1)
                break;

        }
        while(true);

        do{
            $password = self :: typePrompt('Please setup your password for MV admin panel (min 6 characters)');

            if(strlen($password) >= 6)
                break;

        }
        while(true);

        static :: $instance['login'] = $login;
        static :: $instance['password'] = $password;

        $password = password_hash($password, PASSWORD_DEFAULT, ['cost' => 10]);
        $date = date('Y-m-d H:i:s');

        if(is_array($row) && isset($row['id']) && $row['id'] == 1 && $total == 1)
        {
            $query = $pdo -> prepare(
                "UPDATE `users`
                 SET `login`=".$pdo -> quote($login).", `password`=".$pdo -> quote($password).",
                 `date_registered`='".$date."' 
                 WHERE `id`='1'"
            );
        }
        else if($total == 0)
        {                        
            $query = $pdo -> prepare(
                "INSERT INTO `users`(`name`,`login`,`password`,`date_registered`,`active`)
                 VALUES ('Root', ".$pdo -> quote($login).", ".$pdo -> quote($password).", '".$date."', '1')"
            );
        }

        if($query -> execute())
            self :: displaySuccessMessage(' - Root user of admin panel has been successfully created.');
    }

    /**
     * Inserts initial records into database.
     */
    static public function insertInitionDatabaseContent(string $region)
    {
        self :: boot();

        $region = $region === 'us' ? 'en' : $region;
        $package = static :: $instance['directory'].'/customs/regions/'.$region;

        $file = $package.'/package-'.$region.'.php';
        $data = is_file($file) ? include $file : null;
        
        if(is_array($data))
            if(0 < self :: importInitialDatabaseData($data['database']))
                self :: displaySuccessMessage(' - Database data has been imported.');

        return $data;
    }

    /**
     * Checks and runs all migrations if they exist (from CLI).
     */
    static public function findAndExecuteAllAvailableMigartions()
    {
        static :: boot();

        $migrations = new Migrations(true);
        $migrations -> scanModels();
        $available = $migrations -> getMigrationsQuantity();
        
        if($available)
        {
            self :: displaySuccessMessage(' - Found available migrations: '.$available);

            $migrations -> runMigrations('all');
            self :: displaySuccessMessage(' - Migrations have been executed.');
        }
    }

    //Commands

    /**
     * Database configuration via CLI composer command.
     */
    static public function commandConfigureDatabase(Event $event)
    {
        self :: instance();
        $env = parse_ini_file(static :: $instance['directory'].'/.env');

        if($env['DATABASE_ENGINE'] === 'mysql')
        {
            $arguments = $event -> getArguments();

            if(isset($arguments[0]) && $arguments[0] == 'dev')
            {
                self :: setEnvFileParameter('DATABASE_HOST', 'localhost');
                self :: setEnvFileParameter('DATABASE_USER', 'root');
                self :: setEnvFileParameter('DATABASE_PASSWORD', '');
                self :: setEnvFileParameter('DATABASE_NAME', $arguments[1] ?? 'development');
                
                $env = parse_ini_file(static :: $instance['directory'].'/.env');
            }
        }
        
        if($env['DATABASE_ENGINE'] === 'mysql')
            self :: configureDatabaseMysql();
        else if($env['DATABASE_ENGINE'] === 'sqlite')
            self :: configureDatabaseSQLite();
        else
            self :: displayErrorMessage('Undefined database "DATABASE_ENGINE='.$env['DATABASE_ENGINE'].'" in .env file');
    }

    /**
     * Check and runs database migrations via CLI composer command.
     */
    static public function commandMigrations(Event $event)
    {
        self :: instance();
        self :: boot();

        $tables = Database :: instance() -> getTables();

        if(!in_array('versions', $tables) || !in_array('users', $tables))
        {
            $message = "Unable to run migrations. Initial database dump was not imported.".PHP_EOL;
            $message .= " Probably you need to execute \"composer database\" before.";

            self :: displayErrorMessage($message);
            return;
        }

        $migrations = new Migrations(true);
        $migrations -> scanModels();
        $available = $migrations -> getMigrationsQuantity();

        if($available == 0)
        {
            self :: displaySuccessMessage(' - No new migrations available.');
            return;
        }

        echo PHP_EOL;
        self :: displaySuccessMessage(' - Found available migrations ('.$available.'):');
        echo implode("\n", $migrations -> getMigrationsShortList()).PHP_EOL;
        
        $answer = self :: typePrompt('Do you want to run the migrations now? [yes]');

        if($answer == '' || $answer == 'yes' || $answer == 'y')
        {
            $migrations -> runMigrations('all');
            self :: displayDoneMessage('Migrations have been executed. Your database now is up to date.');
        }
    }

    /**
     * Cleans cache folder and deletes old files from userfiles/ directory. 
     */
    static public function commandCleanup(Event $event)
    {
        self :: instance();
        self :: boot();
        
        $userfiles = Registry :: get('FilesPath');

        Cache :: emptyCacheDirectory();        
        self :: displaySuccessMessage(' - Env and media cache have been cleared.');

        $folders = ['tmp/', 'tmp/admin/', 'tmp/redactor/', 'tmp/filemanager/'];

        (new Filemanager()) -> cleanTmpFiles();

        foreach($folders as $folder)
            Filemanager :: deleteOldFiles($userfiles.$folder);

        self :: displaySuccessMessage(' - Temporary files have been removed.');

        Filemanager :: setCleanupLimit(100000);
        Filemanager :: makeModelsFilesCleanUp();
        self :: displaySuccessMessage(' - Models files have been optimized.');

        Cache :: cleanAll();
        self :: displaySuccessMessage(' - Database cache has been cleared.');
    }

    /**
     * Sets the application regional localization (env, initial models, views, and database pages)
     */
    static public function commandRegion(Event $event)
    {
        self :: instance();
        self :: boot();

        $arguments = $event -> getArguments();
        $region = strtolower(trim($arguments[0] ?? ''));
        $supported = Registry :: get('SupportedRegions');

        if($region === '')
        {
            $message = 'Region value has not been passed. Pass it like "composer region -- en"';
            $message .= PHP_EOL.' Supported regions are: '.implode(', ', $supported);
            self :: displayErrorMessage($message);

            return;
        }

        if(!in_array($region, Registry :: get('SupportedRegions')))
        {
            $message = 'Undefined region passed "'.$region.'"';
            $message .= PHP_EOL.' Supported regions are: '.implode(', ', $supported);
            self :: displayErrorMessage($message);

            return;
        }

        if(static :: $instance['package'] !== '')
            return $region;

        $env = parse_ini_file(static :: $instance['directory'].'/.env');
        $env_region = $env['APP_REGION'] ?? '';
        $versions = Database :: instance() -> getCount('versions');
        $logs = Database :: instance() -> getCount('log');

        if($env_region !== '' || $versions > 0 || $logs > 0)
        {
            $message = "Attention! Changing of the region will cause overwriting files of 3 base models, views ";
            $message .= "and content of table 'pages' in database.";
            
            self :: displayErrorMessage($message);

            $message = "Do you want to proceed? [yes / no]";

            $answer = self :: typePromptWithCoices($message, ['yes', 'y', 'no', 'n', '']);
            
            if($answer !== 'yes' && $answer !== 'y')
                return;
        }

        self :: setEnvFileParameter('APP_REGION', $region);
        self :: displaySuccessMessage(' - .env file has been configurated.');

        $region_initial = $region;
        $region = $region === 'us' ? 'en' : $region;
        $package = static :: $instance['directory'].'/customs/regions/'.$region;

        if(is_dir($package))
        {
            if(is_dir($package.'/models') && count(scandir($package.'/models')) > 2)
            {
                self :: copyDirectory($package.'/models', static :: $instance['directory'].'/models');
                self :: displaySuccessMessage(' - Models files have been copied.');
            }

            if(is_dir($package.'/views') && count(scandir($package.'/views')) > 2)
            {
                self :: copyDirectory($package.'/views', static :: $instance['directory'].'/views');
                self :: displaySuccessMessage(' - Views files have been copied.');
            }

            $file = $package.'/package-'.$region.'.php';
            $data = is_file($file) ? include $file : null;
            
            if(is_array($data))
                if(0 < self :: importInitialDatabaseData($data['database']))
                    self :: displaySuccessMessage(' - Database data has been imported.');
        }

        $message = 'Region settings from the "'.$region_initial.'" package have been installed.';

        if(isset($data['hello']) && $data['hello'] !== '')
            $message .= PHP_EOL.' '.$data['hello'];
    
        self :: displayDoneMessage($message);
    }

    /**
     * Imports data into database from initial configuration files.
     * @param array $data keys - models classnames, values - arrays with data for db records
     * @return int total number of inserted rows
     */
    static public function importInitialDatabaseData(array $data): int
    {
        $imported = 0;

        if(count($data) === 0)
            return $imported;

        foreach($data as $model_name => $items)
        {
            if(Registry :: checkModel($model_name) !== true)
                continue;

            $model = new $model_name;
            $model -> clearTable();

            foreach($items as $item)
            {
                $record = $model -> getEmptyRecord();
                $record -> setValues($item) -> create();
                $imported ++;
            }                    
        }

        return $imported;
    }

    /**
     * General common command to extend functionality.
     */
    static public function commandService(Event $event)
    {
        $arguments = $event -> getArguments();

        //for the future ...
    }
}