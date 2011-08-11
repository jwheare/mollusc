<?php

namespace Core;
use DateTime;
use Exception;

class Model extends RelationshipCache {
    
    protected $db = 'db';
    protected $columns = array();
    protected $getByColumns;
    protected $columnSelects = array();
    
    public $table;
    public $id;
    public $creation_date;
    public $data = array();
    public function db () {
        return service($this->db);
    }
    public function __construct($data = array(), $keyPrefix = null) {
        if ($keyPrefix === true) {
            $keyPrefix = $this->table;
        }
        $this->loadData($data, $keyPrefix);
    }
    public function __call($method, $args) {
        // loadBy* magic method handler
        if (preg_match('/^((?:load|getAll)By)(.+)/', $method, $matches)) {
            // Check key is a valid column
            $method = $matches[1];
            $key = strtolower($matches[2]);
            if ($key == 'id' || in_array($key, $this->columns)) {
                array_unshift($args, $key);
                return call_user_func_array(array($this, $method), $args);
            }
        }
        return parent::__call($method, $args);
    }
    
    private function getCommonData() {
        return array(
            'id' => $this->id,
            'creation_date' => $this->creation_date,
        );
    }
    
    // Overload getter and setter to store data
    public function __get($name) {
        if (array_key_exists($name, $this->data)) {
            return $this->data[$name];
        }
    }
    public function __set($name, $value) {
        if (in_array($name, $this->columns)) {
            if ($value instanceof Model) {
                $this->primeCache($name, $value);
                $value = $value->id;
            }
            $this->data[$name] = $value;
        }
    }
    
    private function flattenValueMysql ($value, $placeholder = '?') {
        $flatValue = $value;
        if ($value instanceof Model) {
            $flatValue = $value->id;
        } else if ($value instanceof DateTime) {
            $flatValue = $this->sqlDate($value);
        } else if ($value instanceof Point) {
            $flatValue = $this->sqlPoint($value);
            $placeholder = "GeomFromText($placeholder)";
        }
        return array($flatValue, $placeholder);
    }
    private function flattenValueSql ($value, $placeholder = '?') {
        return $this->flattenValueMysql($value, $placeholder);
    }
    private function flattenValueTsv ($value) {
         if ($value instanceof Model) {
            return $value->id;
        } else if ($value instanceof DateTime) {
            return $value->getTimestamp();
        } else if ($value instanceof Point) {
            return "{$value->latitude} {$value->longitude}";
        } else if (is_string($value)) {
            // Collapse white space
            return preg_replace("/\s+/", " ", $value);
        } else {
            return $value;
        }
    }
    
    protected function loadData($data, $keyPrefix = '') {
        if (!$data) {
            return false;
        }
        if ($keyPrefix) {
            $keyPrefix = "{$keyPrefix}_";
        }
        // Load data into the object
        $this->beforeLoad($data, $keyPrefix);
        if (array_key_exists("{$keyPrefix}id", $data)) {
            $this->id = $data["{$keyPrefix}id"];
            unset($data["{$keyPrefix}id"]);
        }
        if (array_key_exists("{$keyPrefix}creation_date", $data)) {
            $creationDate = $data["{$keyPrefix}creation_date"];
            if (!$creationDate instanceof DateTime) {
                $creationDate = new DateTime($creationDate);
            }
            $this->creation_date = $creationDate;
            unset($data["{$keyPrefix}creation_date"]);
        }
        foreach ($this->columns as $column) {
            $value = null;
            if (isset($data[$keyPrefix . $column])) {
                $value = $data[$keyPrefix . $column];
            }
            if ($value instanceof Model) {
                $this->primeCache($column, $value);
                $value = $value->id;
            }
            $this->data[$column] = $value;
        }
        $this->afterLoad();
        return true;
    }
    protected function getModels ($rows, $cache = array()) {
        $models = array();
        foreach ($rows as $modelData) {
            $class = get_called_class();
            $model = new $class($modelData);
            foreach ($cache as $key => $value) {
                $model->primeCache($key, $value);
            }
            $models[] = $model;
        }
        return $models;
    }
    
    private function loadBy($keys, $values) {
        // Prepare the query
        if (!is_array($keys)) {
            $keys = array($keys);
        }
        if (!is_array($values)) {
            $values = array($values);
        }
        $wheres = array();
        $flatValues = array();
        foreach ($keys as $i => $key) {
            $value = $values[$i];
            if (is_array($value)) {
                $orPlaceholders = array();
                foreach ($value as $orValue) {
                    list ($flatOrValue, $orPlaceholder) = $this->flattenValueSql($orValue);
                    $flatValues[] = $flatOrValue;
                    $orPlaceholders[] = $orPlaceholder;
                }
                $wheres[] = "`$key` in (". implode(',', $orPlaceholders) . ")";
            } else {
                list ($flatValue, $placeholder) = $this->flattenValueSql($value);
                $flatValues[] = $flatValue;
                if (is_array($key)) {
                    $wheres[] = "`{$key[0]}` {$key[1]} $placeholder";
                } else {
                    $wheres[] = "`$key` = $placeholder";
                }
            }
        }
        $query = "SELECT {$this->getColumnsSql()} FROM {$this->table} WHERE " . implode(' AND ', $wheres) . " LIMIT 1";
        // Execute
        $row = $this->db()->fetch($query, $flatValues);
        // Load data, returns false on non-existence
        return $this->loadData($row);
    }
    public function total() {
        return $this->totalBy();
    }
    public function totalBy($keys = null, $values = null) {
        // Prepare the query
        if ($keys) {
            if (!is_array($keys)) {
                $keys = array($keys);
            }
            if (!is_array($values)) {
                $values = array($values);
            }
            $wheres = array();
            $flatValues = array();
            foreach ($keys as $i => $key) {
                $value = $values[$i];
                if (is_array($value)) {
                    $orPlaceholders = array();
                    foreach ($value as $orValue) {
                        list ($flatOrValue, $orPlaceholder) = $this->flattenValueSql($orValue);
                        $flatValues[] = $flatOrValue;
                        $orPlaceholders[] = $orPlaceholder;
                    }
                    $wheres[] = "`$key` in (". implode(',', $orPlaceholders) . ")";
                } else {
                    list ($flatValue, $placeholder) = $this->flattenValueSql($value);
                    $flatValues[] = $flatValue;
                    if (is_array($key)) {
                        $wheres[] = "`{$key[0]}` {$key[1]} $placeholder";
                    } else {
                        $wheres[] = "`$key` = $placeholder";
                    }
                }
            }
        } else {
            $flatValues = $values;
        }
        $query = "SELECT COUNT(*) FROM {$this->table} WHERE " . implode(' AND ', $wheres);
        // Execute
        $total = (int) $this->db()->fetchColumn($query, $flatValues);
        return $total;
    }
    private function getAllByData($keys = null, $values = null, $limit = null, $page = 1, $ordering = "creation_date DESC") {
        $query = "SELECT %s FROM $this->table ";
        $flatValues = null;
        if ($keys) {
            if (!is_array($keys)) {
                $keys = array($keys);
            }
            if (!is_array($values)) {
                $values = array($values);
            }
            $wheres = array();
            $flatValues = array();
            foreach ($keys as $i => $key) {
                $value = $values[$i];
                if (is_array($value)) {
                    $orPlaceholders = array();
                    foreach ($value as $orValue) {
                        list ($flatOrValue, $orPlaceholder) = $this->flattenValueSql($orValue);
                        $flatValues[] = $flatOrValue;
                        $orPlaceholders[] = $orPlaceholder;
                    }
                    $wheres[] = "`$key` in (". implode(',', $orPlaceholders) . ")";
                } else {
                    list ($flatValue, $placeholder) = $this->flattenValueSql($value);
                    $flatValues[] = $flatValue;
                    if (is_array($key)) {
                        $wheres[] = "`{$key[0]}` {$key[1]} $placeholder";
                    } else {
                        $wheres[] = "`$key` = $placeholder";
                    }
                }
            }
            $query .= "WHERE " . implode(' AND ', $wheres) . " ";
        }
        // Get the total
        $total = (int) $this->db()->fetchColumn(sprintf($query, "COUNT(*)"), $flatValues);
        // Run the paginated query
        $query .= "ORDER BY $ordering ";
        if ($limit) {
            $query .= "LIMIT $limit ";
            $offset = ($page - 1) * $limit;
            if ($offset) {
                $query .= "OFFSET $offset ";
            }
        }
        $rows = $this->db()->fetchAll(sprintf($query, $this->getColumnsSql()), $flatValues);
        return array($rows, $total);
    }
    public function getAllBy($keys = null, $values = null, $limit = null, $page = 1, $ordering = "creation_date DESC") {
        list($rows, $total) = $this->getAllByData($keys, $values, $limit, $page, $ordering);
        // Create an array of models
        $models = $this->getModels($rows);
        return array($models, $total);
    }
    public function getAll($limit = null, $page = 1, $ordering = "creation_date DESC") {
        return $this->getAllBy(null, null, $limit, $page, $ordering);
    }
    
    protected function beforeLoad(&$data, $keyPrefix = '') {
        // override in subclass
    }
    protected function afterLoad() {
        // override in subclass
    }
    protected function beforeMutate() {
        // override in subclass
    }
    protected function afterMutate() {
        // override in subclass
    }
    protected function beforeInsert() {
        // override in subclass
    }
    protected function afterInsert() {
        // override in subclass
    }
    protected function beforeUpdate() {
        // override in subclass
    }
    protected function afterUpdate() {
        // override in subclass
    }
    protected function beforeDelete() {
        // override in subclass
    }
    protected function afterDelete() {
        // override in subclass
    }
    
    public function insert() {
        // Callback
        $this->beforeMutate();
        $this->beforeInsert();
        // Prepare the query
        $values = array();
        $flatData = array();
        foreach ($this->columns as $col) {
            if (array_key_exists($col, $this->data)) {
                list ($flatValue, $placeholder) = $this->flattenValueSql($this->data[$col], ":$col");
                $flatData[$col] = $flatValue;
                $keys[] = "`$col`";
                $values[] = $placeholder;
            }
        }
        if ($this->creation_date) {
            $keys[] = '`creation_date`';
            $values[] = ':creation_date';
            $flatData['creation_date'] = $this->sqlDate($this->creation_date);
        }
        if ($this->id) {
            $keys[] = '`id`';
            $values[] = ':id';
            $flatData['id'] = $this->id;
        }
        $query = "INSERT INTO {$this->table} (" . implode(', ', $keys) . ") VALUES (" . implode(', ', $values) . ")";
        // Execute in a transaction
        $db = $this->db();
        $db->beginTransaction();
        $db->execute($query, $flatData);
        // Load data
        $lastId = $db->lastInsertId();
        $this->loadById($lastId);
        $db->commit();
        // Callback
        $this->afterMutate();
        $this->afterInsert();
    }
    
    public function update() {
        // Callback
        $this->beforeMutate();
        $this->beforeUpdate();
        // Prepare the query
        $sets = array();
        $flatData = array();
        foreach ($this->columns as $col) {
            if (array_key_exists($col, $this->data)) {
                list ($flatValue, $placeholder) = $this->flattenValueSql($this->data[$col], ":$col");
                $flatData[$col] = $flatValue;
                $sets[] = "`$col` = $placeholder";
            }
        }
        if ($this->creation_date) {
            $sets[] = "`creation_date` = :creation_date";
            $flatData['creation_date'] = $this->sqlDate($this->creation_date);
        }
        $query = "UPDATE {$this->table} SET " . implode(', ', $sets) . " WHERE `id` = :id";
        $flatData['id'] = $this->id;
        // Execute
        $this->db()->execute($query, $flatData);
        // Callback
        $this->afterMutate();
        $this->afterUpdate();
    }
    
    public function delete() {
        if (!$this->load()) {
            return false;
        }
        // Callback
        $this->beforeMutate();
        $this->beforeDelete();
        // Prepare query
        $query = "DELETE FROM {$this->table} WHERE `id` = :id";
        $params = array('id' => $this->id);
        // Execute
        $this->db()->execute($query, $params);
        // Callback
        $this->afterMutate();
        $this->afterDelete();
        return true;
    }
    public function save() {
        // Can't save without data
        if (empty($this->data)) {
            return false;
        }
        // Update or insert
        if ($this->id) {
            $this->update();
        } else {
            $this->insert();
        }
        return true;
    }
    public function load() {
        if ($this->id) {
            return $this->loadById($this->id);
        }
        if (!$this->getByColumns) {
            throw new ModelException('This model has no getByColumns');
        }
        $loadByValues = array();
        $loadByColumns = array();
        foreach ($this->getByColumns as $column) {
            if ($this->{$column} !== null) {
                $loadByColumns[] = $column;
                $loadByValues[] = $this->{$column};
            }
        }
        if (empty($loadByColumns)) {
            throw new ModelException('No loadable columns specified');
        }
        return $this->loadBy($loadByColumns, $loadByValues);
    }
    // returns created (bool)
    public function loadOrCreate() {
        $loaded = $this->load();
        if ($loaded) {
            return false;
        } else {
            $this->insert();
            return true;
        }
    }
    public function getCreationDate() {
        return $this->creation_date ? $this->creation_date->format('D j M Y g:ia') : null;
    }
    public function mysqlDate (DateTime $dateTime) {
        return $dateTime->format('Y-m-d H:i:s');
    }
    public function sqlDate ($dateTime) {
        if (!$dateTime instanceof DateTime) {
            $dateTime = new DateTime("@$dateTime");
        }
        return $this->mysqlDate($dateTime);
    }
    public function mysqlPoint (Point $point) {
        return "POINT({$point->latitude} {$point->longitude})";
    }
    public function sqlPoint (Point $point) {
        return $this->mysqlPoint($point);
    }
    public function getEncodedId() {
        return encodeNumber($this->id);
    }
    
    public function equals($model) {
        return $model && ($this->table === $model->table) && ($this->id === $model->id);
    }
    
    protected function getColumnSelect($column, $qualified) {
        $select = "{$this->table}.{$column}";
        if (isset($this->columnSelects[$column])) {
            $select = sprintf($this->columnSelects[$column], $select);
            if (!$qualified) {
                $select .= " AS {$column}";
            }
        }
        if ($qualified) {
            $select .= " AS {$this->table}_{$column}";
        }
        return $select;
    }
    public function getColumnsSql($qualified = false) {
        $selects = array(
            "{$this->table}.id" . ($qualified ? " AS {$this->table}_id" : ""),
            "{$this->table}.creation_date" . ($qualified ? " AS {$this->table}_creation_date" : ""),
        );
        foreach ($this->columns as $col) {
            $select = $this->getColumnSelect($col, $qualified);
            $selects[] = $select;
        }
        return implode(', ', $selects);
    }
    public function getFieldsSql($withCommonData = false) {
        if ($withCommonData) {
            $fields = array_merge(array_keys($this->getCommonData()), $this->columns);
        } else {
            $fields = $this->columns;
        }
        $fieldsSql = '("' . implode('", "', $fields) . '")';
        return $fieldsSql;
    }
    public function tsvHeader($filehandler = null, $withCommonData = false) {
        if ($withCommonData) {
            $fields = array_merge(array_keys($this->getCommonData()), $this->columns);
        } else {
            $fields = $this->columns;
        }
        return array_to_tsv_line($fields, $filehandler);
    }
    public function toTsv($filehandler = null, $withCommonData = false) {
        if ($withCommonData) {
            $data = array_merge($this->getCommonData(), $this->data);
        } else {
            $data = $this->data;
        }
        return array_to_tsv_line(array_map(array($this, 'flattenValueTsv'), $data), $filehandler);
    }

}

class ModelException extends Exception {
    var $callingClass;
    public function __construct($message, $code = 0, Exception $previous = null) {
        $trace = $this->getTrace();
        $this->callingClass = strtolower($trace[0]['class']);
        parent::__construct($message, $code, $previous);
    }
    public function __toString() {
        return $this->callingClass . " {$this->getStatusLine()}";
    }
    public function getStatusLine() {
        return "{$this->getCode()} {$this->getMessage()}";
    }
}
