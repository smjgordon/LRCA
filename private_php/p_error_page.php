<?php
require_once('p_page_template_no_db.php');

function errorPage($errorCode) {
	http_response_code($errorCode);
	pageHeaderNoDb("$errorCode Error - Leicestershire and Rutland Chess Association");
	
	switch ($errorCode) {
		case 403:
		?>
			<p>You do not have permission to access this facility.</p>
		<?php
			break;

		case 404:
		?>
			<p>The requested page was not found.</p>
		<?php
			break;

		case 500:
		?>
			<p>Something went wrong.</p>
			
			<p>If you were in the middle of doing something, please check to see whether it has worked.&nbsp; If not, please try again.</p>
			
			<p>If the problem persists, please <a href="mailto:smjg@iname.com">email me</a> to let me know what you were trying to do.&nbsp; Please include in your email the URL of this page and a description of what you were trying to do.</p>
		<?php
			break;

		default:
		?>
			<p>HTTP error <?php echo $errorCode; ?></p>
		<?php
	}

	pageFooterNoDb();
	exit;
}
?>