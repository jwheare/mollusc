<?php

namespace Core;

class InitDBScript extends Script {
    
    public function run () {
        $rootDBUser = 'root';
        $rootDBPassword = '';
        $this->out("Root MySQL user (so we can set up the database and user account) [$rootDBUser]: ");
        $input = trim(fgets(STDIN));
        if ($input) {
            $rootDBUser = $input;
        }
        $this->out("Root MySQL password [$rootDBPassword]: ");
        $input = trim(fgets(STDIN));
        if ($input) {
            $rootDBPassword = $input;
        }
        
        // Create the database and user
        $db = new DB('mysql');
        $db->setSocket(MYSQL_SOCKET);
        $db->setCredentials($rootDBUser, $rootDBPassword);
        
        $create = "CREATE DATABASE IF NOT EXISTS " . MYSQL_DB . " DEFAULT CHARACTER SET = 'utf8'";
        $grant = "GRANT ALL ON " . MYSQL_DB . ".* TO '" . MYSQL_USER . "'@'localhost' IDENTIFIED BY ?";
        $this->out("\n$create\n$grant\n\nWe’ll now run the above queries, if you’d rather we didn’t, just type 'n' and press return:\n");
        $input = strtolower(trim(fgets(STDIN)));
        if ($input && ($input[0] == 'n' || $input == 0)) {
            $this->error("Fine. Sorry we even asked. Geeze.");
        }
        $db->execute($create);
        $db->execute($grant, array(MYSQL_PASSWORD));
        
        // Now use the main service DB to load the schemas
        $this->out("Loading schemas:\n");
        $schemaDir = APP_DIR  . '/schema';
        $schemas = scandir($schemaDir);
        $schemas = array_filter($schemas, function ($value) {
            return $value[0] != '.';
        });
        natcasesort($schemas);
        foreach ($schemas as $schema) {
            $this->out("$schema\n");
            service('db')->execute(file_get_contents("$schemaDir/$schema"));
        }
        $this->out("\nAll done!\n");
        
        $this->end();
    }
}
