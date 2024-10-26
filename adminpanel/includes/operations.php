
<?php $controls_url = $registry -> getSetting('AdminPanelPath')."controls/"; ?>
<input class="button-list" type="button" id="operations-list-button" value="<?php echo I18n::locale('operations'); ?>" />
<ul id="operations-menu">	
   <li><a href="<?php echo $controls_url; ?>export-csv.php?model=<?php echo $system -> model -> getModelClass(); ?>"><?php echo I18n::locale('export-csv'); ?></a></li>
   <li><a href="<?php echo $controls_url; ?>import-csv.php?model=<?php echo $system -> model -> getModelClass(); ?>"><?php echo I18n::locale('import-csv'); ?></a></li>
</ul>
