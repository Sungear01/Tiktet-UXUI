<?php
require_once __DIR__ . '/core/helpers.php';
$u=auth_user(); json_ok($u);
