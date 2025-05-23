
MVobject.fileManagerAjaxPath = MVobject.adminPanelPath + '?ajax=filemanager';

$(document).ready(function()
{
	$('#filemanager-form a.action-delete').on('click', function()
	{
		let data = $(this).attr('data');

		if(!data)
			return;

		$('#filemanager-delete-any input[name="target"]').val(data);
		
		let target = $(this).parents('tr').find('td.name a').text();
		let message = $(this).hasClass('is-file') ? 'delete_file' : 'delete_folder';
		message = MVobject.locale(message, {name: target})

		$.modalWindow.open(message, {form: $('#filemanager-delete-any')});
	});
});