<?php
/**
 * Flash messages are stored in an isolated session, split into groups and destroyed after display.
 * Types are: 'info', 'notice', 'error', 'success'.
 */
class FlashMessages
{
    /**
     * Container with messages of all types.
     */
    private static $messages = null;

    /**
     * Available types list.
     */
    public const TYPES = ['info', 'notice', 'error', 'success'];

    /**
     * Disabled constructor.
     */
    private function __construct() {}

    /**
     * Adds one flash message into session accordint to message type.
     * Types are: 'info', 'notice', 'error', 'success'.
     */
    static public function add(string $type, string $message)
    {
        if(in_array($type, self::TYPES))
        {
            self::loadFromSession();
            self::$messages ??= [];
            self::$messages[$type] ??= [];
            self::$messages[$type][] = $message;
            self::saveIntoSession();
        }
    }

    /**
     * Saves all messages into separate session container.
     */
    static private function saveIntoSession()
    {
        $current = Session::current() ?? '';

        Session::start('flash');
        Session::set('flash_messages', self::$messages);
        Session::start($current);
    }

    /**
     * Loads all messages from separate session container into local static container.
     */
    static private function loadFromSession()
    {
        if(is_array(self::$messages))
            return;

        $current = Session::current() ?? '';

        Session::start('flash');
        self::$messages = Session::get('flash_messages', []);
        Session::start($current);
    }

    /**
     * Chaeck if messages of any type exist in container.
     */
    static public function hasAny(): bool
    {
        self::loadFromSession();
        return (bool) count(self::$messages);
    }

    /**
     * Gets all flash messages by type.
     * Types are: 'info', 'notice', 'error', 'success'.
     */
    static public function get(string $type): array
    {
        if(in_array($type, self::TYPES) && array_key_exists($type, self::$messages))
        {
            self::loadFromSession();
            return self::$messages[$type];
        }

        return [];
    }

    /**
     * Returns all flash messages of all types.
     */
    static public function all()
    {
        self::loadFromSession();
        return self::$messages;
    }

    /**
     * Removes all flash messages of all types from session.
     */
    static public function clear()
    {
        self::$messages = [];
        self::saveIntoSession();
    }

    /**
     * Displays all flash messages of all types wrapped into divs.
     * @return string html code
     */
    static public function display(): string
    {
        self::loadFromSession();
        $html = '';

        foreach(self::$messages as $type => $many)
        {
            $html .= "<div class=\"flash-message ".$type."\">\n";

            foreach($many as $one)
                $html .= "<div>".$one."</div>\n";

            $html .= "</div>\n";
        }

        return $html;
    }

    /**
     * Displays all flash messages of all types wrapped into divs.
     * Removes all messages after display.
     */
    static public function displayAndClear(): string
    {
        $html = self::display();
        self::clear();

        return $html;
    }
}