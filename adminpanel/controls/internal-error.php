<?php
$registry = Registry::instance();
$path = Registry::get("AdminPanelPath");

include Registry::get('IncludeAdminPath')."includes/header.php";
?>
<div id="columns-wrapper">
   <div id="model-table">
      <div class="column-inner">
         <h3 class="column-header"><?php echo I18n::locale("caution"); ?></h3>
            <div class="flash-message info">
               <div><?php echo $interal_error_text; ?></div>
            </div>
            <input class="button-light" onclick="location.href='<?php echo $path; ?>'" type="button" value="<?php echo I18n::locale('back'); ?>" />
       </div>
   </div>
</div>
<?php
include Registry::get('IncludeAdminPath')."includes/footer.php";
?>