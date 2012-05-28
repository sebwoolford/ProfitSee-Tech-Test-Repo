<?php

require('../classes/CommandLine.php');
require('../classes/Forecast.php');
require('../classes/Csv.php');


/**
 * The CommandLine object deals with all input and output operations
 */
$CommandLine = new CommandLine;

/**
 * Here we set the start date for the forecast. This is the first day
 * of the forecast year. The historical data used for comparison will be
 * the previous 12 months and the forecast itself will be for 12 months
 * from this date. Eg. if we want a forecast for 2012 the start date should
 * be '2012-01-01'. The historical data would be from '2011-01' - '2011-12'
 * inclusive.
 */
$startDate = '2011-01-01';

try
{
	$CommandLine
		->addArgument('companyId')
		->process();

	$companyId = (int)$CommandLine->companyId;

	if($companyId > 0)
	{
		$Forecast = new Forecast;
		
		$Forecast
			->setCompanyId($companyId)
			->setStartDate($startDate);
		
		$accounts = $Forecast->getHistoricalDataByAccount();
		
		foreach($accounts as &$account)
		{
			$account['forecast'] = $Forecast->movingAverage($account['months']);
		}
		
		// Generate the CSV header row.
		// Loop through the last account's forecast just to get the column names
		$headerRow = '"Account",';
		foreach($account['forecast'] as $month => $amount)
		{
			$headerRow .= '"' . date("Y-m", strtotime($month)) . '",';
		}
		
		// Output header row to STDOUT
		$CommandLine->outputLine(substr($headerRow, 0, -1)); // (remove the extra comma at the end)
		
		// Output the CSV data
		foreach($accounts as &$account)
		{
			$row = '"' . $account['account_name'] . '",';
			
			foreach($account['forecast'] as $amount)
			{
				$row .= '"' . $amount . '",';
			}
			
			// Output account forecast row to STDOUT
			$CommandLine->outputLine(substr($row, 0, -1)); // (remove the extra comma at the end)
		}
	}
	else
	{
		throw new Exception('Company ID must be numeric.');
	}

}
catch(Exception $e)
{
	$CommandLine
		->outputErrorLine("Error in {$e->getFile()} on line {$e->getLine()}. {$e->getMessage()}")
		->abort();
}
?>