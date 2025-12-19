<?php
// pages/customer_logout.php
require_once __DIR__ . '/../lib/auth.php';

logout_customer();
header("Location: ?page=home");
exit;