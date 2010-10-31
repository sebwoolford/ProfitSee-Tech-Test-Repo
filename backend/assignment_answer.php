<?php
//Author: Dev Oliver
//Assignment for ProfitSee
//October 30, 2010


//get the company id from the url
$company_id = $_GET['company_id'];
if(!$company_id || $company_id=="")
	die("Company id required");

//database connection info
$hostname="localhost";
$username="profitsee";
$password="password";
$dbname="profitsee";

//connect to the database
mysql_connect($hostname,$username, $password) OR DIE ("Unable to connect to database! Please try again later.");
mysql_select_db($dbname);


//initialize output file
$forecast_file = "forecast.csv";
$fh = fopen($forecast_file, 'w') or die("can't open file");

$forecast_data = "Account Number, January, February, March, April, May, June, July, August, September, October, November, December\n";

//retrieve all accounts for the given company
$query_account = "select * from account where company_idfk = ".$company_id;
$result_account = mysql_query($query_account) or die("Error retrieving accounts");								

//for each account belonging to the given company
while ($row_account = mysql_fetch_array($result_account)) {
	
	$forecast_data .= $row_account['account_id'];
	
	//retrieve the forecast for the current account
	$query_forecast = "
		SELECT MONTH(MONTH) as forecast_month, AVG(amount) as forecast_amount FROM (
		SELECT MONTH(MONTH), MONTH, entry_id, account_idfk, entry_period_end_date, entry_amount,
		CASE   
			WHEN entry_amount IS NULL THEN 0
			ELSE entry_amount
		END AS amount
		FROM MONTH LEFT JOIN (SELECT * FROM entry WHERE account_idfk = ".$row_account['account_id'].") e 
		ON MONTH.MONTH = e.entry_period_end_date
		) a GROUP by MONTH(MONTH) ORDER BY MONTH(MONTH)	
	";	
	$result_forecast = mysql_query($query_forecast) or die("Error retrieving forecast");

	//extract the forecast data for each month
	while ($row_forecast = mysql_fetch_array($result_forecast)) {
		$forecast_data .= ", ".$row_forecast['forecast_amount'];
	}
	$forecast_data .= "\n";
}

//write forecast data to file
fwrite($fh, $forecast_data);
fclose($fh);

echo "Forecast successfully created";

?>