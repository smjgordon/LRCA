<?php
require_once('private_php/p_error_page.php');
$error = @$_GET['error'] or $error = HttpStatus::NotFound;
errorPage($error);
?>