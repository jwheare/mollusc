<?php

namespace App\Script;
use App\Model;
use Core\Script as CoreScript;
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
    );
    
    protected $username = null;
    protected $password = null;
    protected $card = null;
    
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
        $lastDate = null;
        foreach ($rows as $row) {
            // Blank dates are the same as previous
            if (mb_strlen($row['Date']) < 5 && $lastDate) {
                $row['Date'] = $lastDate;
            }
            $lastDate = $row['Date'];
            // Import
            $dt = DateTime::createFromFormat('d/m/y H:i', "{$row['Date']} {$row['Time']}");
            $event = new Model\Event(array(
                'creation_date' => $dt,
                'location' => $row['Location'],
                'action' => $row['Action'],
                'fare' => $this->convertToPence($row['Fare']),
                'balance' => $this->convertToPence($row['Balance']),
                'price_cap' => $this->convertToPence($row['Price cap']),
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
        $browser->get('https://oyster.tfl.gov.uk/oyster/entry.do');
        $browser->setFieldById('j_username', $this->username);
        $browser->setFieldById('j_password', $this->password);
        $page = $browser->submitFormById('sign-in');
        if ($browser->getUrl() != "https://oyster.tfl.gov.uk/oyster/loggedin.do") {
            $this->error("Invalid logged in URL\n");
        }

        if (OYSTER_CARD) {
            $cardNumber = OYSTER_CARD;
        }

        if (preg_match('/Card No: (\d+)/', $page, $matches)) {
            // Just one card to select
            $cardNumber = $matches[1];
        } else if ($cardNumber) {
            // Need to select a card number
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
        
        echo("Fetching history for: $cardNumber\n");
        
        $page = $browser->clickLink("Journey history");
        
        $previousUrl = $browser->getURL();
        echo("$previousUrl\n");
        list($headings, $rows) = $this->parseStatementPage($page);
        
        while ($nextUrl = $browser->getLink("Next")) {
            $nextUrl = $nextUrl->asString();
            if ($nextUrl == $previousUrl) {
                echo("$nextUrl\n");
                error_log("Next url is same as previous\n");
                break;
            }
            echo("$nextUrl\n");
            $page = $browser->clickLink("Next");
            list($pageHeadings, $pageRows) = $this->parseStatementPage($page);
            $rows = array_merge($rows, $pageRows);
            $previousUrl = $nextUrl;
        }
        
        return array($headings, $rows);
    }
    
    public function run () {
        libxml_use_internal_errors(true);
        
        $this->username = $this->arg('username', OYSTER_USERNAME);
        $this->password = $this->arg('password', OYSTER_PASSWORD);
        $this->card = $this->arg('password', OYSTER_CARD);
        
        list($headings, $rows) = $this->fetchHistory();
        $this->saveRows($rows);
        
        if (!$this->dryRun) {
            if ($this->created) {
                $this->out("Imported: {$this->created}\n");
            }
        }
        
        $this->end();
    }
}
