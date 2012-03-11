<?php

namespace Core;
use \PDOException;

class DBMigrationScript extends Script {
    
    public function run () {
        $migrationDir = APP_DIR  . '/migration';
        $migrations = scandir($migrationDir);
        $migrations = array_filter($migrations, function ($value) {
            return $value[0] != '.';
        });
        if (count($migrations)) {
            $this->out("Loading migrations:\n");
            natcasesort($migrations);
            // Prompt
            $this->out("\n" . implode("\n$migrationDir/", $migrations) . "\n\nAre you happy to run these SQL migrations on the `" . MYSQL_DB . "` database now?\nProbably wise to backup your database first.\n[Y/n]: ");
            $input = strtolower(trim(fgets(STDIN)));
            if ($input && ($input[0] == 'n' || $input == 0)) {
                $this->end();
            }
            foreach ($migrations as $migration) {
                try {
                    $commands = file_get_contents("$migrationDir/$migration");
                    $this->out("\n$migration\n$commands\n");
                    service('db')->execute($commands);
                } catch (PDOException $e) {
                    if ($e->errorInfo[1] != 1060) { // Duplicate column
                        throw $e;
                    }
                }
            }
        }
    }
}
