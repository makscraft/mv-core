<?php
/**
 * Session manager for MV applications.
 * Keeps sessions data in separate containers.
 * Default session has label 'front' and can be used anywhere like Session::get('key').
 */
class Session
{
    private static $current = null;

    private static $containers = [];

    private function __construct() {}

    static public function start(string $container = 'front')
    {
        self::$current = $container;

        self::$containers[$container] ??= [
            'key' => self::generateKey($container),
            'ip' => $_SERVER['REMOTE_ADDR'],
            'time' => time(),
            'data' => []
        ];

        //Debug::pre(get_class_vars(Session::class));
        Debug::pre(self::$containers);
        Debug::pre($_SESSION);
    }

    static public function switch(string $container = 'front')
    {
        if(!array_key_exists($container, self::$containers))
            Debug::displayError("Unable to switch on '".$container."' session container. Such container was not started before. ");
    }

    static public function destroy(string $container)
    {

    }

    static private function generateKey(string $container): string
    {
        return '';
    }

    static public function current(): ?string
    {
        return self::$current;
    }

    static public function dump(): array
    {
        return [];
    }

    static public function set(string $key, mixed $value)
    {

    }

    static public function get(string $key, mixed $default = null)
    {

    }

    static public function empty(string $container)
    {

    }
}