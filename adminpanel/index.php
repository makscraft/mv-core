<?php
include_once "../config/autoload.php";
$system = new System();

include $registry -> getSetting('IncludeAdminPath')."includes/header.php";
?>
<div class="index-models">
   <?php echo $system -> menu -> displayModelsMenu(); ?>
</div>
<div id="index-icons">
	<ul>
		<li class="users">
	      <a href="<?php echo $registry -> getSetting('AdminPanelPath'); ?>model/?model=users">
            <span><?php echo I18n::locale("users"); ?></span>
            <?php echo I18n::locale("index-users-icon"); ?>
          </a>
	    </li>
		  <li class="garbage">
	      <a href="<?php echo $registry -> getSetting('AdminPanelPath'); ?>model/?model=garbage">
            <span><?php echo I18n::locale("garbage"); ?></span>
            <?php echo I18n::locale("index-garbage-icon"); ?>
          </a>
        </li>
		  <li class="history">
	      <a href="<?php echo $registry -> getSetting('AdminPanelPath'); ?>model/?model=log">
            <span><?php echo I18n::locale("logs"); ?></span>
            <?php echo I18n::locale("index-history-icon"); ?>
          </a>
        </li>
		  <li class="filemanager">
	      <a href="<?php echo $registry -> getSetting('AdminPanelPath'); ?>controls/filemanager.php">
            <span><?php echo I18n::locale("file-manager"); ?></span>
            <?php echo I18n::locale("index-file-manager-icon"); ?>
          </a>
	    </li>
	</ul>
</div>
<?php
include $registry -> getSetting('IncludeAdminPath')."includes/footer.php";
?>