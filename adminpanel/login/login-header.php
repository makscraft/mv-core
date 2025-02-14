<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="robots" content="noindex, nofollow" />
<meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0">
<title><?php echo I18n::locale("mv"); ?></title>
<?php $cache_drop = CacheMedia::getDropMark(); ?>
<link rel="stylesheet" type="text/css" href="<?php echo Registry::get("AdminPanelPath"); ?>interface/css/style-login.css<?php echo $cache_drop; ?>" />

<link rel="icon" href="<?php echo Registry::get("AdminPanelPath"); ?>interface/images/favicon.svg" type="image/x-icon" />
<link rel="shortcut icon" href="<?php echo Registry::get("AdminPanelPath"); ?>interface/images/favicon.svg" type="image/x-icon" />

<?php
$is_loading_page = preg_match("/\/loading\.php/", $_SERVER["REQUEST_URI"]);

if($is_loading_page)
{
	$url = Registry::get('AdminPanelPath');
	
	if($url_back = Session::get('login-back-url'))
	{
		$url .= $url_back;
		Session::remove('login-back-url');
	}

	echo "<meta http-equiv=\"refresh\" content=\"1; URL=".$url."\" />\n";
}
	
if(stripos($_SERVER["REQUEST_URI"], "/login/error.php") === false)
	include Registry::get("IncludeAdminPath")."includes/noscript.php";

if(!$is_loading_page):
?>
	<script type="text/javascript">
		const adminPanelPath = "<?php echo Registry::get("AdminPanelPath"); ?>";
	</script>
	<script type="text/javascript" src="<?php echo Registry::get("AdminPanelPath"); ?>interface/js/jquery.js"></script>
	<script type="text/javascript" src="<?php echo Registry::get("AdminPanelPath"); ?>interface/js/login.js<?php echo $cache_drop; ?>"></script>
	<script type="text/javascript">
		$(document).ready(function() { $("form div.submit").append('<input type="hidden" name="js_token" value="<?php echo Login::getJavaScriptToken(); ?>" />'); });
	</script>
	<?php if(!Session::get('ajax-token')): ?>
		<script type="text/javascript"> $(document).ready(function(){ $.post(adminPanelPath + "login/ajax.php", {"data": "<?php echo Login::getAjaxInitialToken(); ?>"}); }); </script>
	<?php endif; ?>
<?php endif; ?>
</head>
<body>