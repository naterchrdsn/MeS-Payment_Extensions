<?php
 
require_once 'top.inc.php';
 
$path = '/var/www/public/xcart/classes/XLite/Module/MeS/MeS/install.yaml';
 
\XLite\Core\Database::getInstance()->loadFixturesFromYaml($path);