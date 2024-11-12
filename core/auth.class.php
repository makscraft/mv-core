<?php
/**
 * 
 */
class Auth
{
    private static $current = null;

    private static $containers = [];

    private const DEFAULT_SETTINGS = [
        //'model_class' => '',
        'login_field' => '',
        'password_field' => '',
        'token_field' => '',
        'date_registration_field' => '',
        'date_last_login_field' => '',
        'remember_me' => false,
        'remember_me_lifetime' => 30,
        'watch_ip' => false,
        'watch_browser' => false,
        //'store_key' => '',
        'allowed_urls' => []
    ];

    /* Initial settings */

    static public function useModel(string $model_class)
    {
        $model = self::analyzeModel($model_class);
        self::generateContainerSettings($model);
        Session::start('auth_'.$model -> getModelClass());

        Debug::pre(self::$containers);
        Debug::pre($_SESSION);
    }

    static protected function generateContainerKey(object $model)
    {
        $class_name = $model -> getModelClass();

        return 'auth_'.$class_name.'_'.md5($class_name.Registry::get('SecretCode'));
    }

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
        $fields = ['login_field','password_field','token_field','date_registration_field','date_last_login_field'];

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
        $defaults['store_key'] = self::generateContainerKey($model);

        self::$containers[$model -> getModelClass()] = $defaults;
    }

    /* Login and logout */

    static public function register()
    {

    }

    static public function login()
    {

    }

    static public function logout()
    {

    }

    static public function check()
    {

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
}