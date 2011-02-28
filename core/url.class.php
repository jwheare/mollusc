<?php

namespace Core;

class Url {
    // http://www.ietf.org/rfc/rfc3986.txt
    static function encodeRFC3986($input) {
        $output = rawurlencode($input);
        $output = str_replace('%7E', '~', $output);
        $output = str_replace('+', '%20', $output);
        return $output;
    }
    static function encodePairs($params, $quoteValue = false) {
        $pairs = array();
        $quote = $quoteValue ? '"' : '';
        foreach ($params as $k => $v) {
            $pairs[] = self::encodeRFC3986($k) . '=' . $quote . self::encodeRFC3986($v) . $quote;
        }
        return $pairs;
    }
    static function encodePairsToString($params, $quoteValue = false) {
        return implode('&', self::encodePairs($params, $quoteValue));
    }
    // Sort, encode and serialize pairs
    static function normaliseParams($params) {
        uksort($params, 'strcmp');
        return self::encodePairsToString($params);
    }
    static function extractUrlParts($url) {
        // Add queryparams array to parse_url format
        $params = array();
        $parts = parse_url($url);
        if (isset($parts['query'])) {
            parse_str($parts['query'], $params);
        }
        $parts['queryparams'] = $params;
        return $parts;
    }
    static function mergeQueryParams($url, $params) {
        $urlParts = self::extractUrlParts($url);
        return self::build($urlParts['path'], array_merge($urlParts['queryparams'], $params));
    }
    static function build($url, $requestParams) {
        // Remove empty params
        $requestParams = array_filter($requestParams);
        if (!empty($requestParams)) {
            $url .= '?' . implode('&', self::encodePairs($requestParams));
        }
        return $url;
    }
    /**
     * Adds a fully qualified host name to urls without a scheme defined.
     *
     * @param string $url - Must be either fully qualified URL or absolute path e.g. /foo/bar?baz=zap
    **/
    static function addHost($url, $host = HOST_NAME) {
        $urlParts = parse_url($url);
        if (isset($urlParts['scheme'])) {
            // Already fully qualified
            return $url;
        }
        // Add the host
        return "http://" . $host . $url;
    }
}
