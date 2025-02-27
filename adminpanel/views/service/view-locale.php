<?php
if(Http::fromGet('region') === I18n::getRegion())
{
	$keys = array("delete-one", "delete-many", "delete-one-finally", "delete-many-finally", "restore-one", 
				  "restore-many", "update-many-bool", "update-many-enum", "update-many-m2m-add", "update-many-m2m-remove", 
				  "sort-by-column", "all-parents-filter", "parent-filter-needed", "error-data-transfer", "no-rights", 
				  "delete-files", "delete-file", "delete-folder", "rename-file", "rename-folder", "add-image-comment", 
				  "not-uploaded-files", "select-fields", "select-csv-file", "quick-edit-limit", "choose-skin",
				  "search-by-name", "add-edit-comment", "move-left", "move-right", "move-first", "move-last", "delete",
				  "not-defined", "no-images", "cancel");
	
	$data = [];
	
	header("Content-Type: application/javascript; charset=utf-8");

	echo "MVobject.region = '".I18n::getRegion()."';\n\n";
	echo "MVobject.localePackage = {\n";
	
	foreach($keys as $key)
		$data[] = "\t".str_replace("-", "_", $key).': '.'"'.I18n::locale($key).'"';
	
	echo implode(",\n", $data);
	echo "\n};";
}