<?php 
include_once '../../config/autoload.php';

$login = new Login();
$i18n = I18n::instance();
$region = I18n::defineRegion();
$i18n -> setRegion($region);
$reason = Http::fromGet('reason');

if($reason === 'js')
   $reason = 'JavaScript is disabled in this browser.';
else 
   $reason = I18n::locale('error-occurred');

include $registry -> getSetting('IncludeAdminPath').'login/login-header.php';
?>
   <div id="container">
      <div id="login-area">
           <div id="login-middle">
              <div id="header"><?php echo I18n::locale('caution'); ?></div>
              <div class="errors">
                  <p><?php echo $reason; ?></p>
              </div>
              <a href="<?php echo $registry -> getSetting('AdminPanelPath'); ?>login/" class="submit"><?php echo I18n::locale('to-authorization-page'); ?></a>
           </div>
      </div>
   </div>
</body>
</html>