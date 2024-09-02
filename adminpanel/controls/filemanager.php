<?php
include_once '../../config/autoload.php';

$system = new System();
$admin_panel = new AdminPanel();
$system -> user -> extraCheckModelRights('file_manager', 'read');

$filemanager = new Filemanager();
$filemanager -> setUser($system -> user) -> setToken($system -> getToken());
$url_params = $filemanager -> pagination -> getUrlParams();

$allowed_actions = ['create-folder','upload-file','delete-many','delete-file', 'delete-folder'];

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
			<div id="filemanager-path">
				<?php echo $filemanager -> displayCurrentPath();  ?>
			</div>
			<form id="filemanager-form" method="post">
				<table class="model-table filemanager">
					<tr>
						<?php /* <th class="check-all"><input type="checkbox" /></th> */ ?>
						<th class="middle"><?php echo I18n :: locale('name'); ?></th>
						<th class="middle"><?php echo I18n :: locale('size'); ?></th>
						<th class="middle"><?php echo I18n :: locale('last-change'); ?></th>
						<th class="middle"><?php echo I18n :: locale('file-params'); ?></th>
						<th class="middle"><?php echo I18n :: locale('operations'); ?></th>
					</tr>
                    <?php echo $filemanager -> display($to_display); ?>
				</table>
			</form>
			<div id="navigation">
		                 <?php 
		                     if($system -> user -> checkModelRights("file_manager", "delete"))
		                        $submit_button = " onclick=\"dialogs.showDeleteFilesMessage()\"";
		                     else
		                        $submit_button = " onclick=\"$.modalWindow.open(mVobject.locale('no_rights'), {css_class: 'alert'});\"";
		                 ?>
	                     <div class="buttons">  					 							
                            <input type="hidden" name="admin-panel-csrf-token" value="<?php echo $system -> getToken(); ?>" />
	                     </div>
	                     <div class="pager-limit">
	                        <span><?php echo I18n :: locale('pager-limit'); ?></span>
					        <select>
					            <?php echo $filemanager -> pagination -> displayPagerLimits(AdminPanel :: PAGINATION_LIMITS); ?>
					        </select>
	                        <input type="hidden" value="filemanager" />
	                     </div>
	                     <?php echo $filemanager -> pagination -> displayPagesAdmin(); ?>
	                </div>

					<?php 
                     if($system -> user -> checkModelRights("file_manager", "create"))
                         $submit_button = "type=\"submit\"";
                     else
                         $submit_button = "type=\"button\" onclick=\"$.modalWindow.open(mVobject.locale('no_rights'), {css_class: 'alert'});\"";
                ?>

			<div class="filameneger-actions">

			<h3><?php echo I18n :: locale('upload-file'); ?></h3>
                     <form action="?upload-file&token=<?php echo $system -> getToken().$url_params; ?>" method="post" enctype="multipart/form-data">
                        <div>
                           <input type="file" name="new-file" />
                           <p><input class="button-light" <?php echo $submit_button; ?> value="<?php echo I18n :: locale('upload'); ?>" /></p>
                        </div>
                     </form>
                     <h3><?php echo I18n :: locale('create-folder'); ?></h3>
                     <form action="?create-folder&token=<?php echo $system -> getToken().$url_params; ?>" method="post">
                        <div>
                           <input type="text" class="borderd" name="new-folder" />
                           <p><input class="button-light" <?php echo $submit_button; ?> value="<?php echo I18n :: locale('create'); ?>" /></p>
                        </div>
                     </form>   			
			</div>
	</div>
</div>
		


<?php
include $registry -> getSetting('IncludeAdminPath').'includes/footer.php';
?>