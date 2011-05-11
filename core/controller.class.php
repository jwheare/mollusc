<?php

namespace Core;

class Controller {
    static function getClasses () {
        return array(
            "text/html" => array(
                "Html" => "GET",
                "Form" => "POST",
            ),
            "application/json" => array(
                "JsonGet" => "GET",
                "JsonPost" => "POST",
                "JsonPut" => "PUT",
                "JsonDelete" => "DELETE",
            ),
        );
    }
}
