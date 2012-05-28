<?php

final class Forecast
{
	
	/**
	 * The PDO database object. Created at instantiation.
	 */
	private $_db;
	
	
	/**
	 * The id of the company we want to forecast for.
	 */
	private $_companyId;
	
	
	/**
	 * The start date of the forecast year.
	 * The historical data used is the 12 months leading up to this date.
	 */
	private $_startDate;
	
	
	/**
	 * Class constructor. Creates database object and connects to database.
	 */
	public function __construct()
	{
		$hostName = 'localhost';
		$dbName = 'profitsee_test';
		$username = 'profitsee';
		$password = '@_profitsee357';

		$this->_db  = new PDO("mysql:host={$hostName};dbname={$dbName}", $username, $password);
	}
	
	
	/**
	 * Sets the company id and returns the current object for method chaining.
	 *
	 * @param integer $companyId  The id of the company we want to forecast for.
	 *
	 * @return Forecast  The current object for method chaining.
	 */
	public function setCompanyId($companyId)
	{
		if(is_integer($companyId))
		{
			$this->_companyId = $companyId;
			return $this;
		}
		else
		{
			throw new Exception('Company ID must be an integer');
		}
	}
	
	
	/**
	 * Get the company id.
	 *
	 * @return integer  The company id.
	 */
	public function getCompanyId()
	{
		return $this->_companyId;
	}
	
	
	/**
	 * Sets the start date and returns the current object for method chaining.
	 *
	 * @param string $startDate  The start date of the forecast year.
	 *
	 * @return Forecast  The current object for method chaining.
	 */
	public function setStartDate($startDate)
	{
		$this->_startDate = $startDate;
		return $this;
	}
	
	
	/**
	 * Get the start date.
	 *
	 * @return string  The start date.
	 */
	public function getStartDate()
	{
		return $this->_startDate;
	}
	
	
	/**
	 * Get the historical data for an account. $this->_companyId must be set first.
	 *
	 * @return array  A multidemensional array of accounts as returned by the database,
	 *                with each element containing a sub-element called 'dates' with
	 *                historical data in it.
	 */
	public function getHistoricalDataByAccount()
	{
		if(!$this->_companyId)
		{
			throw new Exception('Company ID must be set first.');
		}
		if(!$this->_startDate)
		{
			throw new Exception('Start date must be set first.');
		}
		
		
		/**
		 * Step 1 - Fetch all account records for the given company
		 */
		 
		$sql = '
		SELECT account.*
		FROM account
		WHERE company_idfk = ?
		ORDER BY account_order ASC
		';
		
		$stmt = $this->_db->prepare($sql);
		
		$stmt->execute(array($this->_companyId));
		
		$accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
		
		
		/**
		 * Step 2 - Loop through each account and fetch a years worth of historical data
		 */
		 
		// Start date for historic data is 1 year before the forecast start date
		$historicStartDate = date("Y-m-d", strtotime("- 1 year", strtotime($this->_startDate)));
		
		// End date for historic data is the same as the forecast start date
		$historicEndDate = $this->_startDate;
		
		// Loop through each account
		foreach($accounts as &$account)
		{
			$accountId = $account['account_id'];
			
			$sql = '
			SELECT month.month, entry2.amount
			FROM month
			LEFT JOIN (
				SELECT entry_period_end_date, SUM(entry.entry_amount) amount
				FROM entry
				WHERE account_idfk = ?
				GROUP BY entry_period_end_date
			) entry2 ON month.month = entry2.entry_period_end_date
			WHERE month.month BETWEEN ? AND ?
			';
			
			$stmt = $this->_db->prepare($sql);
			
			$stmt->execute(array($accountId, $historicStartDate, $historicEndDate));
			
			$account['months'] = array();
			
			// Fetch all the historical months costs for this account
			foreach($stmt->fetchAll(PDO::FETCH_ASSOC) as $month)
			{
				// Assign costs for that month to the array
				// If no entries, ensure it goes in as 0, not as null
				$account['months'][$month['month']] = floatval($month['amount']);
					//($month['amount']) ? $month['amount'] : 0;
			}
			
		}
		
		return $accounts;
	}
	
	
	/**
	 * Generate a moving average forecast based on a years worth of historical data
	 *
	 * @param array $months  An array containing 12 months worth of historical data
	 *	                     with the month as the key and the amount/cost as the value
	 *
	 * @return array  The 12 month forecast, formatted in the same way as the input
	 */
	public function movingAverage(array $months)
	{
		$forecast = array();
		
		// The date of the month being forecast
		$forecastDate = '';
		
		// Loop 12 times
		for($i = 1; $i <= 12; $i++)
		{
			$rollingYearAmount = 0;
			
			if($forecastDate)
			{
				// If already set, the forecast date is 1 month on from the previous forecast date
				// Beware: using "+ 1 month" breaks this in the Feb/March transition
				$forecastDate = date("Y-m-d H:i:s", strtotime("last day of next month", strtotime($forecastDate)));
			}
			
			// Loop through each historic month so we can calculate average
			foreach($months as $month => $amount)
			{
				if(!$forecastDate)
				{
					// If not set yet, the forecast date is 1 year from the first historic data date
					$forecastDate = date("Y-m-d H:i:s", strtotime("+ 1 year", strtotime($month)));
				}
				
				$rollingYearAmount += $amount;
			}
			
			// Now work out the average for the last 12 months
			$rollingAverage = $rollingYearAmount / 12;
			$forecast[$forecastDate] = $rollingAverage;
				
			// Remove the first month from the months so we can calculate the next rolling average
			array_shift($months);
			
			// And append the new months forecasted amount for the next iteration
			$months[$forecastDate] = $rollingAverage;
			
		}
		
		return $forecast;
	}
}