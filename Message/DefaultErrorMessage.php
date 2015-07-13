<?php

namespace Lexik\Bundle\MailerBundle\Message;

use Exception;

/**
 * Class DefaultErrorMessage
 *
 * @package Lexik\Bundle\MailerBundle\Message
 *
 * @author Alex Buturlakin <alexbuturlakin@gmail.com>
 */
class DefaultErrorMessage extends \Swift_Message implements ErrorMessageInterface
{
    /**
     * Construct.
     *
     * @param string    $reference
     * @param Exception $e
     */
    public function __construct($reference, Exception $e)
    {
        parent::__construct();

        $trace = var_export($e->getTrace(), true);

        $body = <<<EOF
An error occurred while trying to send an email.
Error message: {$e->getMessage()}
Error trace: {$trace}
EOF;

        $this->setSubject('An exception occurred')->setBody($body);
    }
}
