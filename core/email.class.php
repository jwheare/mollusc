<?php

namespace Core;
use DateTime;

class Email {
    protected $subject;
    protected $body;
    public function __construct($address, $message_id, array $context = array(), $footer = '') {
        $this->address = $address;
        $this->message_id = $message_id;
        $this->context = $context;
        $output = $this->render($message_id, $context);
        $this->body .= $footer;
    }
    public function render () {
        // Set variable context
        extract($this->context);
        // Template will set instance variables
        include EMAIL_DIR . "//{$this->message_id}.inc.php";
    }
    public function send() {
        return self::sendRaw($this->address, $this->subject, $this->body);
    }
    static function sendRaw($address, $subject, $body) {
        $headers = null;
        $from = SITE_EMAIL;
        if (!defined('DISABLE_EMAILS') || !DISABLE_EMAILS) {
            return mb_send_mail($address, $subject, $body, $headers, " -t -i -F " . SITE_NAME . " -f $from");
        } else {
            $dt = new DateTime;
            $date = $dt->format('D j M Y g:ia');
            $from = SITE_EMAIL;
            $msg = <<<EOF

From: $from
To: $address
Subject: $subject
Date: $date
$headers

$body

=======================================================
=======================================================

EOF;
            file_put_contents(ROOT_DIR . '/mail.log', $msg, FILE_APPEND);
            return true;
        }
    }
}
