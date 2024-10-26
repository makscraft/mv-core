<?php $hide_css = (isset($hide_captcha) && $hide_captcha) ? " hidden" : ""; ?> 
<div class="line<?php echo $hide_css; ?>">
   <div class="name"><?php echo I18n::locale('captcha'); ?></div>
   <div class="captcha">
       <img src="<?php echo $registry -> getSetting('AdminPanelPath'); ?>login/captcha/" alt="<?php echo I18n::locale('captcha'); ?>" />
       <input type="text" name="captcha" autocomplete="off" />
   </div>   
</div>