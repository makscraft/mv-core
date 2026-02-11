<?php
/**
 * WW editor class, created to add visual editor for the textareas in admin panel.
 */
class Editor
{
	/**
	 * Makes sure we already added base js scripts on the page.
	 * @var bool
	 */
   	private static $instance = false;

	static public function createSecurityToken()
	{
		$token = $_SERVER['HTTP_USER_AGENT'].Registry::get('SecretCode').Http::getIpAddress();
		return Service::createHash($token, 'random');
	}

	static public function run(string $id, int $height)
	{
		$html = '';

		$region = Registry::get('Region');
		$region = ($region == 'en' || $region == 'am' || $region == 'us') ? 'en' : $region;
		$region_path = Registry::get('AdminPanelPath').'i18n/'.$region.'/';
		$upload_path = Registry::get('AdminPanelPath').'?ajax=upload-editor&type=image&token='.Editor::createSecurityToken();
  
		if(!self::$instance)
		{
			$path = Registry::get("AdminPanelPath")."interface/ckeditor/";
			$html .= "<script type=\"text/javascript\"> const editorSecurityToken = '".Editor::createSecurityToken()."'; </script>\n";
			$html .= "<script type=\"text/javascript\" src=\"".$path."ckeditor.js\"></script>\n";
			$html .= "<script type=\"text/javascript\" src=\"".$path."uploaders.js\"></script>\n";
			$html .= "<script type=\"text/javascript\" src=\"".$region_path."ckeditor.".$region.".js\"></script>\n";

			self::$instance = true;
		}

		$html .= "<style type=\"text/css\"> textarea#".$id." + div .ck-editor__editable_inline{min-height: ".$height."px; max-height: 800px} </style>\n";
		$html .= "<script type=\"text/javascript\">
					ClassicEditor
					.create(document.querySelector('#".$id."'), {

						removePlugins: ['ImageInsert', 'MediaEmbedToolbar'],
						extraPlugins: [MVframeworkFileUploadPlugin],
						toolbar: {
							items: ['sourceEditing', 'undo', 'redo', '|', 'heading', '|', 'bold', 'italic', 
									'underline', 'bulletedList', 'numberedList', 'blockQuote', 'link', 
									'fontColor', 'uploadImage', 'fileUploadButton', 'insertTable', 'mediaEmbed', 'code']
						},
						heading: {
							options: [
								{ model: 'paragraph', title: 'Paragraph' },
								{ model: 'heading1', view: 'h1', title: 'Heading 1' },
								{ model: 'heading2', view: 'h2', title: 'Heading 2' },
								{ model: 'heading3', view: 'h3', title: 'Heading 3' }
							]
						},
						image: { insert: {type: 'side'} },
						mediaEmbed: { previewsInData: true },
						language: '".$region."',
						htmlSupport: { allow: [{classes: true, styles: true, attributes: true}] },
						link: {
							decorators: {
								openInNewTab: {
									mode: 'manual',
									label: 'Open in a new tab',
									defaultValue: false,
									attributes: { target: '_blank', rel: 'noopener noreferrer' }
								}
							}
						},
						simpleUpload: {
							uploadUrl: '".$upload_path."',
							withCredentials: true
						}
					})
					.then(editor => {
						window.editor = editor;
					})
					.catch(error => {
						console.error(error);
					});
				  </script>\n";   
		
		return $html;
	}
}