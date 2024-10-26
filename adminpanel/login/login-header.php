<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="robots" content="noindex, nofollow" />
<title><?php echo I18n::locale("mv"); ?></title>
<?php $cache_drop = CacheMedia::getDropMark(); ?>
<link rel="stylesheet" type="text/css" href="<?php echo $registry -> getSetting("AdminPanelPath"); ?>interface/css/style-login.css<?php echo $cache_drop; ?>" />

<link rel="icon" href="<?php echo $registry -> getSetting("AdminPanelPath"); ?>interface/images/favicon.svg" type="image/x-icon" />
<link rel="shortcut icon" href="<?php echo $registry -> getSetting("AdminPanelPath"); ?>interface/images/favicon.svg" type="image/x-icon" />

<?php
$is_loading_page = preg_match("/\/loading\.php/", $_SERVER["REQUEST_URI"]);

if($is_loading_page)
{
	$url = $registry -> getSetting("AdminPanelPath");
	
	if(isset($_SESSION["login-back-url"]) && $_SESSION["login-back-url"])
	{
		$url .= $_SESSION["login-back-url"];
		unset($_SESSION["login-back-url"]);
	}

	echo "<meta http-equiv=\"refresh\" content=\"1; URL=".$url."\" />\n";
}
	
if(stripos($_SERVER["REQUEST_URI"], "/login/error.php") === false)
	include $registry -> getSetting("IncludeAdminPath")."includes/noscript.php";

if(!$is_loading_page):
?>
	<script type="text/javascript">
		let adminPanelPath = "<?php echo $registry -> getSetting("AdminPanelPath"); ?>";
	</script>
	<script type="text/javascript" src="<?php echo $registry -> getSetting("AdminPanelPath"); ?>interface/js/jquery.js"></script>
	<script type="text/javascript" src="<?php echo $registry -> getSetting("AdminPanelPath"); ?>interface/js/login.js<?php echo $cache_drop; ?>"></script>
	<script type="text/javascript">
		$(document).ready(function() { $("form div.submit").append("<input type=\"hidden\" name=\"js-token\" value=\"<?php echo Login::getJavaScriptToken(); ?>\" />"); });
	</script>
	<?php if(!isset($_SESSION["login"]["ajax-token"])): ?>
		<script type="text/javascript"> $(document).ready(function(){ $.post(adminPanelPath + "ajax/login.php", {"data": "<?php echo Login::getAjaxInitialToken(); ?>"}); }); </script>
	<?php endif; ?>
<?php endif; ?>
</head>
<body>