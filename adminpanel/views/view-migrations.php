<?php
I18n::setRegion('en');
$back_path = Registry::get('AdminPanelPath');
$path = $back_path.'?view=migrations';
$errors = false;

if($admin_panel -> user -> getId() != 1)
{
	$interal_error_text = I18n::locale('error-no-rights');
	include Registry::get('IncludeAdminPath')."controls/internal-error.php";
}

$migrations = new Migrations();
$migrations -> scanModels();
$number = $migrations -> getMigrationsQuantity();

if(Http::fromPost('migrations') && Http::fromPost('migrations_csrf_token'))
{
	if(Http::fromPost('migrations_csrf_token') != $migrations -> createAllMigrationsToken())
		$errors = I18n::locale("error-wrong-token");
	
	if(Http::fromPost('migrations') != "all")
		$key = $migrations -> checkMigrationKeyToken(Http::fromPost('migrations'));
	else
		$key = "all";
	
	if(!$key)
		$errors = I18n::locale("error-wrong-token");
	
	if(!$errors)
	{
		$migrations -> runMigrations($key);
        FlashMessages::add('success', I18n::locale('done-operation'));
	}
    else
        FlashMessages::add('error', $errors);

    Http::reload('view=migrations');
}

include Registry::get('IncludeAdminPath').'includes/header.php'; 
?>
<div id="columns-wrapper">
	<div id="model-form" class="one-column migrations-page">
		<h3 class="column-header with-navigation">Migrations
            <?php if($number): ?>
                <span class="header-info"><?php echo $number." migration".($number == 1 ? "" : "s"); ?> available</span>
                <span id="header-navigation">
                    <input class="button-light run-all-migrations" type="button" value="Run all migrations" />
                    <input class="button-dark button-back" type="button" onclick="location.href='<?php echo $back_path; ?>'" value="Cancel" />
                </span>
            <?php endif; ?>
		</h3>

        <?php echo FlashMessages::displayAndClear(); ?>
	    
        <div class="migrations">
		   <?php echo $migrations -> displayMigrationsList(); ?>
		</div>
		<form method="post" id="run-migrations-form" action="<?php echo $path; ?>">
		   <input type="hidden" name="migrations" id="current-migration-value" value="" />
           <input type="hidden" name="migrations_csrf_token" value="<?php echo $migrations -> createAllMigrationsToken(); ?>" />
		</form>
		<?php $css = $number ? "dark" : "light"; ?>
		<div class="migrations-bottom">
			<?php if($number): ?>
			<input class="button-light run-all-migrations" type="button" id="submit-button" value="Run all migrations" />
			<?php endif; ?>
			<input class="button-<?php echo $css; ?> button-back" type="button" onclick="location.href='<?php echo $back_path; ?>'" value="Cancel" />
		</div>
    </div>
</div>
<?php include Registry::get('IncludeAdminPath').'includes/footer.php'; ?>