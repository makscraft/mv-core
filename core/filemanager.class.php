<?php
/**
 * File manager class for admin panel of MV framework.
 * Operates with files and folders, can also cleanup old useless files.
 * Root directory is /userfiles/.
 */

class Filemanager
{
	/**
	 * Paginator object to split list of files into single pages.
	 * @var Paginator
	 */ 
	public $pagination;
		
	/**
	 * User object to check the rights to manage files.
	 * @var User
	 */ 
	public $user;

	/**
	 * Current filesystem path we show the files from.
	 * @var string
	 */ 
	protected $path;

	/**
	 * Total number of items in the current directory, including directories and '..' (but not '.').
	 * @var int
	 */ 
	protected $total;

	/**
	 * Limit of files to process during the one run of cleanup.
	 * @var int
	 */ 
	private static $cleanup_limit = 10000;

	/**
	 * Processed files of current cleanup step.
	 * @var mixed can have int or string value
	 */
	private static $cleanup_count;

	/**
	 * CSRF token from System core class.
	 * @var string
	 */ 
	private $token;

	/**
	 * Files and directories to protect.
	 * @var array
	 */
	private const FORBIDDEN_FILES = ['tmp', 'cache', 'database'];

	public function __construct()
	{
		$_SESSION['mv']['file_manager'] ??= [];
		$_SESSION['mv']['file_manager']['path'] ??= Registry :: get('FilesPath');

		$regexp = Service :: prepareRegularExpression(Registry :: get('FilesPath'));
		$path = $_SESSION['mv']['file_manager']['path'];
		$match = preg_match('/^'.$regexp.'/ui', $path);

		if(!$match || $path === '/' || strpos($path, '..') !== false || !is_dir($path))
			$path = $_SESSION['mv']['file_manager']['path'] = Registry :: get('FilesPath');

		$this -> path = preg_replace('/\/$/', '', $path);

		$limit = AdminPanel :: getPaginationLimit();
		$this -> total = $this -> countFilesInDirectory($this -> path);
		$this -> pagination = new Paginator($this -> total, 10);
	}

	public function setUser(User $user)
	{ 
		$this -> user = $user;
		return $this;
	}
	
	public function setToken(string $token)
	{
		$this -> token = $token;
		return $this;
	}

	static public function setCleanupLimit(int $limit)
	{
		self :: $cleanup_limit = $limit;
	}

	static public function getCleanupLimit(): int
	{
		return self :: $cleanup_limit;
	}

	public function displayCurrentPath()
	{ 
		$html = Service :: removeDocumentRoot($this -> path);
		$html = str_replace('/', ' / ', $html);

		return $html;
	}

	public function openDirectory(string $path = '')
	{
		$folder = $path === '' ? $this -> path : $path;
		$descriptor = @opendir($folder);
		
		if($descriptor === false)
		{
			Debug :: displayError('Unable to open the directory:'.$folder);
			return false;
		}
		else 
			return $descriptor;
	}

	/**
	 * Counts the total number of files in directory for pagination.
	 */
	public function countFilesInDirectory(string $directory): int
	{
		if(false === $descriptor = $this -> openDirectory($directory))
			return 0;
		
		$count = 0;
				
		while(false !== ($file = readdir($descriptor)))
		{
			if($file === '.' || preg_match('/^\.[^\.]+/', $file))
				continue;
			
			if($file === '..' && $this -> path == Registry :: get('FilesPath'))
				continue;

			if(is_file($directory.'/'.$file) || is_dir($directory.'/'.$file)) //Counts only real files and folders
				$count ++;
		}
		
		return $count;
	}

	public function prepareFilesForDisplay(): array
	{
		if(false === $descriptor = $this -> openDirectory($this -> path))
			return ['..'];

		$result = ['..'];

		while(false !== ($file = readdir($descriptor)))
			if($file !== '.' && $file !== '..' && !preg_match('/^\.[^\.]+/', $file))
				if(is_dir($this -> path.'/'.$file))
					$result[] = $this -> path.'/'.$file;

		rewinddir($descriptor);

		while(false !== ($file = readdir($descriptor)))
			if($file !== '.' && $file !== '..' && !preg_match('/^\.[^\.]+/', $file))
				if(is_file($this -> path.'/'.$file))
					$result[] = $this -> path.'/'.$file;

		return $result;
	}

	/**
	 * Shows the list of all files / folders in directory, according to pagination.
	 * @return string html markup
	 */
	public function display(array $files): string
	{
		clearstatcache();
		self :: cleanTmpFiles();

		$html = '';
		$images_types = Registry :: get('AllowedImages');
		$all_types = Registry :: get('AllowedFiles');
		$tmp = Registry :: get('FilesPath').'tmp';
		$imager = new Imager();

		foreach($files as $file)
		{
			if($file === '..')
				$size = 0;
			else
				$size = is_dir($file) ? $this -> defineFolderSize($file) : filesize($file);

			$params = '-';
			$disable = false;
			$href = Service :: removeDocumentRoot($file);

			if(is_dir($file) && $file !== '..')
			{
				if(in_array(basename($file), self :: FORBIDDEN_FILES))
					$disable = true;
				else
					$params = I18n :: locale('show').': '.$this -> countFilesInDirectory($file) - 1;
			}
			else if(is_file($file))
			{
				$extension = Service :: getExtension($file);

				if(!in_array($extension, $all_types))
					$disable = true;
				else
				{
					if(in_array($extension, $images_types))
					{
						if($extension === 'svg')
						{
							$thumb = Service :: removeDocumentRoot($file);
							$params = "<img class=\"svg-image\" src=\"".$thumb."\" alt=\"".basename($file)."\" />\n";
						}
						else
						{
							$tmp_file = $tmp.'/'.md5($file).'.'.$extension;
							copy($file, $tmp_file);
							$thumb = $imager -> compress($tmp_file, 'filemanager', 100, 100);
							unlink($tmp_file);

							if($thumb)
								$params = "<img src=\"".$thumb."\" alt=\"".basename($thumb)."\" />\n";
						}

						if(strpos($params, '<img ') !== false)
						{
							$params = "<a target=\"_blank\" href=\"".$href."\">".$params."</a>\n";
							
							if($dimentions = @getimagesize($file))
								$params .= "<span>".$dimentions[0].' x '.$dimentions[1]." px<span>\n";
						}
					}
				}
			}

			$html .= "<tr".($disable ? ' class="disabled"' : '').">\n";
			//$html .= "<td><input type=\"checkbox\" name=\"delete_".$count."\" value=\"\" /></td>\n";

			if($file === '..')
				$html .= "<td><a href=\"?back=".md5(dirname($file))."\"> .. ".I18n :: locale('back')."</a></td>\n";
			else if($disable)
				$html .= "<td class=\"name\">".basename($file)."</td>\n";
			else if(is_dir($file))
				$html .= "<td><a href=\"?folder=".basename($href)."\">".basename($file)."</a></td>\n";
			else
				$html .= "<td><a target=\"_blank\" href=\"".$href."\">".basename($file)."</a></td>\n";
			
			$html .= "<td>".($file === '..' ? '-' : I18n :: convertFileSize($size))."</td>\n";
			$html .= "<td>".I18n :: timestampToDate(filemtime($file))."</td>\n";
			$html .= "<td>".$params."</td>\n";

			if($file !== '..' && !$disable)
				$html .= "<td><a class=\"single-action action-delete\"></a></td>\n";
			else
				$html .= "<td></td>\n";
			
			$html .= "</tr>\n";
		}

		return $html;
	}

	/* Actions in admin panel */

	/**
	 * 
	 */
	public function uploadFile($file)
	{

	}

	/**
	 * 
	 */
	public function createFolder($name)
	{
		$name = trim(strval($name));
		
		if($name == "")
			return;
		
		if(file_exists($this -> path.$name))
			return "error=folder-exists";
		else if(preg_match("/[^\w-]/", $name))
			return "error=bad-folder-name";
		
		// Creates a new folder (directory) in current directory
		return @mkdir($this -> path.$name) ? "done=folder-created" : "error=folder-not-created";
	}

	/**
	 * Deletes folder in current directory.
	 */
	public function deleteFolder($folder)
	{
		if(file_exists($this -> path.$folder))
			return @rmdir($this -> path.$folder);
	}

	/**
	 * Deletes the one file in current directory.	
	 */
	public function deleteFile($file)
	{
		if(file_exists($this -> path.$file))
			return @unlink($this -> path.$file);
	}

	/* Cleanup support */

	/**
	 * Removes filemeneger temporary files form certain directories.
	 */
	public function cleanTmpFiles()
	{
		$tmp_folder = Registry :: get('FilesPath').'tmp/';
		$descriptor = $this -> openDirectory($tmp_folder);		

		while(false !== ($file = readdir($descriptor)))
			if(is_file($tmp_folder.$file) && !preg_match('/^\.[^\.]+/', $file))
				if(time() - filemtime(tmp_folder.$file) > 3600)
					@unlink($tmp_folder.$file);
		
		$tmp_folder .= 'filemanager/';
		$descriptor = $this -> openDirectory($tmp_folder);
		
		while(false !== ($file = readdir($descriptor)))
			if(is_file($tmp_folder.$file) && !preg_match('/^\.[^\.]+/', $file))
				if(time() - filemtime($tmp_folder.$file) > 60)
					@unlink($tmp_folder.$file);
	}

	static public function cleanModelImages($path) 
	{
		//Deletes temporary images which are not reladted to any image from main folder
		//There must be dir with initial images and dirs like tmp, tmpsmall with thumbs

		if(!is_dir($path))
			return;
		
		clearstatcache();
		
		$tmp_folders = $parents = []; //Folders with temporary images and array of deleted images in main folder
		$dir = opendir($path);

		$registry = Registry :: instance();
		$step = (int) $registry -> getDatabaseSetting('admin_cleanup_step');
		$start = $step * self :: $cleanup_limit;
		$stop = $start + self :: $cleanup_limit;
		
		while(false !== ($file = readdir($dir)))
		{
			if($file == "." || $file == "..")
				continue;
			
			if(filetype($path.$file) == "dir") //Collects all temporary directories
				$tmp_folders[] = $file;
			
			if(filetype($path.$file) == "file") //Files (parents) of temporary copies
			{
				self :: $cleanup_count ++;

				if(self :: $cleanup_count < $start)
					continue;
				else if(self :: $cleanup_count > $stop)
					break;

				$parents[] = $file;
			}
		}

		if(self :: $cleanup_count > $stop)
		{
			$registry -> setDatabaseSetting("admin_cleanup_step", $step + 1);
			self :: $cleanup_count = "stop";
		}

		closedir($dir);
		
		foreach($tmp_folders as $folder) //Search the temporary copies of deleted file
		{
			$sub_dir = $path.$folder."/"; //Directory to open

			if(!is_dir($sub_dir))
				continue;

			$dir = opendir($sub_dir);
			
			while(false !== ($file = readdir($dir)))
			{
				if($file == "." || $file == ".." || filetype($sub_dir.$file) != "file")
					continue;
								
				$real_name = str_replace($folder."_", "", $file); //Takes real name of file

				if(!in_array($real_name, $parents)) //If the temp copy not related to any initial file we delete it
					@unlink($sub_dir.$file);
			}			
		    
			closedir($dir);
		}
	}
	
	static public function makeModelsFilesCleanUp()
	{
		$models = array_keys(Registry :: get('ModelsLower'));
		$path = Registry :: get("FilesPath")."models/";
		
		foreach($models as $model)
		{
			if(is_dir($path.$model."-images/"))
				Filemanager :: cleanModelImages($path.$model."-images/");

			if(self :: $cleanup_count == "stop")
				return;

			if(is_dir($path.$model."-files/"))
				Filemanager :: cleanModelImages($path.$model."-files/");

			if(self :: $cleanup_count == "stop")
				return;
		}

		if(self :: $cleanup_count != "stop")
			Registry :: setDatabaseSetting("admin_cleanup_step", 0);
	}
	
	static public function deleteOldFiles($path)
	{
		if(!is_dir($path))
			return;

		clearstatcache();
		$dir = opendir($path);
		
		if($dir)
			while(false !== ($file = readdir($dir)))
			{
				if($file == '.' || $file == '..' || preg_match('/^\./', $file))
					continue;
					
				if(filetype($path.$file) == 'file' && (time() - filemtime($path.$file)) >= 10800)
					@unlink($path.$file);
			}
	}

	static public function deleteResizedCopiesOfOneImage($image_path)
	{
		if(!is_file($image_path))
			return;

		clearstatcache();

		$path = dirname($image_path);
		$image_name = basename($image_path);
		$path = preg_replace("/\/$/", "", $path)."/";
		$tmp_folders = [];
		$dir = opendir($path);
		
		while(false !== ($file = readdir($dir)))
		{
			if($file == "." || $file == "..")
				continue;
			
			if(filetype($path."/".$file) == "dir")
				$tmp_folders[] = $file;			
		}

		closedir($dir);
		
		foreach($tmp_folders as $folder)
		{
			$sub_dir = $path.$folder."/";
			$dir = opendir($sub_dir);
			
			while(false !== ($file = readdir($dir)))
			{
				if($file == "." || $file == ".." || filetype($sub_dir.$file) != "file")
					continue;
								
				if($file == $image_name)
				{
					@unlink($sub_dir.$image_name);
					break;
				}
			}
		    
			closedir($dir);
		}		
	}


	/* Heplers */

	public function defineFolderSize(string $path)
	{
	    $size = 0;
	    $dir = scandir($path);
	    
	    foreach($dir as $file)
	        if (($file !== '.') && ($file !== '..'))
	            if(is_dir($path.'/'.$file))
	                 $size += $this -> defineFolderSize($path.'/'.$file);
	            else
	                 $size += filesize($path.'/'.$file);
	    
	    return $size;
	}
}