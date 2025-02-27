<?php include Registry::get('IncludeAdminPath').'includes/header.php'; ?>
<div class="index-models">
   <?php echo (new Menu) -> displayModelsMenu(); ?>
</div>
<div id="index-icons">
	<ul>
		<li class="users">
	      <a href="<?php echo Registry::get('AdminPanelPath'); ?>?model=users&action=index">
            <span><?php echo I18n::locale("users"); ?></span>
            <?php echo I18n::locale("index-users-icon"); ?>
          </a>
	    </li>
		  <li class="garbage">
	      <a href="<?php echo Registry::get('AdminPanelPath'); ?>?model=garbage&action=index">
            <span><?php echo I18n::locale("garbage"); ?></span>
            <?php echo I18n::locale("index-garbage-icon"); ?>
          </a>
        </li>
		  <li class="history">
	      <a href="<?php echo Registry::get('AdminPanelPath'); ?>?model=log&action=index">
            <span><?php echo I18n::locale("logs"); ?></span>
            <?php echo I18n::locale("index-history-icon"); ?>
          </a>
        </li>
		  <li class="filemanager">
	      <a href="<?php echo Registry::get('AdminPanelPath'); ?>?view=filemanager">
            <span><?php echo I18n::locale("file-manager"); ?></span>
            <?php echo I18n::locale("index-file-manager-icon"); ?>
          </a>
	    </li>
	</ul>
</div>
<?php include Registry::get('IncludeAdminPath').'includes/footer.php'; ?>