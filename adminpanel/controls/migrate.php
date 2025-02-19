<?php
include_once "../../config/autoload.php";
$system = new System();

$path = $system -> registry -> getSetting('AdminPanelPath')."controls/migrate.php";
$back_path = $registry -> getSetting('AdminPanelPath');
$errors = false;

if($system -> user -> getId() != 1)
{
	$system -> error = I18n::locale("error-no-rights");
	include $system -> registry -> getSetting("IncludeAdminPath")."controls/internal-error.php";
}

$migrations = new Migrations();
$migrations -> scanModels();
$number = $migrations -> getMigrationsQuantity();

if(isset($_POST["migrations"], $_POST["migrations-csrf-token"]))
{
	I18n::setRegion("en");
	
	if($_POST["migrations-csrf-token"] != $migrations -> createAllMigrationsToken())
		$errors = I18n::locale("error-wrong-token");
	
	if($_POST["migrations"] != "all")
		$key = $migrations -> checkMigrationKeyToken($_POST["migrations"]);
	else
		$key = "all";
	
	if(!$key)
		$errors = I18n::locale("error-wrong-token");
	
	if(!$errors)
	{
		$migrations -> runMigrations($key);
		
		$_SESSION["message"]["done"] = I18n::locale("done-operation");
		$system -> reload("controls/migrate.php");
	}
}

include $registry -> getSetting('IncludeAdminPath')."includes/header.php";
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
        <?php
	      	if($errors)
	      		echo "<div class=\"form-errors\"><p>".$errors."</p></div>\n";
	      	else if(isset($_SESSION["message"]["done"]))
	      	{
	      		echo "<div class=\"form-no-errors\"><p>".$_SESSION["message"]["done"]."</p></div>\n";
	      		unset($_SESSION["message"]);
	      	}
	    ?>
	    <div class="migrations">
		   <?php echo $migrations -> displayMigrationsList(); ?>
		</div>
		<form method="post" id="run-migrations-form" action="<?php echo $path; ?>">
		   <input type="hidden" name="migrations" id="current-migration-value" value="" />
           <input type="hidden" name="migrations-csrf-token" value="<?php echo $migrations -> createAllMigrationsToken(); ?>" />
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
<?php
include $registry -> getSetting('IncludeAdminPath')."includes/footer.php";
?>