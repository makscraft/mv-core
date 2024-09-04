<?php
/**
 * Class is under construction ...
 */
class AdminPanel
{
    /**
	 * Current user object of admin panel with credentials and settings.
	 * @var User
	 */ 
	public $user;

    /**
     * Available limits for pagination in admin panel, for all modules.
     */
    public const PAGINATION_LIMITS = [5, 10, 15, 20, 30, 50, 100, 200, 300, 500];

    public function __construct(User $user = null)
    {
        Registry :: set('AdminPanelEnvironment', true);
        
        if(!Service :: sessionIsStarted())
            session_start();

        $_SESSION['mv']['flash_messages'] ??= [];
        $_SESSION['mv']['flash_parameters'] ??= [];

        if(is_object($user))
            $this -> setUser($user);
    }

    /**
     * Sets current user objec in admin panel.
     */
    public function setUser(User $user)
	{ 
		$this -> user = $user;
		return $this;
	}

    static public function getPaginationLimit(): int
    {
        $limit = intval($_SESSION['mv']['settings']['pager-limit'] ?? 10);

        return in_array($limit, self :: PAGINATION_LIMITS) ? $limit : 10;
    }

    public function savePaginationLimit(mixed $limit): bool
    {
        $limit = intval($limit);

        if(!in_array($limit, self :: PAGINATION_LIMITS))
            return false;

        $_SESSION['mv']['settings']['pager-limit'] = $limit;

        if(is_object($this -> user))
            $this -> user -> saveSettings($_SESSION['mv']['settings']);

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

    static public function addFlashParameter(string $key, mixed $value)
    {
        if(is_numeric($value) || is_string($value) || is_array($value))
            $_SESSION['mv']['flash_parameters'][$key] = $value;
    }

    static public function getFlashParameter(string $key): mixed
    {
        return $_SESSION['mv']['flash_parameters'][$key] ?? null;
    }

    static public function clearFlashParameters()
    {
        $_SESSION['mv']['flash_parameters'] = [];
    }
}