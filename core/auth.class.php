<?php
/**
 * 
 */
class Auth
{
    private static $current = null;

    private static $model = null;

    private static $containers = [];

    private const DEFAULT_SETTINGS = [
        'login_field' => '',
        'password_field' => '',
        'token_field' => '',
        'active_field' => '',
        'last_login_field' => '',
        'remember_me' => false,
        'remember_me_lifetime' => 30,
        'watch_ip' => false,
        'watch_browser' => false,
        'allowed_urls' => []
    ];

    /* Initial settings */

    static public function useModel(string $model_class)
    {
        $model = self::analyzeModel($model_class);
        $settings = self::generateContainerSettings($model);

        self::$current = get_class($model);
        self::$model = $model;

        //Session::start($settings['session_key']);
    }

    // static protected function generateContainerKey(object $model)
    // {
    //     $class_name = $model -> getModelClass();

    //     return 'auth_'.$class_name.'_'.md5($class_name.Registry::get('SecretCode'));
    // }

    static protected function analyzeModel(string $model_class)
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

    static protected function generateContainerSettings(object $model)
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

    /* Login and logout */

    static public function login(mixed $login, string $password = '')
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

        $record = self::$model -> find([$login_field => $login]);

        if(is_object($record) && ($force_login || Service::checkHash($password, $record -> $password_field)))
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

            return $record;
        }
        
        return null;
    }

    static public function logout()
    {
        if(self::$current !== null)
            Session::destroy(self::$containers[self::$current]['session_key']);
    }

    static public function check()
    {
        if(is_null(self::$current))
            return null;

        $session_key = self::$containers[self::$current]['session_key'];

        if(!Session::exists($session_key))
            return null;
        
        Session::start($session_key);

        if(!Session::has('id', 'password', 'token'))
            return null;
        
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

                return $record;
            }

            Session::destroy(self::$containers[self::$current]['session_key']);
        }

        return null;
    }

    /* Remember me */

    static public function remember()
    {
        $key = Service::strongRandomString(50);
    }

    static public function cancelRemember()
    {

    }

    static public function loginWithRememberCookie()
    {

    }

    /* Password recovery */

    static public function generatePasswordRecoveryLink(Record $record)
    {

    }

    static public function checkPasswordRecoveryLink(Router $router)
    {

    }

    static public function recoverPassword(Record $record, string $password)
    {
        
    }

    /* Helpers */

    static public function generateRecordSessionToken(Record $record): string
	{
		$token = $record -> id.Registry::get('SecretCode');
		$token .= Debug::browser().session_id();
		
		return md5($token);
	}
}