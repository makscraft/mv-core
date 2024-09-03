<?php
/**
 * Class is under construction ...
 */
class AdminPanel
{
    /**
     * Available limits for pagination in admin panel, for all modules.
     */
    public const PAGINATION_LIMITS = [5, 10, 15, 20, 30, 50, 100, 200, 300, 500];

    public function __construct()
    {
        Registry :: set('AdminPanelEnvironment', true);
        
        if(!Service :: sessionIsStarted())
            session_start();

        if(!isset($_SESSION['mv']['flash_messages']))
            $_SESSION['mv']['flash_messages'] = [];
    }

    static public function getPaginationLimit(): int
    {
        $limit = intval($_SESSION['mv']['settings']['pager-limit'] ?? 10);

        return in_array($limit, self :: PAGINATION_LIMITS) ? $limit : 10;
    }

    static public function savePaginationLimit(mixed $limit): bool
    {
        $limit = intval($limit);

        if(!in_array($limit, self :: PAGINATION_LIMITS))
            return false;

        $_SESSION['mv']['settings']['pager-limit'] = $limit;

        return true;
    }

    public function addFlashMessage(string $type, string $message)
    {
        $_SESSION['mv']['flash_messages'][$type] ??= [];
        $_SESSION['mv']['flash_messages'][$type][] = $message;

        return $this;
    }

    public function displayAndClearFlashMessages(): string
    {
        $html = '';

        foreach($_SESSION['mv']['flash_messages'] as $type => $messages)
        {
            $html .= "<div class=\"flash-message ".$type."\">\n";

            foreach($messages as $message)
                $html .= "<div>".$message."</div>\n";

            $html .= "</div>\n";
        }

        $_SESSION['mv']['flash_messages'] = [];

        return $html;
    }
}