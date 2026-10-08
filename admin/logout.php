<?php
require_once __DIR__ . '/../config/config.php';
start_session();

unset($_SESSION['admin_logged_in']);
unset($_SESSION['admin_id']);
unset($_SESSION['admin_name']);
unset($_SESSION['admin_email']);

redirect('login.php');