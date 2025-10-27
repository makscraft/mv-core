<?php
/**
 * Handles user authorization for the MV framework, including session management and "remember me" functionality.
 * Supports multiple account models simultaneously, each with its own configuration options.
 * Stores session and cookie data in separate containers for enhanced security.
 * 
 * Always begin by calling the Auth::useModel(YourModel::class) method to initialize the desired account model.
 */
class Auth
{
    /**
     * Current model class name.
     * @var string
     */
    protected static $current = null;

    /**
     * Current model object.
     * @var object
     */
    protected static $model = null;

    /**
     * List of separate auth containers for each model.
     * @var array
     */
    protected static $containers = [];

    /**
     * List of currently logged in users (last one from each model).
     * @var array
     */
    protected static $users = [];

    /**
     * List of default authorization settings for each model (can be overriden).
     * @const array
     */
    protected const DEFAULT_SETTINGS = [
        'login_field' => '',
        'password_field' => '',
        'token_field' => '',
        'active_field' => '',
        'last_login_field' => '',
        'remember_me' => false,
        'remember_me_lifetime' => 30,
        'recover_password_lifetime' => 3600,
        'watch_ip' => false,
        'watch_browser' => false,
        'allowed_urls' => []
    ];

    /* Initial settings */

    /**
     * Initial required method to start working with Auth.
     * Provide model class with filled $auth_settings property like Auth::useModel(YourModel::class)
     */
    static public function useModel(string $model_class)
    {
        if(self::$current == $model_class && is_object(self::$model))
            return;
		
        $model = self::analyzeModel($model_class);
        self::generateContainerSettings($model);

        self::$current = get_class($model);
        self::$model = $model;
    }

    /**
     * Internal method to analyze $auth_settings property of current model.
     * @return object analyzed model or fires an error
     */
    static protected function analyzeModel(string $model_class): object
    {
        if(!class_exists($model_class) || get_parent_class($model_class) !== 'Model')
            Debug::displayError('Undefined or not suitable class name passed for Authorization: '.$model_class);
        
        $model = new $model_class;
        $settings = $model -> getAuthorizationSessings();

        if(!is_array($settings) || !count($settings))
            Debug::displayError('You must define and fill the $auth_settings property for Authorization in model '.$model_class.'.');

        if(!array_key_exists('login_field', $settings) || !$settings['login_field'])
            Debug::displayError('You must define $auth_settings[\'login_field\'] property for Authorization in model '.$model_class.'.');

        $allowed = array_keys(self::DEFAULT_SETTINGS);
        $fields = ['login_field', 'password_field', 'token_field', 'active_field', 'last_login_field'];

        foreach($settings as $key => $value)
        {
            if(!in_array($key, $allowed))
            {
                $message = 'Undefined authorization setting \''.$key.'\' in model '.$model_class.'. ';
                $message .= 'Allowed settings are: '.implode(', ', $allowed).'.'; 
                Debug::displayError($message);
            }

            if(in_array($key, $fields) && $model -> getElement($value) === null)
            {
                $message = 'Undefined authorization field \''.$key.'\' => \''.$value.'\' in model '.$model_class.'.';
                Debug::displayError($message);
            }
        }

        return $model;
    }

    /**
     * Combines settings from $auth_settings property with default Auth settings from DEFAULT_SETTINGS.
     * @return array properties
     */
    static protected function generateContainerSettings(object $model): array
    {
        $settings = $model -> getAuthorizationSessings();
        $defaults = self::DEFAULT_SETTINGS;
        $bools = ['remember_me', 'watch_ip', 'watch_browser'];

        foreach($settings as $key => $value)
        {
            if($key === 'remember_me_lifetime' && !is_int($value))
                continue;

            if($key === 'allowed_urls' && !is_array($value))
                continue;

            if(in_array($key, $bools) && !is_bool($value))
                continue;

            $defaults[$key] = $value;
        }

        if(!$defaults['remember_me'])
            $defaults['remember_me_lifetime'] = null;

        $defaults['allowed_urls'] = count($defaults['allowed_urls']) ? $defaults['allowed_urls'] : null;
        $defaults['session_key'] = 'auth_'.mb_strtolower(get_class($model));

        self::$containers[get_class($model)] = $defaults;

        return $defaults;
    }

    /**
     * Current state of Auth class with current model settings and all authorized users.
     * @return array data of Auth
     */
    static public function getState(): array
    {
        return [
            'model' => self::$current,
            'settings' => self::$containers[self::$current] ?? [],
            'users' => self::$users
        ];
    }

    /**
     * Adds one authorized user into state data.
     */
    static protected function addAuthorizedUser(Record $user, string $source = '')
    {
        if(is_null(self::$current) || $user -> getModelClass() !== self::$current)
            return;

        $login_field = self::$containers[self::$current]['login_field'];

        self::$users[self::$current] = [
            'id' => $user -> id,
            'login' => $user -> $login_field,
            'source' => $source
        ];
    }

    /**
     * Removes one authorized user from state data? usually after logout.
     */
    static protected function removeAuthorizedUser(string $model)
    {
        if(!is_null(self::$current) && isset(self::$users[self::$current]))
            unset(self::$users[self::$current]);
    }

    /* Login and logout */

    /**
     * Logins user of current model, according to settings, runs individual Session container.
     * @param mixed $login accepts string | Form | Record parameter
     * @param string $password required string if $login is string
     * @param string $source internal marker for getState()
     * 
     * @return ?Record object of user or null if failed
     */
    static public function login(mixed $login, string $password = '', string $source = 'login'): ?Record
    {
        if(is_null(self::$current) || !$login)
            return null;

        $login_field = self::$containers[self::$current]['login_field'];
        $password_field = self::$containers[self::$current]['password_field'];
        $force_login = false;
        
        if(is_object($login) && ($login instanceof Form || $login instanceof Record))
        {
            $object = $login;
            $login = (string) $object -> $login_field;
            $password = (string) $object -> $password_field;

            if($object instanceof Record)
            {
                if($object -> getModelClass() !== self::$current)
                    return null;

                $force_login = true;
            }
            else if($password === '')
                return null;

            if($login === '')
                return null;
        }
        else if(!is_string($login) || $password === '')
            return null;

        if(null === $record = self::$model -> find([$login_field => $login]))
            return null;

        $element = self::$model -> getElement($password_field);
        $compare_hash = $element -> comparePasswordHash($password, $record -> $password_field);

        if($force_login || $compare_hash)
        {
            if('' !== $active_field = self::$containers[self::$current]['active_field'])
                if(!$record -> $active_field)
                    return null;

            Session::start(self::$containers[self::$current]['session_key']);
            Session::set('id', $record -> id);
            Session::set('password', md5($record -> $password_field));
            Session::set('token', self::generateRecordSessionToken($record));

            if('' !== $last_login_field = self::$containers[self::$current]['last_login_field'])
                $record -> setValue($last_login_field, I18n::getCurrentDateTime()) -> update();

            if('' !== $token_field = self::$containers[self::$current]['token_field'])
                if(!$record -> $token_field)
                    $record -> setValue($token_field, Service::strongRandomString(50)) -> update();

            self::addAuthorizedUser($record, $source);

            return $record;
        }
        
        return null;
    }

    /**
     * Drops current user's authorization including rememeber me cookie.
     */
    static public function logout()
    {
        if(is_null(self::$current))
            return;

        Session::destroy(self::$containers[self::$current]['session_key']);

        self::forget();
        self::removeAuthorizedUser(self::$current);
    }

    /**
     * Check if current model has authorized user.
     * @return ?Record object of user or null if failed
     */
    static public function check(): ?Record
    {
        if(is_null(self::$current))
            return null;

        $session_key = self::$containers[self::$current]['session_key'];

        if(!Session::exists($session_key))
            return null;
        
        Session::start($session_key);

        if(!Session::has('id', 'password', 'token'))
            return null;

        if(!self::checkIpAndBrowser())
            return self::logout();
        
        $record = self::$model -> find(Session::get('id'));
        $password_field = self::$containers[self::$current]['password_field'];

        if(is_object($record) && md5($record -> $password_field) === Session::get('password'))
        {
            if(self::generateRecordSessionToken($record) === Session::get('token'))
            {
                if('' !== $active_field = self::$containers[self::$current]['active_field'])
                    if(!$record -> $active_field)
                    {
                        Session::destroy($session_key);
                        return null;
                    }
                
                self::addAuthorizedUser($record, 'session');
                
                return $record;
            }

            return self::logout();
        }

        return null;
    }

    /**
     * Check ip and browser condition if exist in auth settings of current model.
     */
    static protected function checkIpAndBrowser(): bool
    {
        if(self::$containers[self::$current]['watch_ip'])
            if(Session::getParameter('ip_hash') !== md5($_SERVER['REMOTE_ADDR']))
                return false;
        
        if(self::$containers[self::$current]['watch_browser'])
            if(Session::getParameter('browser_hash') !== md5($_SERVER['HTTP_USER_AGENT']))
               return false;

        return true;
    }

    /* Remember me */

    /**
     * Generates cookie key name for remember me.
     */
    static protected function generateCookieStorageKey(): string
    {
        $key = self::$current.Registry::get('SecretCode').Debug::browser();
        $key .= self::$containers[self::$current]['login_field'];

        return 'remember_'.substr(md5($key), 5, 10);
    }

    /**
     * Sets remember me cookie for record of current model.
     */
    static public function remember(Record $record): bool
    {
        if(is_null(self::$current) || !self::$containers[self::$current]['remember_me'])
            return false;

        $value = self::generateRememberMeCookieToken($record);
        $time = time() + ((self::$containers[self::$current]['remember_me_lifetime'] ?? 30) * 3600 * 24);

        Http::setCookie(self::generateCookieStorageKey(), $value, ['expires' => $time]);

        return true;
    }

    /**
     * Removes remember me cookie for current model and drops autologin.
     */
    static public function forget(): bool
    {
        if(is_null(self::$current))
            return false;

        $key = self::generateCookieStorageKey();

        if(Http::getCookie($key))
        {
            Http::setCookie($key, '', ['expires' => time() - 60]);
            return true;
        }

        return false;
    }

    /**
     * Logins user of current model with remember me cookie if such one exists.
     * @return ?Record object of user or null if failed
     */
    static public function loginWithRememberMeCookie(): ?Record
    {
        if(is_null(self::$current) || isset(self::$users[self::$current]))
            return null;

        if(!self::$containers[self::$current]['remember_me'])
            return null;
        
        $key = self::generateCookieStorageKey();
        $cookie = Http::getCookie($key, '');

        if(null !== $record = self::checkRememberMeCookieToken($cookie))
            return self::login($record, '', 'remember_me');
        
        return null;
    }

    /**
     * Creates remember me cookie value.
     */
    static public function generateRememberMeCookieToken(Record $record): string
    {
        $base = self::getCryptoBase($record);
        $data_second = $base['secret'].$base['record_data'];
        
        $first = Service::mixNumberWithLetters($record -> id + $base['id_offset'], $base['digits'] + mt_rand(30, 50));
        $first = str_replace($base['first_separator'], '', $first);
        $second = preg_replace('/^\$2y\$14\$/', '', Service::makeHash($data_second, 14));
        $third = Service::mixNumberWithLetters($base['browser_digits'], $base['digits'] + mt_rand(120, 150));
        $third = str_replace($base['second_separator'], '', $third);

        return $first.$base['first_separator'].$second.$base['second_separator'].$third;
    }

    /**
     * Checks remember me cookie value and looks for needed user.
     * @return ?Record object of user or null if failed
     */
    static public function checkRememberMeCookieToken(string $token): ?Record
    {
        $token = trim($token);

        if($token === '')
            return null;

        $base = self::getCryptoBase();
        $first_separator = strpos($token, $base['first_separator']);
        $second_separator = strrpos($token, $base['second_separator']);
        $first = substr($token, 0, $first_separator);
        $second = '$2y$14$'.substr($token, $first_separator + 1, $second_separator - $first_separator - 1);
        $third = substr($token, $second_separator + 1);

        $id = (int) preg_replace('/\D/', '', $first) - $base['id_offset'];
        
        if(null === $record = self::checkActiveUserWithIdFromToken($id))
            return null;            
        
        $base = self::getCryptoBase($record);        
        $data_second = $base['secret'].$base['record_data'];
        $browser = preg_replace('/\D/', '', $third);
        
        return ($browser == $base['browser_digits'] && Service::checkHash($data_second, $second)) ? $record : null;
    }

    /* Password recovery */

    /**
     * Creates token value for password recovery URL.
     */
    static public function generatePasswordRecoveryToken(Record $record): string
    {
        $lifetime = time() + self::$containers[self::$current]['recover_password_lifetime'];
        $base = self::getCryptoBase($record);
        
        $first = Service::mixNumberWithLetters($record -> id + $base['id_offset'], $base['digits'] + mt_rand(10, 20), true);
        $first = str_replace($base['first_separator_flat'], '', $first);
        $second = Service::createHash($base['record_data'].$lifetime.$base['secret'].self::$current, 'gost');
        $third = Service::mixNumberWithLetters(($lifetime - $base['filetime_offset']), mt_rand(10, 20), true);
        $third = str_replace($base['second_separator_flat'], '', $third);
        
        return $first.$base['first_separator_flat'].$second.$base['second_separator_flat'].$third;
    }

    /**
     * Checks password recovery token value and looks for needed user.
     * @return ?Record object of user or null if failed
     */
    static public function checkPasswordRecoveryToken(string $token): ?Record
    {
        $token = trim($token);

        if($token === '')
            return null;

        $base = self::getCryptoBase();
        $first_separator = strpos($token, $base['first_separator_flat']);
        $second_separator = strrpos($token, $base['second_separator_flat']);
        $first = substr($token, 0, $first_separator);
        $second = substr($token, $first_separator + 1, $second_separator - $first_separator - 1);
        $third = substr($token, $second_separator + 1);

        $id = (int) preg_replace('/\D/', '', $first) - $base['id_offset'];
        $time = (int) preg_replace('/\D/', '', $third) + $base['filetime_offset'];

        if(time() > $time)
            return null;
        
        if(null === $record = self::checkActiveUserWithIdFromToken($id))
            return null;

        $base = self::getCryptoBase($record);
        $check = Service::createHash($base['record_data'].$time.$base['secret'].self::$current, 'gost');

        return $check === $second ? $record : null;
    }

    /**
     * Performs saving a new password of user.
     */
    static public function recoverPassword(Record $record, string $password): bool
    {
        if(!$password === '')
            return false;
        
        $check = self::$model -> find($record -> id);

        if($check === null || $check -> getModelClass() !== self::$current)
            return false;
        
        $password_field = self::$containers[self::$current]['password_field'];
        $record -> $password_field = $password;
        $record -> save();

        return true;
    }

    /**
     * Creates token value for email confirmation URL.
     */
    static public function generateEmailConfirmationToken(Record $record, int $lifetime_days = 1): string
    {
        if(is_null(self::$current))
            return '';

        if(null === $element = self::$model -> findElementByProperty('type', 'email'))
            return '';

        $email_field = $element -> getName();

        if(!$record -> $email_field)
            return '';

        $lifetime = time() + 3600 * 24 * $lifetime_days;
        $base = self::getCryptoBase();
        $email = $record -> $email_field;
        
        $first = Service::mixNumberWithLetters($record -> id + $base['id_offset'], $base['digits'] + mt_rand(5, 10), true);
        $first = str_replace($base['first_separator_flat'], '', $first);
        $second = Service::createHash($lifetime.$email.$base['secret'].self::$current, 'gost');
        $third = Service::mixNumberWithLetters(($lifetime - $base['filetime_offset']), mt_rand(5, 10), true);
        $third = str_replace($base['second_separator_flat'], '', $third);

        return $first.$base['first_separator_flat'].$second.$base['second_separator_flat'].$third;
    }

    /**
     * Checks email confirmation token, according to current model.
     * @return ?Record object of user with needed email or null if failed
     */
    static public function checkEmailConfirmationToken(string $token): ?Record
    {
        $token = trim($token);

        if($token === '' || is_null(self::$current))
            return null;

        if(null === $element = self::$model -> findElementByProperty('type', 'email'))
            return null;

        $email_field = $element -> getName();
        $base = self::getCryptoBase();
        $first_separator = strpos($token, $base['first_separator_flat']);
        $second_separator = strrpos($token, $base['second_separator_flat']);
        $first = substr($token, 0, $first_separator);
        $second = substr($token, $first_separator + 1, $second_separator - $first_separator - 1);
        $third = substr($token, $second_separator + 1);

        $id = (int) preg_replace('/\D/', '', $first) - $base['id_offset'];
        $lifetime = (int) preg_replace('/\D/', '', $third) + $base['filetime_offset'];
        
        if(time() > $lifetime)
            return null;
        
        if(null === $record = self::checkActiveUserWithIdFromToken($id))
            return null;

        $email = $record -> $email_field;
        $check = Service::createHash($lifetime.$email.$base['secret'].self::$current, 'gost');

        return $check === $second ? $record : null;
    }

    /* Helpers */

    /**
     * Creates token for user's session after login success.
     */
    static public function generateRecordSessionToken(Record $record): string
	{
		$token = $record -> id.Registry::get('SecretCode');
		$token .= Debug::browser().session_id();
		
		return md5($token);
	}

    /**
     * Combines special set of data for creating remember me and recovery tokens.
     */
    static public function getCryptoBase(?Record $record = null): array
    {
        $separators = ['a','b','c','d','e','f'];
        $secret = preg_replace('/\d/', '', Registry::get('SecretCode'));
        $first_separator = substr($secret, 0, 1);
        $second_separator = substr(str_replace($first_separator, '', $secret), 0, 1);

        $login_field = self::$containers[self::$current]['login_field'];
        $password_field = self::$containers[self::$current]['password_field'];
        $token_field = self::$containers[self::$current]['token_field'];

        return [
            'browser' => Debug::browser(),
            'browser_digits' => (int) preg_replace('/\D/', '', md5(Debug::browser().Registry::get('SecretCode'))),
            'secret' => Registry::get('SecretCode'),
            'digits' => $record ? strlen(strval($record -> id)) : 0,
            'id_offset' => preg_replace('/\D/', '', Registry::get('SecretCode')),
            'first_separator' => $first_separator,
            'second_separator' => $second_separator,
            'first_separator_flat' => $separators[ord($first_separator) % 6],
            'second_separator_flat' => $separators[ord($second_separator) % 6],
            'filetime_offset' => filemtime(__FILE__),
            'record_data' => $record ? md5($record -> id.$record -> $login_field.$record -> $password_field.$record -> $token_field) : ''
        ];
    }

    /**
     * Checks if the user of current model exists and active (if required by settings).
     */
    static protected function checkActiveUserWithIdFromToken(int $id): ?Record
    {
        if(null === $record = self::$model -> find($id))
            return null;

        if('' !== $active_field = self::$containers[self::$current]['active_field'])
            if(!$record -> $active_field)
                return null;

        return $record;
    }
}