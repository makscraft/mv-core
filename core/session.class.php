<?php
/**
 * Session manager for MV applications, utilizing native PHP sessions.
 * Stores session data in separate containers with hashed keys.
 * The default session is labeled 'front' and can be accessed using 
 * Session::get/set('key', 'value') anywhere in the application.
 */
class Session
{
    /**
     * Label of current container.
     * @var string
     */
    private static $current = null;

    /**
     * Data and parameters of current container, loaded on Session::start().
     * @var array
     */
    private static $container = null;

    /**
     * Disabled constructor.
     */
    private function __construct() {}

    /**
     * Starts the session for certain container.
     * @param string $container label of needed container
     */
    static public function start(string $container = 'front')
    {
        if($container === '')
            return;

        if(!Service::sessionIsStarted())
            session_start();
        
        $key = self::generateKey($container);
        
        if(!array_key_exists($key, $_SESSION))
            $_SESSION[$key] = [
                'key' => $key,
                'ip_hash' => md5($_SERVER['REMOTE_ADDR']),
                'browser_hash' => md5($_SERVER['HTTP_USER_AGENT'] ?? ''),
                'start_time' => time(),
                'data' => []            
            ];

        self::$current = $container;
        self::$container = $_SESSION[$key];
    }
    
    /**
     * Creates hashed key for container to store data in session.
     * @param string $container label of needed container
     */
    static private function generateKey(string $container): string
    {
        return $container.'_'.md5($container.Registry::get('SecretCode').session_id());
    }
    
    /**
     * Saves the current container data into session global array.
     */
    private static function flush()
    {
        if(self::$current)
            $_SESSION[self::$container['key']] = self::$container;
    }

    /**
     * Returns the label of current session container.
     */
    static public function current(): ?string
    {
        return self::$current;
    }

    /**
     * Returns one parameter of current session.
     * @param string $key like 'start_time', 'ip_hash', 'browser_hash'
     */
    static public function getParameter(string $key)
    {
        if(self::$current !== null && is_array(self::$container))
            if(array_key_exists($key, self::$container) && $key !== 'key')
                return self::$container[$key];
    }

    /**
     * Checks if the certain session container was started before.
     * @param string $container label of needed container
     * @return bool true if the continer exists in global session
     */
    static public function exists(string $container = 'front'): bool
    {
        if($container === '' || !Service::sessionIsStarted())
            return false;

        return array_key_exists(self::generateKey($container), $_SESSION);
    }

    /**
     * Removes all container data from global session.
     * @param string $container label of needed container
     * @return bool true if the operation was performed
     */
    static public function destroy(string $container): bool
    {
        if(!self::exists($container))
            return false;

        unset($_SESSION[self::generateKey($container)]);

        if(self::$current === $container)
            self::$current = self::$container = null;

        return true;
    }

    /**
     * Returns all session data for current container.
     */
    static public function all(): array
    {
        return self::$container !== null && is_array(self::$container['data']) ? self::$container['data'] : [];
    }

    /**
     * Sets one value by key into current session container.
     */
    static public function set(string $key, mixed $value): mixed
    {
        if(self::$current === null)
            Debug::displayError('Session container is not started. You need to run Session::start() before.');

        self::$container['data'][$key] = $value;
        self::flush();

        return $value;
    }

    /**
     * Returns one value by key from current session container.
     */
    static public function get(string $key, mixed $default = null)
    {
        if(self::$current === null)
            Debug::displayError('Session container is not started. You need to run Session::start() before.');

        if(array_key_exists($key, self::$container['data']))
            return self::$container['data'][$key];
        else
            return $default;
    }

    /**
     * Checks if the current session data has passed keys.
     */
    static public function has(...$keys): bool
    {
        if(!isset(self::$container['data']) || !count($keys))
            return false;

        foreach($keys as $key)
            if(!array_key_exists($key, self::$container['data']))
                return false;

        return true;
    }

    /**
     * Removes one value by key from current session container.
     * @return bool true if the operation was performed
     */
    static public function remove(string $key): bool
    {
        if(self::$current === null)
            Debug::displayError('Session container is not started. You need to run Session::start() before.');

        if(array_key_exists($key, self::$container['data']))
        {
            unset(self::$container['data'][$key]);
            self::flush();

            return true;
        }

        return false;
    }

    /**
     * Removes all session data from current container.
     * @param string $container label of needed container
     * @return bool true if the operation was performed
     */
    static public function clear(string $container = 'front'): bool
    {
        if(self::exists($container))
        {
            self::$container['data'] = [];
            self::flush();

            return true;
        }

        return false;
    }
}