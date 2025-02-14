<?php
include '../../config/autoload.php';

Http::isAjaxRequest('post', true);
$login = new Login();

I18n::setRegion(I18n::defineRegion());

if($token = Http::fromPost('data'))
{
	Session::set('ajax-token', preg_replace('/\W/', '', $token));
	exit();
}

if(Http::requestHas('login', 'password'))
{
	Session::start('admin_panel_login');
	$errors = [];
	$result = ['errors' => '', 'action' => '', 'captcha' => false];

	$login_attempts = $login -> checkAllAttemptsFromIp();
	$login_filled = htmlspecialchars(Http::fromPost('login', ''), ENT_QUOTES);
	$password_filled = htmlspecialchars(Http::fromPost('password', ''), ENT_QUOTES);

	if(!$login_filled)
		$errors[] = I18n::locale('complete-login').'.';
	
	if(!$password_filled)
		$errors[] = I18n::locale('complete-password').'.';

	if($login_attempts >= Login::ATTEMPTS_NUMBER)
	{
		if(!Http::fromPost('captcha'))
			$errors[] = I18n::locale('complete-captcha').'.';
		else if(Http::fromPost('captcha') !== Session::get('captcha'))
			$errors[] = I18n::locale('wrong-captcha').'.';

		sleep(1);
	}

	if($login_attempts + 1 >= Login::ATTEMPTS_NUMBER)
		$result['captcha'] = time();

	if($login_filled && $password_filled && !count($errors))
	{
		if($login_attempts < Login::ATTEMPTS_NUMBER)
			$login -> addNewLoginAttempt($login_filled);

		if(Session::get('ajax-token') != Login::getAjaxInitialToken())
		{
			$errors[] = I18n::locale('error-wrong-token');
			$result['action'] = 'reload';
		}

		if(Http::fromPost('js_token') != Login::getJavaScriptToken())
		{
			$errors[] = I18n::locale('error-wrong-token');
			$result['action'] = 'reload';
		}

		if(Http::fromPost('admin_login_csrf_token') != Login::getTokenCSRF())
		{
			$errors[] = I18n::locale('error-wrong-token');
			$result['action'] = 'reload';
		}

		if(Http::isLocalHost() && Http::fromPost('test_token_check', '') !== '')
		{
			$string = Registry::get('APP_FOLDER').Registry::get('APP_TOKEN');
			
			if(Service::checkHash($string, Http::fromPost('test_token_check')))
				$errors = [];
		}
		
		if(!count($errors))
		{
			if($id = $login -> loginUser($login_filled, $password_filled))
			{
				if(Http::fromPost('remember'))
					$login -> rememberUser($id);

				Session::start('admin_panel_login');
			
				if($back = Session::get('login-back-url'))
				{
					Session::start('admin_panel');
					Session::set('login-back-url', $back);
				}

				Session::destroy('admin_panel_login');
			
				$user = new User($id);
				$user -> updateSetting('region', I18n::defineRegion());
				
				$result['action'] = 'start';
			}
			else
				$errors[] = I18n::locale('login-failed');
		}
	}

	if(count($errors))
		$result['errors'] = $login -> displayLoginErrors($errors);

	Http::responseJson($result);
}