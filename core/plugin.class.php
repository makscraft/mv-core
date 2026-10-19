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
    public ?string $root_path = null;

   	/**
	 * Pagination manager.
	 * @var object Paginator
	 */
	public ?Paginator $paginator = null;

	/**
	 * Says if we need to start the plugin right after main MV object (Builder class) is constructed.
	 * @var bool
	 */
	protected ?bool $auto_start = null;
	
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

	public function getElement(string $name) {}

	public function __get(string $name)
	{
		if($name === 'pager')
			return $this -> paginator;
	}

	public function __isset(string $name)
	{
		if($name === 'pager')
			return isset($this -> paginator);
	}

	public function __call(string $method, array $arguments)
	{		
		if($method == 'runPager')
			return $this -> runPaginator(...$arguments);
		else
			Debug::handleCallMagicMethod($method, self::class, debug_backtrace());
	}
}