<?php
$admin_panel -> user -> extraCheckModelRights('file_manager', 'read');

$filemanager = new Filemanager();
$filemanager -> setUser($admin_panel -> user) -> setToken($admin_panel -> createCSRFToken());
$filemanager -> pagination -> setUrlParams('view=filemanager');
$url_params = $filemanager -> pagination -> getUrlParams();

$action_complete = false;

if($navigation = Http::fromGet('navigation'))
    if($filemanager -> navigate(strval($navigation)))
	    $action_complete = true;

if($limit = Http::fromGet('pager-limit'))
    if($admin_panel -> savePaginationLimit($limit))
        $action_complete = true;

$allowed_actions = ['createFolder', 'uploadFile', 'deleteAction'];

if(isset($_GET['action'], $_POST['csrf_token']) && in_array($_GET['action'], $allowed_actions))
	if(Http::fromPost('csrf_token') === $admin_panel -> createCSRFToken())
	{
		$action = Http::fromGet('action');
		$parameter = Http::fromPost('target', 'target');
		$permission = ($action == 'createFolder' || $action == 'uploadFile') ? 'create' : 'delete';

		$system -> user -> extraCheckModelRights('file_manager', $permission);
		$result = $filemanager -> $action($parameter);

		if($result['message'] !== '')
			FlashMessages::add($result['success'] ? 'success' : 'error', $result['message']);

		$action_complete = true;
	}

if($action_complete)
    Http::reload('view=filemanager'.($url_params ? '&'.$url_params : ''));

CacheMedia::addJavaScriptFile(Registry::get('AdminFolder').'/interface/js/file-manager.js');
CacheMedia::addCssFile(Registry::get('AdminFolder').'/interface/css/style-filemanager.css');
$to_display = $filemanager -> prepareFilesForDisplay();


include Registry::get('IncludeAdminPath').'includes/header.php';
?>
<div id="columns-wrapper">
    <div id="filemanager-area">
		<h3 class="column-header"><?php echo I18n::locale('file-manager'); ?></h3>
		<?php echo FlashMessages::displayAndClear(); ?>
		<div id="filemanager-path">
			<?php echo $filemanager -> displayCurrentPath();  ?>
		</div>
		<form id="filemanager-form" method="post">
			<table class="model-table filemanager">
				<tr>
					<th class="middle"><?php echo I18n::locale('name'); ?></th>
					<th class="middle"><?php echo I18n::locale('size'); ?></th>
					<th class="middle"><?php echo I18n::locale('file-params'); ?></th>
					<th class="middle"><?php echo I18n::locale('last-change'); ?></th>
					<th class="actions"><?php echo I18n::locale('operations'); ?></th>
				</tr>
				<?php echo $filemanager -> display($to_display); ?>
			</table>
		</form>

		<?php 
			if($admin_panel -> user -> checkModelRights('file_manager', 'create'))
				$submit_button = "type=\"submit\"";
			else
				$submit_button = "type=\"button\" onclick=\"$.modalWindow.open(MVobject.locale('no_rights'), {css_class: 'alert'});\"";
		?>

		<div id="filemanager-navigation">
			<div class="forms">
				<section>
					<h3><?php echo I18n::locale('upload-file'); ?></h3>
					<form action="?view=filemanager&action=uploadFile&<?php echo $url_params; ?>" method="post" enctype="multipart/form-data">
						<input type="file" name="target" />
						<input type="hidden" name="csrf_token" value="<?php echo $admin_panel -> createCSRFToken(); ?>" />
						<input class="button-light" <?php echo $submit_button; ?> value="<?php echo I18n::locale('upload'); ?>" />
					</form>						
				</section>
				<section>
					<h3><?php echo I18n::locale('create-folder'); ?></h3>
					<form action="?view=filemanager&action=createFolder&<?php echo $url_params; ?>" method="post">
						<input type="text" class="borderd" name="target" />
						<input type="hidden" name="csrf_token" value="<?php echo $admin_panel -> createCSRFToken(); ?>" />
						<input class="button-light" <?php echo $submit_button; ?> value="<?php echo I18n::locale('create'); ?>" />
					</form>
				</section>
				<section>
					<form action="?view=filemanager&action=deleteAction&<?php echo $url_params; ?>" method="post" id="filemanager-delete-any">
						<input type="hidden" name="target" value="" />
						<input type="hidden" name="csrf_token" value="<?php echo $admin_panel -> createCSRFToken(); ?>" />
					</form>
				</section>
			</div>
			<div class="pagination">
				<div class="pager-limit">
					<span><?php echo I18n::locale('pager-limit'); ?></span>
					<select>
						<?php echo $filemanager -> pagination -> displayPagerLimits(AdminPanel::PAGINATION_LIMITS); ?>
					</select>
					<input type="hidden" value="filemanager" />
				</div>
				<?php echo $filemanager -> pagination -> displayPagesAdmin(); ?>
			</div>
		</div>
	</div>
</div>
<?php
include Registry::get('IncludeAdminPath').'includes/footer.php';
?>