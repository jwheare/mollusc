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
            foreach ($migrations as $migration) {
                try {
                    $this->out("$migration\n");
                    service('db')->execute(file_get_contents("$migrationDir/$migration"));
                } catch (PDOException $e) {
                    if ($e->errorInfo[1] != 1060) { // Duplicate column
                        throw $e;
                    }
                }
            }
        }
    }
}
