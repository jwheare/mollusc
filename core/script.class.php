<?php

namespace Core;

abstract class Script {
    private $defaultOptions = array(
        'dry-run' => null,
    );
    private $argv = array();
    private $args = array();
    
    private $end;
    private $error;
    
    protected $dryRun;
    protected $options = array();
    
    public function __construct () {
        $this->setArgs();
        $this->dryRun = $this->argExists('dry-run');
        
        register_shutdown_function(array($this, 'shutdownHandler'));
    }
    
    public function shutdownHandler () {
        if (!$this->end) {
            if ($lastError = error_get_last()) {
                $this->error("Error {$lastError['type']}: {$lastError['message']} in {$lastError['file']} on line {$lastError['line']}");
            } else {
                $this->end();
            }
        }
    }
    protected function onEnd () {
        // overrite in subclass
    }
    protected function onCancel () {
        // overrite in subclass
    }
    protected function onError () {
        // overrite in subclass
    }
    
    public function argvExists ($key) {
        return array_key_exists($key, $this->argv);
    }
    public function argv ($key, $default = null) {
        return $this->argvExists($key) ? $this->argv[$key] : $default;
    }
    public function getArgv () {
        return $this->argv;
    }
    
    public function argExists ($key) {
        return array_key_exists($key, $this->args);
    }
    public function arg ($key, $default = null) {
        return $this->argExists($key) ? $this->args[$key] : $default;
    }
    private function setArg ($key, $value) {
        if ($this->argExists($key)) {
            $this->args[$key] = array_merge((array) $this->args[$key], (array) $value);
        } else {
            $this->args[$key] = $value;
        }
    }
    
    // Parse command line options to an array keyed to long option names
    // Takes an array of long -> short option name mappings
    private function setArgs () {
        // Set Argv
        $this->argv = $_SERVER["argv"];
        // Remove script name
        array_shift($this->argv);
        // Parse named args
        $shortopts = '';
        $longopts = array();
        $longToShort = array_merge($this->defaultOptions, $this->options);
        $normLookup = array();
        foreach ($longToShort as $long => $short) {
            // Add lookup values for the normalised long name
            $normLong = str_replace(':', '', $long);
            $normLookup[$normLong] = $normLong;
            if ($short) {
                $normShort = str_replace(':', '', $short);
                $normLookup[$normShort] = $normLong;
            }
            // Build getopt arguments
            $longopts[] = $long;
            if ($short) {
                // Add short option to long option list too for more flexible argument passing
                $longopts[] = $short;
                $shortopts .= $short;
            }
        }
        $opts = getopt($shortopts, $longopts);
        // Set args
        foreach ($opts as $key => $val) {
            $this->setArg($normLookup[$key], $val);
        }
        $i = 0;
        while (next($this->argv)) {
            $arg = $this->argv[$i];
            if (strpos($arg, '-') === 0) {
                // Check for invalid argument
                if ($arg[1] === '-') {
                    $argNormParts = explode('=', ltrim($arg, '-'));
                    $argNorm = $argNormParts[0];
                } else {
                    $argNorm = $arg[1];
                }
                if (!array_key_exists($argNorm, $normLookup)) {
                    $this->error("Invalid argument: $arg");
                }
                if (!$this->argExists($normLookup[$argNorm], $this->args)) {
                    $this->error("Missing value for argument: $arg");
                }
                // Remove named from argv
                unset($this->argv[$i]);
                $inline = ($arg[1] !== '-') && (strlen($arg) > 2);
                // Unset the next argv param unless it was set inline or is missing
                if (!$inline && $this->arg($normLookup[$argNorm])) {
                    if (count(explode('=', $arg)) == 1) {
                        unset($this->argv[++$i]);
                    }
                }
            }
            $i++;
        }
        $this->argv = array_values($this->argv);
    }
    abstract public function run();
    
    static function getScriptPath ($name) {
        return realpath(SCRIPT_DIR . "/$name.php");
    }
    protected function out ($string) {
        echo $string;
    }
    protected function warn ($string) {
        error_log($string);
    }
    protected function error ($string = null, $status = 1) {
        $this->end = true;
        $this->onError();
        if ($string) {
            $this->warn($string);
        }
        exit($status);
    }
    protected function cancel () {
        $this->end = true;
        $this->onCancel();
        exit(0);
    }
    protected function end () {
        $this->end = true;
        $this->onEnd();
        exit(0);
    }
}
