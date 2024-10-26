<?php
include_once "../../config/autoload.php";

$system = new System();
$system -> detectModel();
$system -> user -> extraCheckModelRights($system -> model -> getModelClass(), "update");
$back_path = $registry -> getSetting("AdminPanelPath")."model/?model=".$system -> model -> getModelClass();
$csv_manager = new Csv();

include $registry -> getSetting('IncludeAdminPath')."includes/header.php";
?>
<div id="columns-wrapper">
   <div id="model-table">
      <h3 class="column-header"><?php echo I18n::locale("export-csv"); ?><span class="header-info"><?php echo $system -> model -> getName(); ?></span></h3>
      <p class="csv-notice"><?php echo I18n::locale('choose-fields-export-csv'); ?></p>
      <form id="csv-settings">
		    <?php echo $csv_manager -> displayFieldsLists($system -> model); ?>            
            <div class="clear">
               <input type="hidden" name="model" value="<?php echo $system -> model -> getModelClass(); ?>" />
            </div>
            <table>
               <tr>
                  <td class="setting-name"><?php echo I18n::locale('column-separator'); ?></td>
	                  <td class="setting-input">
	                    <select name="csv_separator">
                           <option value="semicolon"><?php echo I18n::locale('semicolon'); ?></option>
                           <option value="comma"><?php echo I18n::locale('comma'); ?></option>
                           <option value="tabulation"><?php echo I18n::locale('tabulation'); ?></option>
                        </select>
                     </td>
               </tr>
               <tr>
                  <td class="setting-name"><?php echo I18n::locale('file-encoding'); ?></td>
	                  <td class="setting-input">
	                     <select name="csv_encoding">
                            <option value="windows-1251">Windows1251</option>
                            <option value="utf-8">UTF-8</option>
	                     </select>
	                  </td>
	               </tr>
	               <tr>
	                  <td class="setting-name"><?php echo I18n::locale('first-line-headers'); ?></td>
                      <td class="setting-input"><input type="checkbox" name="csv_headers" checked="checked" /></td>
                  </tr>
            </table>
        </form>
        <input class="button-light" onclick="exportIntoCSV()" type="button" value="<?php echo I18n::locale('download-file'); ?>" />
        <input class="button-dark" onclick="location.href='<?php echo $back_path; ?>'" type="button" value="<?php echo I18n::locale('back'); ?>" />
    </div>
</div>
<?php
include $registry -> getSetting('IncludeAdminPath')."includes/footer.php";
?>