<?php

namespace App\Script;
use App\Model;
use Core\Script as CoreScript;
use \DOMDocument;
use \DateTime;

class Import extends CoreScript {
    protected $created = 0;
    protected $loaded = 0;
    
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
    
    protected function importStatement ($statement) {
        # Parse the journey table
        $doc = new DOMDocument;
        $doc->loadHTMLFile("{$this->getDataDir()}/$statement");
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
        list($headings, $data) = $this->parseTable($history);
        
        
        $lastDate = null;
        foreach ($data as $row) {
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
    
    protected function getDataDir () {
        return APP_DIR  . '/data/html';
    }
    
    public function run () {
        libxml_use_internal_errors(true);
        $files = scandir($this->getDataDir());
        $files = array_filter($files, function ($value) {
            return $value[0] != '.';
        });
        natcasesort($files);
        $numfiles = count($files);
        
        $this->out("Importing $numfiles files\n");
        
        foreach ($files as $i => $statement) {
            $this->importStatement($statement);
        }
        
        if (!$this->dryRun) {
            if ($this->created) {
                $this->out("Imported: {$this->created}\n");
            }
        }
        
        $this->end();
    }
}
