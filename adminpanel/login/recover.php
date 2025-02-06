<?php
include_once '../../config/autoload.php';

if((new AdminPanel) -> checkAnyAuthorization())
   Http::redirect(Registry::get('AdminPanelPath'));

sleep(1);

$login = new Login();
$i18n = I18n::instance();
$region = I18n::defineRegion();
$i18n -> setRegion($region);

if(Http::requestHas('code', 'token'))
{
	if(!$login -> checkNewPasswordParams(Http::fromGet('code'), Http::fromGet('token')))
	{
		FlashMessages::add('error', I18n::locale('password-not-confirmed'));
		$login -> reload('login/');
	}
	else
		$login -> reload('login/recover.php');
}

if(!Session::get('change-password'))
	$login -> reload('login/');

$fields = [
	['{password}', 'password', 'password', [
			'required' => true,
			'letters_required' => true,
			'digits_required' => true
		]
	],
	['{password-repeat}', 'password', 'password_repeat', [
			'letters_required' => true,
			'digits_required' => true
		]
	]
];

$form = new Form($fields);
$errors = [];

if(Http::isPostRequest())
{
	$form -> getDataFromPost() -> validate();
	
	foreach($form -> getErrors() as $error)
		$errors[] = $form -> displayOneError($error);
	
	if(!count($errors) && $form -> password != $form -> password_repeat)
		$errors[] = I18n::locale('passwords-must-match');
	
	if(Session::get('ajax-token') != Login::getAjaxInitialToken())
		$errors[] = I18n::locale('error-wrong-token');
	else if(Http::fromPost('js_token') != Login::getJavaScriptToken())
		$errors[] = I18n::locale('error-wrong-token');
	else if(Http::fromPost('admin_login_csrf_token') != Login::getTokenCSRF())
		$errors[] = I18n::locale('error-wrong-token');
	
	if(!count($errors))
	{
		$login -> saveNewPassword(Session::get('change-password'), $form -> password);
		Session::remove('change-password');
		FlashMessages::add('success', I18n::locale('password-confirmed'));
		 
		$login -> reload('login/');
	}
}

include $registry -> getSetting('IncludeAdminPath')."login/login-header.php";
?>
	<div id="container">
	   <div id="login-area">
           <div id="login-top"></div>
           <div id="login-middle">
	           <div id="header"><?php echo I18n::locale('password-restore'); ?></div>
               <form method="post" class="login-form">
                  <?php echo $login -> displayLoginErrors($errors); ?>
                  <div class="line">
                     <div class="name"><?php echo I18n::locale('new-password'); ?></div>
                     <input class="password" type="password" name="password" value="<?php echo $form -> password; ?>" autocomplete="off" />
                  </div>
                  <div class="line">
                     <div class="name"><?php echo I18n::locale('password-repeat'); ?></div>
                     <input class="password" type="password" name="password_repeat" value="<?php echo $form -> password_repeat; ?>" autocomplete="off" />
                  </div>
                  <div class="submit">
                     <input class="submit" type="submit" value="<?php echo I18n::locale('restore'); ?>" />
                     <input type="hidden" name="admin_login_csrf_token" value="<?php echo $login -> getTokenCSRF(); ?>" />
                  </div>
                  <div class="cancel">
                     <a href="<?php echo $registry -> getSetting('AdminPanelPath'); ?>login/"><?php echo I18n::locale('cancel'); ?></a>
                  </div>               
               </form>
           </div>
           <div id="login-bottom"></div>
	   </div>
	</div>
</body>
</html>