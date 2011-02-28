<?php

namespace Core;
use App\Model;

abstract class RelationshipCache {
    private $cache = array();
    protected $relations = array();
    public function __call($method, $args) {
        // Look up relationships in the cache
        if (preg_match('/^get(.+)/', $method, $matches)) {
            $key = strtolower($matches[1]);
            if (isset($this->relations[$key])) {
                return $this->getRelationship($key);
            }
        }
        undefined_method($method, get_called_class());
    }
    protected function getColumn ($key) {
        $column = $key;
        if (isset($this->relations[$key])) {
            $relationVars = $this->relations[$key];
            if (count($relationVars) === 3) {
                $column = $relationVars[2];
            }
        }
        return $column;
    }
    public function primeCache($key, $value) {
        $this->cache[$this->getColumn($key)] = $value;
    }
    protected function getRelationship($key) {
        $column = $this->getColumn($key);
        if (isset($this->cache[$column])) {
            return $this->cache[$column];
        } else {
            $relationVars = $this->relations[$key];
            $relatedClass = $relationVars[0];
            $relatedColumn = $relationVars[1];
            $className = "App\\Model\\$relatedClass";
            $relationship = new $className();
            $loadBy = "loadBy" . $relatedColumn;
            $relationship->$loadBy($this->$column);
            if ($relationship->id) {
                $this->primeCache($column, $relationship);
                return $relationship;
            }
            return false;
        }
    }
}
