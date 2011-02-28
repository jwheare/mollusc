<?php

namespace Core;
use PDO;

class DB {
    protected $connection;
    
    protected $type;
    
    protected $name;
    
    protected $username;
    protected $password;
    
    protected $host;
    protected $port;
    
    protected $socket;
    
    protected $charset;
    
    function __construct($type, $name = null) {
        $this->type = $type;
        $this->name = $name;
    }
    public function setCredentials($username, $password) {
        $this->username = $username;
        $this->password = $password;
    }
    public function setSocket($socket) {
        $this->socket = $socket;
    }
    public function setHost($host, $port) {
        $this->host = $host;
        $this->port = $port;
    }
    public function setCharset($charset) {
        $this->charset = $charset;
    }
    
    function __call($method, $args) {
        if ($this->connection) {
            return call_user_func_array(array($this->connection, $method), $args);
        }
        undefined_method($method, get_called_class());
    }
    
    protected function connect() {
        if (!$this->connection) {
            $this->connection = $this->getConnection();
        }
    }
    
    protected function getConnection() {
        return new PDO($this->getDsn(), $this->username, $this->password, array(
            PDO::ATTR_PERSISTENT => true,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ));
    }
    
    protected function getDsn() {
        $dsn = "{$this->type}:";
        if ($this->name) {
            $dsn .= "dbname={$this->name};";
        }
        if ($this->socket) {
            $dsn .= "unix_socket={$this->socket};";
        }
        if ($this->host) {
            $dsn .= "host={$this->host};";
        }
        if ($this->port) {
            $dsn .= "port={$this->port};";
        }
        if ($this->charset) {
            $dsn .= "charset={$this->charset};";
        }
        return $dsn;
    }
    
    public function fetch($query, $data = array(), $class = null) {
        $statement = $this->execute($query, $data);
        $fetchStyle = PDO::FETCH_ASSOC;
        if ($class) {
            $statement->setFetchMode(PDO::FETCH_CLASS, $class);
            $fetchStyle = PDO::FETCH_CLASS;
        }
        return $statement->fetch($fetchStyle);
    }
    public function fetchAll($query, $data = array(), $class = null) {
        $statement = $this->execute($query, $data);
        if ($class) {
            return $statement->fetchAll(PDO::FETCH_CLASS, $class);
        } else {
            return $statement->fetchAll(PDO::FETCH_ASSOC);
        }
    }
    public function fetchColumn($query, $data = array()) {
        $statement = $this->execute($query, $data);
        return $statement->fetch(PDO::FETCH_COLUMN);
    }
    
    public function execute($query, $data = array()) {
        $this->connect();
        $statement = $this->connection->prepare($query);
        $statement->execute($data);
        return $statement;
    }
}
