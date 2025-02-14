<?php
include_once '../../config/autoload.php';

if(Http::fromGet('logout') === null && (new AdminPanel) -> checkAnyAuthorization())
   Http::redirect(Registry::get('AdminPanelPath'));

$registry = Registry::instance();
$i18n = I18n::instance();
$login = new Login();

if($region = Http::fromGet('region'))
{
   if(I18n::checkRegion($region))
	   I18n::saveRegion($region);

	$login -> reload('login/');
}
else
{
	$region = I18n::defineRegion();
	I18n::setRegion($region);
}

if(Http::fromGet('logout') === Login::getLogoutToken())
{	
   $login -> logoutUser() -> cancelRemember();
   
	set_time_limit(300);
	Filemanager::deleteOldFiles($registry -> getSetting('FilesPath').'tmp/');
	Filemanager::deleteOldFiles($registry -> getSetting('FilesPath').'tmp/admin/');
	Filemanager::deleteOldFiles($registry -> getSetting('FilesPath').'tmp/admin_multi/');
	Filemanager::deleteOldFiles($registry -> getSetting('FilesPath').'tmp/admin_record/');
	Filemanager::deleteOldFiles($registry -> getSetting('FilesPath').'tmp/redactor/');
	Filemanager::deleteOldFiles($registry -> getSetting('FilesPath').'tmp/filemanager/');
	Filemanager::makeModelsFilesCleanUp();

   $login -> reload('login/');
}

include $registry -> getSetting('IncludeAdminPath')."login/login-header.php";
?>
	<div id="container">
	   <div id="login-area">
           <div id="login-middle">
	           <div id="header"><?php echo I18n::locale('authorization'); ?></div>
	           <form method="post" class="login-form">
                  <?php echo FlashMessages::displayAndClear(); ?>
                  <div class="line">
                     <div class="name"><?php echo I18n::locale('login'); ?></div>
                     <input type="text" name="login" value="" autocomplete="off" />
                  </div>
                  <div class="line">
                     <div class="name"><?php echo I18n::locale('password'); ?></div>
                     <input class="password" type="password" name="password" autocomplete="off" />
                  </div>
                  <?php
                  	$hide_captcha = $login -> checkAllAttemptsFromIp() < Login::ATTEMPTS_NUMBER;

							include $registry -> getSetting('IncludeAdminPath')."login/captcha.php";
                  ?>
                  <div id="remember">
                     <input id="remember-login" type="checkbox" name="remember" />
                     <label for="remember-login"><?php echo I18n::locale('remember-me'); ?></label>                              
                  </div>
                  <div class="submit">
                     <input class="submit" type="button" value="<?php echo I18n::locale('login-action'); ?>" />
                     <input type="hidden" name="admin_login_csrf_token" value="<?php echo Login::getTokenCSRF(); ?>" />
                  </div>
                  <div class="remind">
                     <a href="<?php echo $registry -> getSetting('AdminPanelPath'); ?>login/remind.php" class="forgot-password"><?php echo I18n::locale('forgot-password'); ?></a>
                  </div>
                  <div class="line">
                     <div class="name"><?php echo I18n::locale('language'); ?></div>
                     <select name="region" id="select-login-region">
                         <?php echo I18n::displayRegionsSelect($region); ?>
                     </select>
                  </div>
	           </form>               
           </div>
	   </div>
	</div>
</body>
</html>