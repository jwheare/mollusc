<?php

namespace Core;

class Dump {
    protected static $output = '';
    public static function var_dump ($var, $highlight = false) {
        $output = '';
        if ($highlight) {
            $output .= '<div style="font-weight: bold;">';
        }
        $xdebug = ini_get('xdebug.overload_var_dump');
        if (!$xdebug) {
            $output .= "<pre style='overflow: auto; background: #fff; color: #222; border: 1px dotted #ddd; padding: 3px;'>";
        }
        // Dump into a buffer
        ob_start();
        var_dump($var);
        $dump = ob_get_clean();
        if ($xdebug) {
            $output .= $dump;
        } else {
            $output .= safe($dump);
            $output .= "</pre>";
        }
        if ($highlight) {
            $output .= '</div>';
        }
        echo $output;
    }
    public static function light ($var) {
        return print_r($var, true) . "\n";
    }
}