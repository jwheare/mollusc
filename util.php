<?php

use Core\ServiceManager;
use Core\Dump;

function dump($var, $highlight = false) {
    Dump::var_dump($var, $highlight);
}

function safe($value) {
    // Recurse through arrays
    if (is_array($value)) {
        return array_map('safe', $value);
    }
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}
function out($string) {
    echo safe($string);
}
function jsout($output) {
    echo json_encode($output);
}
function service($serviceName) {
    $manager = new ServiceManager();
    return $manager->get($serviceName);
}


function truncate($string, $truncate, $linked = false) {
    if ($linked) {
        $truncated = '';
        $isAt = false;
        $inAtPotential = false;
        $inLink = false;
        $inAtLink = false;
        $inTag = false;
        $length = mb_strlen($string);
        $pointer = 0;
        // Set truncate to 0 indexed
        $truncate--;
        while ($pointer < $length) {
            // Current character
            $char = mb_substr($string, $pointer, 1);
            // Potential @ link coming up
            $isAt = $char == '@' || $char == '＠';
            if ($isAt) {
                $inAtPotential = true;
            }
            // Only truncate if we're not in a link
            if (!$inLink && !$inAtLink) {
                // Truncate before a trailing @
                if ($pointer == $truncate && $isAt) {
                    break;
                }
                // Truncate at given length
                if ($pointer > $truncate) {
                    break;
                }
            }
            // Open tag, in link
            if ($char == '<') {
                $inLink = true;
                // Entering an @ link
                if ($inAtPotential) {
                    $inAtLink = true;
                    // Turn off potential
                    $inAtPotential = false;
                }
            } else if (!$isAt) {
                // Turn off potential unless it was only just set
                $inAtPotential = false;
            }
            if ($char == '>') {
                if ($inTag) {
                    $inAt = false;
                    $inLink = false;
                    $inAtLink = false;
                    $inTag = false;
                } else {
                    // We're now in the tag body, the next > will be the closer
                    $inTag = true;
                }
            }
            // Append the character
            $truncated .= $char;
            // Move along pointer
            $pointer++;
        }
    } else {
        $truncated = mb_strcut($string, 0, $truncate);
    }
    if (mb_strlen($truncated) < mb_strlen($string)) {
        $truncated .= '…';
    }
    return $truncated;
}
class LinkifyCallback {
    public $style;
    public $truncate;
    
    public function handle ($matches) {
        $url = preg_replace("/^http:\/\//i", '', $matches[1]);
        $url = preg_replace("/^www\./i", '', $url);
        $url = preg_replace("/\/$/", '', $url);
        $fullUrl = $matches[1];
        $style = '';
        if ($this->style) {
            $style = ' style="' . $this->style . '"';
        }
        return '<a href="' . $fullUrl . '" title="' . $fullUrl . '"' . $style . '>' . truncate($url, $this->truncate) . '</a>';
    }
}
define('AUTOLINK_REGEX', "/\b(([a-z][\w-]+:(?:\/{1,3}|[a-z0-9%])|www\d{0,3}[.]|[a-z0-9.\-]+[.][a-z]{2,4}\/)(?:[^\s()<>]+|\((?:[^\s()<>]+|(?:\([^\s()<>]+\)))*\))+(?:\((?:[^\s()<>]+|(\(?:[^\s()<>]+\)))*\)|[^\s`!()\[\]{};:'\".,<>?«»“”‘’]))/i");
function linkify($string, $style = '', $truncate = 50) {
    $linkifyCallback = new LinkifyCallback();
    $linkifyCallback->style = $style;
    $linkifyCallback->truncate = $truncate;
    return preg_replace_callback(
        AUTOLINK_REGEX,
        array($linkifyCallback, 'handle'),
        $string
    );
}

function plur ($count, $string) {
    if ($count != 1) {
        $string .= 's';
    }
    return $string;
}

function undefined_method ($method, $class) {
    $bt = debug_backtrace(false);
    $file = '';
    $line = '';
    for ($i = count($bt) - 1; $i >= 0; $i--) {
        $frame = $bt[$i];
        if (isset($frame['type']) && $frame['type'] === '->' && $frame['class'] === $class && $frame['function'] === $method) {
            if (ini_get('html_errors') && $xdebug_link = ini_get('xdebug.file_link_format')) {
                $file = ' in <a style="color: black;" href="' . str_replace(array('%f', '%l'), array($frame['file'], $frame['line']), $xdebug_link) . '">' . $frame['file'] . '</a>';
            } else {
                $file = " in {$frame['file']}";
            }
            $line = " on line {$frame['line']}";
            break;
        }
    }
    trigger_error("Call to undefined method {$class}->{$method}{$file}{$line}", E_USER_ERROR);
}

function array_to_tsv_line($data_array, $filehandler = null) {
    $tsv_line = implode("\t", $data_array);
    if ($filehandler) {
        return fwrite($filehandler, "$tsv_line\n");
    } else {
        return $tsv_line;
    }
}
