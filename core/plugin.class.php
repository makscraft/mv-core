<?php
/**
 * Base class for MV plugins.
 * Plugins are activated by adding them to the array in config/plugins.php.
 * Plugin objects are instantiated automatically within the Builder $mv object.
 */
abstract class Plugin extends ModelInitial
{
	/**
	 * Url from the domain root including the app folder (if actual).
	 * @var string
	 */
    public $root_path;

   	/**
	 * Pagination manager.
	 * @var object Paginator
	 */
	public $paginator;

	/**
	 * Says if we need to start the plugin right after main MV object (Builder class) is constructed.
	 * @var bool
	 */
	protected $auto_start;
	
	public function __construct()
	{
		$this -> registry = Registry::instance(); 
		$this -> db = DataBase::instance();
		$this -> table = $this -> registry -> definePluginTableName(get_class($this));
		$this -> root_path = $this -> registry -> get('MainPath');
	}
	
	public function getTable()
	{
		return $this -> table;
	}
	
	public function getId()
	{
		return $this -> id; 
	}

	public function runPaginator(int $total, int $limit, mixed $current = null)
	{
		$this -> paginator = new Paginator($total, $limit);

		if(is_numeric($current))
			$this -> paginator -> definePage(intval($current));
	}	

	public function __get($name)
	{
		if($name === 'pager')
			return $this -> paginator;
	}

	public function __isset($name)
	{
		if($name === 'pager')
			return isset($this -> paginator);
	}

	public function __call($method, $arguments)
	{		
		if($method == 'runPager')
			return $this -> runPaginator($arguments[0], $arguments[1], $arguments[2] ?? null);
		else
		{
			$trace = debug_backtrace();
			$message = "Call to undefiend method '".$method."' of plugin '".get_class($this)."'";
			$message .= ', in line '.$trace[0]['line'].' of file ~'.Service::removeDocumentRoot($trace[0]['file']);

			Debug::displayError($message, $trace[0]['file'], $trace[0]['line']);
		}		
	}
}