<?php
/**
 * File manager for the admin panel of MV framework.
 * Manages files and directories, including cleaning up obsolete files.
 * The root directory is /userfiles/.
 */
class Filemanager
{
	/**
	 * Paginator object to split list of files into single pages.
	 * @var Paginator
	 */ 
	public $pagination;
		
	/**
	 * Current user object of admin panel to check the rights to manage files.
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

	/**
	 * Shows if we are currently at the root allowed derectory.
	 * @var bool
	 */
	private $in_root = true;

	public function __construct()
	{
		$_SESSION['mv']['file_manager'] ??= [];
		$_SESSION['mv']['file_manager']['path'] ??= Registry::get('FilesPath');

		$regexp = Service::prepareRegularExpression(Registry::get('FilesPath'));
		$path = $_SESSION['mv']['file_manager']['path'];
		$match = preg_match('/^'.$regexp.'/ui', $path);

		if(!$match || $path === '/' || strpos($path, '..') !== false || !is_dir($path))
			$path = $_SESSION['mv']['file_manager']['path'] = Registry::get('FilesPath');

		foreach(self::FORBIDDEN_FILES as $forbidden)
			if(realpath($path) == realpath(Registry::get('FilesPath').$forbidden))
			{
				$path = $_SESSION['mv']['file_manager']['path'] = Registry::get('FilesPath');
				break;
			}

		$this -> in_root = realpath(Registry::get('FilesPath')) === realpath($path);

		$this -> path = preg_replace('/\/$/', '', $path);
		$_SESSION['mv']['file_manager']['path'] = $this -> path;

		$limit = AdminPanel::getPaginationLimit();
		$this -> total = $this -> countFilesInDirectory($this -> path);

		if($this -> in_root && $this -> total > 0)
			$this -> total --;

		$this -> pagination = new Paginator($this -> total, $limit);
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
		self::$cleanup_limit = $limit;
	}

	static public function getCleanupLimit(): int
	{
		return self::$cleanup_limit;
	}

	public function displayCurrentPath()
	{ 
		$html = Service::removeFileRoot($this -> path);
		$html = str_replace('/', ' / ', $html);

		return '/ '.$html;
	}

	/* Navigation */

	/**
	 * Canges current filesystem path stored in session.
	 * @param string $path clicked folder or back action
	 * @return bool true is success
	 */
	public function navigate(string $path): bool
	{
		$path = trim($path);

		if($path === 'back')
		{
			if($this -> in_root)
				return false;
			
			$back = realpath($_SESSION['mv']['file_manager']['path'].'/..');

			if($back !== false)
			{
				$_SESSION['mv']['file_manager']['path'] = str_replace('\\', '/', $back);

				return true;
			}
		}
		else if(preg_match('/^folder-/', $path))
		{
			$folder = preg_replace('/^folder-/', '', $path);

			if($folder == '' || preg_match('/[\.`\/\\\]+/', $folder) || !is_dir($this -> path.'/'.$folder))
				return false;

			$_SESSION['mv']['file_manager']['path'] .= '/'.$folder;

			return true;
		}

		return false;
	}

	/**
	 * Selects files and folders to display according to rules and pagination.
	 * @return array files and folders to display
	 */
	public function prepareFilesForDisplay(): array
	{
		clearstatcache();

		if(false === $descriptor = $this -> openDirectory($this -> path))
			return [];

		$pagination = $this -> pagination -> getState();
		$count = 0;
		$result = [];
		
		if(!$this -> in_root)
		{
			if($pagination['page'] == 1)
				$result = ['..'];

			$count = 1;
		}

		while(false !== ($file = readdir($descriptor)))
			if($file !== '.' && $file !== '..' && is_dir($this -> path.'/'.$file))
				{
					if($count >= $pagination['item_from'] && $count <= $pagination['item_to'])
						$result[$count] = $this -> path.'/'.$file;

					$count ++;
				}

		rewinddir($descriptor);

		while(false !== ($file = readdir($descriptor)))
			if($file !== '.' && $file !== '..' && is_file($this -> path.'/'.$file))
				{
					if($count >= $pagination['item_from'] && $count <= $pagination['item_to'])
						$result[$count] = $this -> path.'/'.$file;

					if( ++ $count > $pagination['item_to'])
						break;
				}

		closedir($descriptor);

		return $result;
	}

	/**
	 * Shows the list of all files / folders in directory, according to pagination.
	 * @return string html markup
	 */
	public function display(array $files): string
	{
		clearstatcache();
		self::cleanTmpFiles();

		$html = '';
		$images_types = Registry::get('AllowedImages');
		$all_types = Registry::get('AllowedFiles');
		$tmp = Registry::get('FilesPath').'tmp';
		$imager = new Imager();
		$img_max_weight = 1024 * 1024 * 3;
		$secret = Registry::get('SecretCode');
		$highlight = AdminPanel::getFlashParameter('highlight') ?? '';
		$can_delete = $this -> user -> checkModelRights('filemanager', 'delete');

		foreach($files as $file)
		{
			if($file === '..')
				$size = 0;
			else
				$size = is_dir($file) ? $this -> defineFolderSize($file) : filesize($file);

			$params = '-';
			$disable = false;
			$href = Service::removeDocumentRoot($file);
			$identity = ['type' => '', 'path' => $file, 'token' => md5(filemtime($file).$file.$secret)];

			if(is_dir($file) && $file !== '..')
			{
				if(in_array(basename($file), self::FORBIDDEN_FILES))
					$disable = true;

				$total_in = $this -> countFilesInDirectory($file) - 1;
				
				if($total_in > 0)
					$params = I18n::locale('number-files', ['number' => $total_in, 'files' => '*number']);

				$identity['type'] = 'directory';
			}
			else if(is_file($file))
			{
				$extension = Service::getExtension($file);

				if(!in_array($extension, $all_types) || preg_match('/^\.[^\.]+/', $file))
					$disable = true;
				else
				{
					if(in_array($extension, $images_types))
					{
						if($extension === 'svg')
						{
							$thumb = Service::removeDocumentRoot($file);
							$params = "<img class=\"svg-image\" src=\"".$thumb."\" alt=\"".basename($file)."\" />\n";
						}
						else if(filesize($file) <= $img_max_weight)
						{
							$tmp_file = $tmp.'/'.md5($file).'.'.$extension;
							copy($file, $tmp_file);
							$thumb = $imager -> compress($tmp_file, 'filemanager', 60, 60);
							unlink($tmp_file);

							if($thumb)
								$params = "<img src=\"".$thumb."\" alt=\"".basename($thumb)."\" />\n";
						}

						if(strpos($params, '<img ') !== false)
						{
							$params = "<div>\n<a target=\"_blank\" href=\"".$href."\">".$params."</a>\n";
							
							if($dimentions = @getimagesize($file))
								$params .= "<div>".$dimentions[0].' x '.$dimentions[1]." px</div>\n";

							$params .= "</div>\n";
						}
					}

					$identity['type'] = 'file';
				}
			}

			$css = $file === '..' ? 'back' : '';
			$css = is_dir($file) && $file !== '..' ? 'folder' : $css;
			$css .= $disable ? ' disabled' : '';
			$css .= $highlight === $file ? ' moved-line' : '';

			$html .= "<tr".($css ? ' class="'.$css.'"' : '').">\n";
			$html .= "<td class=\"name\">";

			if($file === '..')
				$html .= "<a href=\"?navigation=back\"> .. ".mb_strtolower(I18n::locale('back'))."</a>";
			else if($disable)
				$html .= basename($file);
			else if(is_dir($file))
				$html .= "<a href=\"?navigation=folder-".urlencode(basename($href))."\">".basename($file)."</a>";
			else
				$html .= "<a target=\"_blank\" href=\"".$href."\">".basename($file)."</a>";

			$html .= "</td>\n";				
			
			$html .= "<td>".(($file === '..' || !$size) ? '-' : I18n::convertFileSize($size))."</td>\n";			
			$html .= "<td class=\"params\">".$params."</td>\n";
			$html .= "<td>".I18n::timestampToDate(filemtime($file))."</td>\n";

			if(is_dir($file) && count(scandir($file)) > 2 || !is_writable($file))
				$disable = true;

			if($file !== '..')
			{
				$identity = Service::encodeBase64(json_encode($identity));
				$type = is_file($file) ? 'is-file' : 'is-folder';
				$css = '';

				if($disable || !$can_delete || !is_writable($file))
				{
					$css = ' disabled';
					$identity = '';
				}

				$html .= "<td class=\"operations\"><a class=\"single-action action-delete ".$type.$css."\"";
				$html .= " data=\"".$identity."\"></a></td>\n";
			}
			else
				$html .= "<td></td>\n";
			
			$html .= "</tr>\n";
		}

		AdminPanel::clearFlashParameters();

		return $html;
	}

	/* Actions in admin panel */

	/**
	 * Uploads a file using FileModelElement functions.
	 * @param string $name field name in $_FILES array
	 */
	public function uploadFile(string $name)
	{
		$folder = Service::removeFileRoot($this -> path);
		$folder = preg_replace('/^\/?userfiles\/?/', '', $folder);
		$object = new FileModelElement('File', 'file', $name, ['files_folder' => $folder]);
		$object -> setValue($_FILES[$name] ?? []);
		$result = ['success' => false, 'message' => ''];

		if(is_null($object -> getValue()) || !is_file($object -> getValue()))
		{
			if($object -> getError())
				$result['message'] = Model::processErrorText(['File', $object -> getError()], $object);

			return $result;
		}

		if(file_exists($this -> path.'/'.$object -> getProperty('file_name')))
		{
			$result['message'] = I18n::locale('file-exists');
			return $result;
		}

		if($object -> copyFile())
		{
			$result = [
				'success' => true,
				'message' => I18n::locale('file-uploaded')
			];

			AdminPanel::addFlashParameter('highlight', $this -> path.'/'.$object -> getProperty('file_name'));
		}

		return $result;
	}

	/**
	 * Creates a new folder (directory) in current directory.
	 */
	public function createFolder($name): array
	{
		$name = trim(strval($name));
		$result = ['success' => false, 'message' => ''];
		
		if($name === '' || $name === '/')
			return $result;

		if(preg_match("/[^\w\-\s]/ui", $name))
		{
			$result['message'] = I18n::locale('bad-folder-name');
			return $result;
		}
		
		if(file_exists($this -> path.'/'.$name))
		{
			$result['message'] = I18n::locale('folder-exists');
			return $result;
		}

		if(self::createDirectory($this -> path.'/'.$name))
		{
			$result = [
				'success' => true,
				'message' => I18n::locale('folder-created')
			];

			AdminPanel::addFlashParameter('highlight', $this -> path.'/'.$name);
		}

		return $result;
	}

	/**
	 * Common delete processor for files and folders.
	 * @param string $target packed identifier
	 */
	public function deleteAction(string $target)
	{
		$result = ['success' => false, 'message' => ''];
		$target = json_decode(Service::decodeBase64($target), true);

		if(!is_array($target) || !isset($target['path']))
			return $result;

		$in_path = $this -> path.'/'.basename($target['path']);
		$token = md5(filemtime($target['path']).$target['path'].Registry::get('SecretCode'));

		if(!file_exists($target['path']) || $target['token'] !== $token || $in_path !== $target['path'])
			return $result;

		if(!is_writable($target['path']))
			$result['message'] = I18n::locale('no-rights');
		else
		{
			if($target['type'] === 'file')
				$result['success'] = $this -> deleteFile($target['path']);
			else if($target['type'] === 'directory')
				$result['success'] = $this -> deleteFolder($target['path']);

			$key = $result['success'] ? 'done-delete' : 'not-deleted';
			$result['message'] = I18n::locale($key);		
		}

		return $result;
	}

	/**
	 * Deletes folder in current directory.
	 */
	public function deleteFolder(string $path)
	{
		if(is_dir($path))
			@rmdir($path);

		return !is_dir($path);
	}

	/**
	 * Deletes the one file in current directory.	
	 */
	public function deleteFile(string $path)
	{
		if(is_file($path))
			@unlink($path);

		return !is_file($path);
	}

	/* Cleanup support */

	/**
	 * Removes filemeneger temporary files form certain directories.
	 */
	public function cleanTmpFiles()
	{
		$tmp_folder = Registry::get('FilesPath').'tmp/';
		$descriptor = $this -> openDirectory($tmp_folder);		

		while(false !== ($file = readdir($descriptor)))
			if(is_file($tmp_folder.$file) && !preg_match('/^\.[^\.]+/', $file))
				if(time() - filemtime($tmp_folder.$file) > 3600)
					@unlink($tmp_folder.$file);
		
		$tmp_folder .= 'filemanager/';
		$descriptor = $this -> openDirectory($tmp_folder);
		
		while(false !== ($file = readdir($descriptor)))
			if(is_file($tmp_folder.$file) && !preg_match('/^\.[^\.]+/', $file))
				if(time() - filemtime($tmp_folder.$file) > 60)
					@unlink($tmp_folder.$file);
	}

	/**
	 * Deletes temporary images which are not reladted to any image from main folder.
	 * There must be a dir with initial images and dirs like tmp, tmpsmall with thumbs.
	 */
	static public function cleanModelImages(string $path)
	{
		if(!is_dir($path))
			return;
		
		clearstatcache();
		
		$tmp_folders = $parents = []; //Folders with temporary images and array of deleted images in main folder
		$dir = opendir($path);

		$registry = Registry::instance();
		$step = (int) $registry -> getDatabaseSetting('admin_cleanup_step');
		$start = $step * self::$cleanup_limit;
		$stop = $start + self::$cleanup_limit;
		
		while(false !== ($file = readdir($dir)))
		{
			if($file == "." || $file == "..")
				continue;
			
			if(filetype($path.$file) == "dir") //Collects all temporary directories
				$tmp_folders[] = $file;
			
			if(filetype($path.$file) == "file") //Files (parents) of temporary copies
			{
				self::$cleanup_count ++;

				if(self::$cleanup_count < $start)
					continue;
				else if(self::$cleanup_count > $stop)
					break;

				$parents[] = $file;
			}
		}

		if(self::$cleanup_count > $stop)
		{
			$registry -> setDatabaseSetting("admin_cleanup_step", $step + 1);
			self::$cleanup_count = "stop";
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
		$models = array_keys(Registry::get('ModelsLower'));
		$path = Registry::get("FilesPath")."models/";
		
		foreach($models as $model)
		{
			if(is_dir($path.$model."-images/"))
				Filemanager::cleanModelImages($path.$model."-images/");

			if(self::$cleanup_count == "stop")
				return;

			if(is_dir($path.$model."-files/"))
				Filemanager::cleanModelImages($path.$model."-files/");

			if(self::$cleanup_count == "stop")
				return;
		}

		if(self::$cleanup_count != "stop")
			Registry::setDatabaseSetting("admin_cleanup_step", 0);
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

	public function openDirectory(string $path = '')
	{
		$folder = $path === '' ? $this -> path : $path;
		$descriptor = @opendir($folder);
		
		if($descriptor === false)
		{
			Debug::displayError('Unable to open the directory:'.$folder);
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
			if($file === '.')
				continue;
			
			//Counts only real files and folders
			if(is_file($directory.'/'.$file) || is_dir($directory.'/'.$file))
				$count ++;
		}

		closedir($descriptor);
		
		return $count;
	}	

	/**
	 * Calculates total size of directory with all it's content.
	 */
	public function defineFolderSize(string $path): int
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


	/**
	 * Creates new directory and sets permissions according to OS.
	 * @return bool true if created succesfully, false if not
	 */
	static public function createDirectory(string $path): bool
	{
		if(!file_exists($path))
			mkdir($path, 0777, true);

		if(PHP_OS_FAMILY === 'Darwin')
			chmod($path, 0777);

		return is_dir($path);
	}
}