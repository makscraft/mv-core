$(document).ready(function() //Makes name of module check whole current row of checkboxes after click
{	
    $('#rights-table tr:gt(0)').each(function()
	{
		$(this).find('td:first').on('click', function()
	    {
			 let all_boxes = $(this).parent().find(':checkbox');
				 
			 let boxes_total = $(this).parent().find(':checkbox').size();
			 let boxes_checked = $(this).parent().find(':checkbox:checked').size();
				 
			 if(boxes_total > boxes_checked) //If all checked we remove check
				 all_boxes.attr('checked', true);
			 else //If not all are checked we check all at one time
				 all_boxes.removeAttr('checked');

			$('#rights-table :checkbox:first').change();
		});
	});
	
    let count = 1;
    
	//Makes name of action check whole current column of checkboxes after click
	$('#rights-table tr:first th:gt(0)').each(function()
	{
		this.ind = count ++; //The number of column
		
		$(this).click(function()
		{
			let rows = $('#rights-table tr:gt(0)'); //Rows of modules with checkboxes
			let position = this.ind; //Current column (action)
			let boxes_total = 0;
			let boxes_checked = 0;
			
			rows.each(function() //Counts checked checkboxes in the row
			{
				boxes_total += $(this).find('td:eq(' + position + '):has(:checkbox)').size();
				boxes_checked += $(this).find('td:eq(' + position + '):has(:checkbox:checked)').size();
			});
			
			rows.each(function() //If not all checked we check all, othewise we uncheck all
			{
				let box = $(this).find('td:eq(' + position + ') :checkbox');
				
				if(boxes_total > boxes_checked)
					box.attr('checked', true);
				else 
					box.removeAttr('checked');
			});

			$('#rights-table :checkbox:first').change();
		});
	});

	//Packs rights into common json value
	$('#rights-table :checkbox').on('change', function()
	{
		let values = {};

		$('#rights-table :checkbox').each(function()
		{
			values[$(this).attr('data')] = $(this).is(':checked');
		});

		$('form.model-elements-form input[name="admin_rights_list"]').val(JSON.stringify(values));
	});
});