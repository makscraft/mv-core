<?php
if($limit_setting = $admin_panel -> getUserSessionSetting('versions-pager-limit'))
	$admin_panel -> versions -> pager -> setLimit($limit_setting);

$versions_page = (int) (Http::fromRequest('versions-page') ?? 1);
$admin_panel -> versions -> pager -> definePage($versions_page);
$versions_limit = $admin_panel -> versions -> getLimit();
?>

<table id="versions-table">
	<?php
		if($versions_limit)
			echo $admin_panel -> versions -> display(); 
	?>
</table>

<div id="versions-limit"<?php echo $versions_limit ? "" : ' class="versions-disabled"'; ?>>
	<?php echo $versions_limit ? I18n::locale("versions-limit").": ".$versions_limit : I18n::locale("versions-disabled"); ?>
</div>

<div id="versions-pager">
	<div class="limit">
      	<?php
			if($versions_limit)
			{
				echo "<span>".I18n::locale('pager-limit')."</span>\n";
				echo "<select>\n".$admin_panel -> versions -> pager -> displayPagerLimits([5,10,15,20,25,30,50,100])."</select>\n";
			}
      	?>
	</div>
	<?php
		if($versions_limit)
			echo $admin_panel -> versions -> displayPager(); 
	?>
</div>