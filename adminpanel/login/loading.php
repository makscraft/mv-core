<?php 
include_once '../../config/autoload.php';
Session::start('admin_panel');
$i18n = I18n::instance();
$region = I18n::defineRegion();
$i18n -> setRegion($region);

include $registry -> getSetting("IncludeAdminPath")."login/login-header.php";
?>
   <div id="container">
      <div id="login-area">
         <div id="login-middle">
            <div id="header"><?php echo I18n::locale('get-ready'); ?></div>
         </div>
      </div>
   </div>
</body>
</html>