<?php
require_once 'config.php';
require_once 'auth.php';

Auth::logout();
redirect(SITE_URL . '/login.php?logout=1');
?>
