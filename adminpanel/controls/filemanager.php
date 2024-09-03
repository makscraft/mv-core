<?php
include_once '../../config/autoload.php';

$system = new System();
$admin_panel = new AdminPanel();
$system -> user -> extraCheckModelRights('file_manager', 'read');

$filemanager = new Filemanager();
$filemanager -> setUser($system -> user) -> setToken($system -> getToken());
$url_params = $filemanager -> pagination -> getUrlParams();

$action_complete = false;

if(isset($_GET['navigation']) && $filemanager -> navigate(strval($_GET['navigation'])))
	$action_complete = true;
else if(isset($_GET['pager-limit']) && AdminPanel :: savePaginationLimit($_GET['pager-limit']))
	$action_complete = true;

$allowed_actions = ['createFolder', 'deleteFolder', 'uploadFile', 'deleteFile'];

if(isset($_GET['action'], $_GET['token']) && in_array($_GET['action'], $allowed_actions))
	if($_GET['token'] === $system -> getToken())
	{
		$action = trim($_GET['action']);
		$parameter = $_POST['target'] ?? 'target';
		$permission = ($action == 'createFolder' || $action == 'uploadFile') ? 'create' : 'delete';

		$system -> user -> extraCheckModelRights('file_manager', $permission);
		$result = $filemanager -> $action($parameter);

		if($result['message'] !== '')
			$admin_panel -> addFlashMessage($result['success'] ? 'success' : 'error', $result['message']);

		// Debug :: pre($_GET);
		// Debug :: pre($result);

		$action_complete = true;
	}

if($action_complete)
	$system -> reload('controls/filemanager.php'.($url_params ? '?'.$url_params : ''));
//Debug :: pre(AdminPanel :: getPaginationLimit());
// Debug :: pre($url_params);
// Debug :: pre($filemanager);

//Debug :: pre($admin_panel);

//Debug :: pre(AdminPanel :: PAGINATION_LIMITS);
//Debug :: exit(Registry :: getAllSettings());

CacheMedia :: addJavaScriptFile(Registry :: get('AdminFolder').'/interface/js/file-manager.js');
CacheMedia :: addCssFile(Registry :: get('AdminFolder').'/interface/css/style-filemanager.css');
$to_display = $filemanager -> prepareFilesForDisplay();

//Debug :: pre($to_display);

include $registry -> getSetting('IncludeAdminPath').'includes/header.php';
?>
<div id="columns-wrapper">
    <div id="filemanager-area">
		<h3 class="column-header"><?php echo I18n :: locale('file-manager'); ?></h3>
		<?php echo $admin_panel -> displayAndClearFlashMessages(); ?>
		<div id="filemanager-path">
			<?php echo $filemanager -> displayCurrentPath();  ?>
		</div>
		<form id="filemanager-form" method="post">
			<table class="model-table filemanager">
				<tr>
					<th class="middle"><?php echo I18n :: locale('name'); ?></th>
					<th class="middle"><?php echo I18n :: locale('size'); ?></th>
					<th class="middle"><?php echo I18n :: locale('file-params'); ?></th>
					<th class="middle"><?php echo I18n :: locale('last-change'); ?></th>
					<th class="actions"><?php echo I18n :: locale('operations'); ?></th>
				</tr>
				<?php echo $filemanager -> display($to_display); ?>
			</table>
		</form>

		<?php 
			if($system -> user -> checkModelRights('file_manager', 'create'))
				$submit_button = "type=\"submit\"";
			else
				$submit_button = "type=\"button\" onclick=\"$.modalWindow.open(mVobject.locale('no_rights'), {css_class: 'alert'});\"";
		?>

		<div id="filemanager-navigation">
			<div class="buttons">
				<section>
					<h3><?php echo I18n :: locale('upload-file'); ?></h3>
					<form action="?action=uploadFile&token=<?php echo $system -> getToken().'&'.$url_params; ?>" method="post" enctype="multipart/form-data">
						<input type="file" name="target" />
						<input class="button-light" <?php echo $submit_button; ?> value="<?php echo I18n :: locale('upload'); ?>" />
					</form>						
				</section>
				<section>
					<h3><?php echo I18n :: locale('create-folder'); ?></h3>
					<form action="?action=createFolder&token=<?php echo $system -> getToken().'&'.$url_params; ?>" method="post">
						<input type="text" class="borderd" name="target" />
						<input class="button-light" <?php echo $submit_button; ?> value="<?php echo I18n :: locale('create'); ?>" />
					</form>
				</section>

				<input type="hidden" name="admin-panel-csrf-token" value="<?php echo $system -> getToken(); ?>" />
			</div>
			<div>
				<div class="pager-limit">
					<span><?php echo I18n :: locale('pager-limit'); ?></span>
					<select>
						<?php echo $filemanager -> pagination -> displayPagerLimits(AdminPanel :: PAGINATION_LIMITS); ?>
					</select>
					<input type="hidden" value="filemanager" />
				</div>
				<?php echo $filemanager -> pagination -> displayPagesAdmin(); ?>
			</div>
		</div>
	</div>
</div>
<?php
include $registry -> getSetting('IncludeAdminPath').'includes/footer.php';
?>