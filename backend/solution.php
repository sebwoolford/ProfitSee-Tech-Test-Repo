<?php
/*/ Fabio Trabucchi - 2010-10-03 /*/
/*/ usage: http://somehost/assignment.php?company_id=12313 /*/

list($usec, $sec) = explode(' ', microtime());
$script_start = (float) $sec + (float) $usec;

/*/ get the company id from the url /*/
if ( !isset($_GET['company_id']) or $_GET['company_id'] == '' ) { die("Company ID Required"); }
$company_id = $_GET['company_id'];

/*/ set db info /*/
$DBHOST = "localhost:8889";
$DBUSER = "root";
$DBPSWD = "root";
$DBNAME = "techtest";

/*/ connect to the dbserver and set default database /*/
$LINK = mysql_connect($DBHOST, $DBUSER, $DBPSWD);
if (!$LINK) { die('Could not connect to the database: ' . mysql_error()); }
if (!mysql_select_db($DBNAME, $LINK)) { die('Cannot use the database $DBNAME: ' . mysql_error()); }

/*/ preparing the output file /*/
$filename = "forecast.csv";
$filehandler = fopen($filename, 'w');
if (!$filehandler) { die('Cannot open $filename in write mode.\nYou may not have the right permission.'); }

/*/ register shutdown function /*/
function shutdown() {
    fclose($filehandler);
    mysql_close($LINK);
}
register_shutdown_function('shutdown');

/*/ get the list containing every accounts for the given company_id /*/
$query = "SELECT account_id FROM Account WHERE company_idfk = {$company_id}";
$result = mysql_query($query, $LINK);
if (!$result) { die('Invalid Query: ' . mysql_error()); }

/*/ start building the result output data /*/
if (!mysql_num_rows($result)) { die("No entries for the requested company_id: {$company_id}"); }
fwrite($filehandler, "account_id, Jan, Feb, Mar, Apr, May, Jun, Jul, Aug, Sep, Oct, Nov, Dec\n");

/*/ cycle over each account_id item returned by the previous query /*/
while ($row = mysql_fetch_object($result)) {
    /*/ fetch forecast's amount (mean of previous amount data) grouped by month /*/
    $forecast_query = "SELECT
        MONTH(entry_period_end_date) as month,
        AVG(entry_amount) as mean,
        STD(entry_amount) as std,
        COUNT(entry_amount) as n
        FROM Entry WHERE account_idfk = {$row->account_id}
        GROUP BY month";
    $forecast_result = mysql_query($forecast_query, $LINK);
    if (!$forecast_result) { die('Invalid Query: ' . mysql_error()); }

    /*/ build a default forecast values for each month /*/
    $forecasts = array(1 => 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0);

    /*/ mapping retrived values and months /*/
    while ($forecast_row = mysql_fetch_object($forecast_result)) {
        /*/ standard error of the mean /*/
        $sem = $forecast_row->std / sqrt($forecast_row->n);

        /*/ calculates lower and highest bounds for the mean with 97.5% of confidence /*/
        $forecast1 = $forecast_row->mean + ($sem * 1.96);
        $forecast2 = $forecast_row->mean - ($sem * 1.96);
        $bounds = array($forecast1, $forecast2);
        sort($bounds);
        $forecasts[$forecast_row->month] = '[' . sprintf("%01.2f", $bounds[0]) . ', '
                                           . sprintf("%01.2f", $bounds[1]) . ']';
    }

    /*/ write data on file /*/
    fwrite($filehandler, $row->account_id . ', ' . implode(', ', $forecasts) . "\n");

    /*/ free all memory associated with the last fetching /*/
    mysql_free_result($forecast_result);
}

/*/ free all memory associated with the first fetching /*/
mysql_free_result($result);

list($usec, $sec) = explode(' ', microtime());
$script_end = (float) $sec + (float) $usec;
$elapsed_time = round($script_end - $script_start, 5);

echo $elapsed_time;
?>
