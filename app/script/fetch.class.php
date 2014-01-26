<?php

namespace App\Script;
use App\Model;
use Core\Script as CoreScript;
use Core\HttpRequest;
use Core\HttpRequestException;
use \DOMDocument;
use \DateTime;

require_once('simpletest/browser.php');

class Fetch extends CoreScript {
    protected $created = 0;
    protected $loaded = 0;
    protected $options = array(
        'username:' => 'u:',
        'password:' => 'p:',
        'card:'     => 'c:',
        'csv:'      => false,
    );
    const HEADERS = 'Date,Start Time,End Time,Journey/Action,Charge,Credit,Balance,Note';
    
    protected $username = null;
    protected $password = null;
    protected $card = null;
    const LOGIN_URL = "https://oyster.tfl.gov.uk/oyster/entry.do";
    const ROOT_URL = "https://oyster.tfl.gov.uk";
    
    protected function parseTable ($table) {
        $rows = $table->getElementsByTagName("tr");
        // First row is the header
        $headings = array();
        $data = array();
        foreach ($rows as $i => $row) {
            if ($i == 0) {
                foreach ($row->getElementsByTagName("th") as $heading) {
                    $headings[] = $heading->nodeValue;
                }
            } else {
                $rowData = array();
                $cells = $row->getElementsByTagName("td");
                foreach ($cells as $j => $cell) {
                    $value = mb_ereg_replace("[\r\t\n]", "", trim(mb_ereg_replace("\xC2\xA0", " ", $cell->nodeValue)));
                    $rowData[$headings[$j]] = $value;
                }
                $data[] = $rowData;
            }
        }
        return array($headings, $data);
    }
    
    protected function parseCsv ($csv) {
        if (!$csv) {
            $this->error("Empty CSV");
        }
        $rows = explode("\n", trim($csv));
        // First row is the header
        $data = array();
        $headingCount = 0;
        foreach ($rows as $i => $row) {
            if (!$row) {
                continue;
            }
            if ($i == 0) {
                $headings = str_getcsv($row);
                if ($headings != str_getcsv(self::HEADERS)) {
                    $this->error("Invalid CSV headers:\n$row\nExpected:\n" . self::HEADERS);
                }
                $headingCount = count($headings);
            } else {
                $rowData = array();
                $values = str_getcsv($row);
                if (count($values) != $headingCount) {
                    $this->error("Row field count doesn't match header:\n" . self::HEADERS . "\n(line " . ($i+1) . ") $row");
                }
                foreach ($values as $j => $value) {
                    $rowData[$headings[$j]] = $value;
                }
                $data[] = $rowData;
            }
        }
        return array($headings, $data);
    }
    
    protected function convertToPence ($value) {
        $clean = mb_ereg_replace("[£\s]", '', $value);
        if ($clean === '') {
            return null;
        }
        return $clean * 100;
    }
    
    protected function parseStatementPage ($page) {
        // Parse the journey table
        $doc = new DOMDocument;
        $doc->loadHTML($page);
        $history = null;
        foreach ($doc->getElementsByTagName('table') as $table) {
            if ($table->getAttribute('class') == 'journeyhistory') {
                $history = $table;
            }
        };
        if (!$history) {
            $this->warn('No history found');
            return false;
        }
        return $this->parseTable($history);
    }
    
    protected function saveRows ($rows) {
        foreach ($rows as $row) {
            // Import
            $startTime = $row['Start Time'];
            if (!$startTime) {
                $startTime = "00:00";
            }
            $dt = DateTime::createFromFormat('d-M-Y H:i', "{$row['Date']} {$startTime}");
            $end_dt = null;
            $action = 'Entry';
            if ($row['End Time']) {
                $end_dt = DateTime::createFromFormat('d-M-Y H:i', "{$row['Date']} {$row['End Time']}");
                $action = 'Journey';
            }
            if ($row['Credit']) {
                $action = 'Auto top-up';
            }
            $credit = $this->convertToPence($row['Credit']);
            $charge = $this->convertToPence($row['Charge']);
            $fare = $credit - $charge;
            $event = new Model\Event(array(
                'creation_date' => $dt,
                'end_date' => $end_dt,
                'location' => $row['Journey/Action'],
                'action' => $action,
                'fare' => $fare,
                'balance' => $this->convertToPence($row['Balance']),
                'note' => $row['Note'],
            ));
            if (!$this->dryRun) {
                if ($event->loadOrCreate()) {
                    $this->created++;
                    $this->out("C");
                } else {
                    $this->loaded++;
                    $this->out("L");
                }
                $this->out("  ");
            }
            $this->out($event->toTsv(null, true));
            $this->out("\n");
        }
    }
    
    protected function fetchHistory () {
        $this->out("Logging in as {$this->username}\n");
        
        $browser = new \SimpleBrowser();
        if (!$browser->get(self::LOGIN_URL)) {
            $this->error("Couldn't reach the Oyster site");
        }
        $browser->setFieldById('j_username', $this->username);
        $browser->setFieldById('UserName', $this->username);
        $browser->setFieldById('j_password', $this->password);
        $browser->setFieldById('Password', $this->password);
        $page = $browser->submitFormById('sign-in');
        $urlParts = explode(";", $browser->getUrl());
        $builtUrl = $urlParts[0];
        if (isset($urlParts[1])) {
            $lastParts = explode("?", $urlParts[1]);
            if (isset($lastParts[1])) {
                $builtUrl .= '?' . $lastParts[1];
            }
        }
        if ($builtUrl != self::ROOT_URL . "/oyster/oyster/selectCard.do?method=display") {
            $this->error("Invalid logged in URL: {$browser->getUrl()}\n");
        }
        
        if (preg_match('/Card No: (\d+)/', $page, $matches)) {
            // Just one card to select
            $cardNumber = $matches[1];
        } else if ($this->card) {
            // Need to select a card number
            $cardNumber = $this->card;
            $browser->setFieldById('select_card_no', $cardNumber);
            $page = $browser->submitFormById('selectCardForm');
            if (preg_match('/Card No: (\d+)/', $page, $matches)) {
                if ($cardNumber != $matches[1]) {
                    $this->error("Got a different card number: {$matches[1]}\n");
                }
            } else {
                $this->error("$page\n\nError selecting card number from page\n");
            }
        } else {
            $this->error("Multiple cards on account, please specify one to fetch history for\n");
        }
        
        $this->out("Fetching history for: $cardNumber\n");
        
        // Can only get 2 months of data
        $from = date("d/m/Y", strtotime("yesterday -2 months"));
        $to = date("d/m/Y", strtotime("yesterday"));
        $this->out("$from - $to\n");
        $page = $browser->post(self::ROOT_URL . '/oyster/journeyHistory.do', array(
            'dateRange' => 'custom date range',
            'offset' => 0,
            'rows' => 0,
            'customDateRangeSel' => false,
            'isJSEnabledForPagenation' => false,
            'csDateFrom' => $from,
            'csDateTo' => $to,
        ));
        
        // Find the CSV download link
        if (preg_match('/document\.jhDownloadForm\.action="([^"]+)"/', $page, $matches)) {
            $req = new HttpRequest;
            try {
                $cookies  = "JSESSIONID=" . $browser->getCurrentCookieValue("JSESSIONID") . "; ";
                list($body, $info) = $req->send(self::ROOT_URL . $matches[1], "POST", array(), array(), $cookies);
            } catch (HttpRequestException $exc) {
                $this->error("Couldn't dowload CSV");
            }
        } else {
            $this->error("Couldn't reach the Oyster site");
        }
        
        list($headings, $rows) = $this->parseCsv($body);
        
        return array($headings, $rows);
    }
    
    public function run () {
        libxml_use_internal_errors(true);
        
        if ($csv = $this->arg('csv')) {
            $this->out("Reading history from local file: $csv\n");
            if (!file_exists($csv)) {
                $this->error("No such file");
            }
            if (!is_readable($csv)) {
                 $this->error("Unreadable file");   
            }
            $body = file_get_contents($csv);
            list($headings, $rows) = $this->parseCsv($body);
        } else {
            $this->username = $this->arg('username', OYSTER_USERNAME);
            $this->password = $this->arg('password', OYSTER_PASSWORD);
            $this->card = $this->arg('card', OYSTER_CARD);
            
            list($headings, $rows) = $this->fetchHistory();
        }

        $this->saveRows($rows);
        
        if (!$this->dryRun) {
            if ($this->created) {
                $this->out("Imported: {$this->created}\n");
            }
        }
        
        $this->end();
    }
}
