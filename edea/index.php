<?php

define('CLASS_DIR', 'Profitsee/');

set_include_path(get_include_path() . PATH_SEPARATOR . CLASS_DIR);

spl_autoload_register();

use Profitsee\Forecast;

$DEFAULT_COMPANY = '54';


$companyId = !empty($_GET['id']) ? $_GET['id'] : $DEFAULT_COMPANY;

$forecast = new Forecast($companyId);

// dump as Array
//$forecast->dumpArray();

// dump CSV in US-format if your locale is set to US
$forecast->dumpCSV('us');

//$forecast->dumpCSV();



