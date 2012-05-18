<?php

namespace Profitsee;

class Forecast {
    
    protected $db;
    
    protected $companyId;
    
    protected $startDate;
    
    protected $forecast;
    
    public function __construct($companyId)
    {
        $this->initDb();
        $this->setCompanyId($companyId);
        $this->setStartDate();
        $this->populateForecast();
    }
    
    public function setCompanyId($companyId)
    {
        if ($this->isValidCompanyId($companyId)) {
            $this->companyId = mysql_real_escape_string($companyId);
        }
    }
    
    public function isValidCompanyId($companyId)
    {
        $sql = "SELECT company_name FROM company "
             . "WHERE company_id = '" . mysql_real_escape_string($companyId) . "'";
        $result = $this->fetch($sql);
        
        if (empty($result)) {
            die('Invalid Company ID');
        }
        return true;
    }
    
    public function dumpArray()
    {
        var_dump($this->forecast);
    }
    
    public function dumpCSV($locale = 'en')
    {
        if ($locale == 'en') {
            $separator = ',';
        } else {
            $separator = ';';
        }
        
        $filename = $this->companyId ."_forecast.csv";
        header("Content-type: application/csv");
        header("Content-Disposition: attachment; filename=$filename");
        
        echo 'Account / Date' . $separator;
        $date = $this->startDate;
        
        for ($i=1; $i<=12; $i++) {
            echo substr($date, 0, -3) . $separator;
            $date = $this->addMonth($date);
        }
        echo "\n";
        
        foreach ($this->forecast as $k => $entry) {
            if ($k != 'total') {
                echo $entry['name'] . $separator;
                $date = $this->startDate;
                for ($i=0; $i<12; ++$i) {
                    $index = substr($date, 0, -3);
                    if (isset($entry['average'][$index])) {
                        echo number_format($entry['average'][$index], 2, ',', '.') . $separator;
                    } else {
                        echo '0' , $separator;
                    }
                    $date = $this->addMonth($date);
                }
                echo "\n";
            }
        }
        
        echo 'Total' . $separator;
        foreach ($this->forecast['total'] as $total) {
            echo number_format($total, 2, ',', '.') . $separator;
        }
    }
    
    protected function setStartDate()
    {
        $sql = "SELECT e.entry_period_end_date "
             . "FROM entry e "
             . "LEFT JOIN account a ON (e.account_idfk = a.account_id) "
             . "WHERE a.company_idfk = '" . $this->companyId . "' "
             . "ORDER BY e.entry_period_end_date DESC "
             . "LIMIT 1";
        $result = $this->fetch($sql);
        
        if (empty($result)) {
            die ('This company has no accounts.');
        }
        
        $date = $result[0]['entry_period_end_date'];
        $date = $this->addMonth($date);
        $date = substr($date, 0, -3) . '-01'; 
        
        $this->startDate = $date;
    }
    
    protected function populateForecast()
    {
        $date = $this->startDate;
        
        for ($i=1; $i<=12; ++$i) {
            $this->addMonthForecast($date);
            $date = $this->addMonth($date);
        }
    }
    
    protected function addMonthForecast($date)
    {
        $month = date('m', strtotime($date));
        $index = substr($date, 0, -3);
        
        $sql = "SELECT a.account_id, a.account_name, AVG(e.entry_amount) average "
             . "FROM account a "
             . "LEFT JOIN entry e ON a.account_id = e.account_idfk "
             . "WHERE a.company_idfk = '" . $this->companyId . "' "
             . "AND month(e.entry_period_end_date) = '$month' "
             . "GROUP BY a.account_id "
             . "ORDER BY a.account_order";
        $result = $this->fetch($sql);
        
        $avgSum = 0;
        foreach ($result as $account) {
            $this->forecast[$account['account_id']]['name'] = $account['account_name'];
            $this->forecast[$account['account_id']]['average'][$index] = $account['average'];
            $avgSum += $account['average'];
        }
        $this->forecast['total'][$index] = $avgSum;
    }
    
    protected function addMonth($date)
    {
        $date = strtotime('+1 month', strtotime($date));
        $date = date('Y-m-d', $date);
        return $date;
    }
    
    protected  function getDbConfig()
    {
        return include __DIR__ . '/../config/db.config.php';
    }
    
    protected function initDb($charset = 'iso88591')
    {
        if (is_null($this->db)) {
            $dbConfig = $this->getDbConfig();
            $this->db = mysql_connect($dbConfig['host'], $dbConfig['user'], $dbConfig['pass']);
            mysql_select_db($dbConfig['dbname']);
            mysql_set_charset($charset);
        }
    }
    
    protected function fetch($sql)
    {
        $query = mysql_query($sql) or die(mysql_error());
        $result = array();
        
        while ($row = mysql_fetch_assoc($query)) {
            $result[] = $row;
        }
        
        return $result;
    }
}