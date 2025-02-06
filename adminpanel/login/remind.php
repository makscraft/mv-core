<?php
include_once '../../config/autoload.php';

if((new AdminPanel) -> checkAnyAuthorization())
   Http::redirect(Registry::get('AdminPanelPath'));

$login = new Login();
$i18n = I18n::instance();
$region = I18n::defineRegion();
I18n::setRegion($region);

$errors = [];

if(Http::isPostRequest())
{
	if(!$email = Http::fromPost('email'))
		$errors[] = I18n::locale('complete-email').'.';
	else if(!preg_match('/^[-a-z0-9_\.]+@[-a-z0-9_\.]+\.[a-z]{2,5}$/i', $email))
		$errors[] = I18n::locale('error-email-format', ['field' => 'E-mail']);

	if(!Http::fromPost('captcha'))
		$errors[] = I18n::locale('complete-captcha').'.';
	else if(Http::fromPost('captcha') !== Session::get('captcha'))
		$errors[] = I18n::locale('wrong-captcha').'.';

	if(!count($errors))
	{
		if(Session::get('ajax-token') != Login::getAjaxInitialToken())
			$errors[] = I18n::locale('error-wrong-token');
		else if(Http::fromPost('js_token') != Login::getJavaScriptToken())
			$errors[] = I18n::locale('error-wrong-token');
		else if(Http::fromPost('admin_login_csrf_token') != Login::getTokenCSRF())
			$errors[] = I18n::locale('error-wrong-token');
	}

	if(!count($errors))
	{
		$admin_data = $login -> checkUserEmail($email);
		
		if($admin_data && is_array($admin_data) && isset($admin_data['id']))
		{
			if($login -> sendUserPassword($admin_data))
			{
				FlashMessages::add('success', I18n::locale('change-password-sent'));
				$login -> reload('login/remind.php');
			}
			else
				$errors[] = I18n::locale('error-failed');
		}	
		else
			$errors[] = I18n::locale('not-user-email');
	}
}

include $registry -> getSetting('IncludeAdminPath').'login/login-header.php';
?>
	<div id="container">
	   <div id="login-area">
           <div id="login-top"></div>
           <div id="login-middle">
	           <div id="header"><?php echo I18n::locale('password-restore'); ?></div>
               <?php
			   		if(FlashMessages::hasAny())
					{
						echo FlashMessages::displayAndClear();
						echo "<a class=\"submit\" href=\"".$registry -> getSetting('AdminPanelPath')."login/\">".I18n::locale('back')."</a>";
					}
					else
						include $registry -> getSetting('IncludeAdminPath').'login/remind-form.php';
               ?>
           </div>
           <div id="login-bottom"></div>
	   </div>
	</div>
</body>
</html>