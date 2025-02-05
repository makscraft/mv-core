<?php
if(isset($_POST['email']) && trim($_POST['email']))
	$email = htmlspecialchars(trim($_POST['email']), ENT_QUOTES);
else
	$email = '';
?>
<form method="post" class="login-form">
   <?php echo $login -> displayLoginErrors($errors); ?>
   <div class="line">
      <div class="name"><?php echo I18n::locale('email'); ?></div>
      <input type="text" name="email" value="<?php echo $email; ?>" autocomplete="off" />
   </div>
   <?php include $registry -> getSetting('IncludeAdminPath')."login/captcha.php"; ?>
  <div class="submit">
     <input class="submit" type="submit" value="<?php echo I18n::locale('restore'); ?>" />
     <input type="hidden" name="admin_login_csrf_token" value="<?php echo $login -> getTokenCSRF(); ?>" />
  </div>
  <div class="cancel">
     <a href="<?php echo $registry -> getSetting('AdminPanelPath'); ?>login/"><?php echo I18n::locale('cancel'); ?></a>
  </div>
</form>