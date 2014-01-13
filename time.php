<?php 
require_once dirname(__FILE__)."/time_config.php";
$now = new DateTime(); 
echo $now->format("M j, Y H:i:s O")."\n";
