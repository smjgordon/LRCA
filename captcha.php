<?php
require_once 'private_php/p_global.php';
require_once 'private_php/c_captcha.php';

$error = validateCaptcha();
pageHeader('Enable Contact Info');
?>

<?php if ($error) { ?>
	<p class="error"><?php echo htmlspecialchars($error); ?></p>
<?php } ?>

<form method="post" action="<?php echo htmlspecialchars($_SERVER['SCRIPT_NAME']); ?>">
	<p>To enable the display of club contact information on this website, please complete the following.</p>
	<p>Registered users will also see club contact information when logged in.</p>
	<p><img id="captcha" src="securimage/securimage_show.php" alt="[CAPTCHA Image]" /></p>
	<p>Please enter the characters you see: <input type="text" name="captcha_code" size="10" maxlength="6" /></p>
	<p><?php carryForwardReferrer(); ?><input type="submit" value="Submit" /></p>
</form>

<?php
pageFooter();
?>