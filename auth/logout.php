<?php
require_once __DIR__ . '/../includes/auth.php';
do_logout();
redirect('login.php');