
<div class="manage-filters" id="model-<?php echo $model -> getModelClass(); ?>">
	<p><?php echo I18n::locale('manage-filters'); ?></p>
	<select id="add-filter">
	   <option value=""><?php echo I18n::locale('add'); ?></option>
       <?php 
			$html_selects = $model -> filter -> displayAdminFiltersFieldsSelects($default_filters, 
																				 $show_empty_default_filters);
			echo $html_selects['add'];
       ?>
	</select>
	<select id="remove-filter">
	   <option value=""><?php echo I18n::locale('delete'); ?></option>
       <?php echo $html_selects['remove']; ?>
	</select>
</div>