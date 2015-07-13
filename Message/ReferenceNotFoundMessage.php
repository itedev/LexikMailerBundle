<?php

namespace Lexik\Bundle\MailerBundle\Message;

use Exception;

/**
 * @author Yoann Aparici <y.aparici@lexik.fr>
 */
class ReferenceNotFoundMessage extends \Swift_Message implements ErrorMessageInterface
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

        $traces = debug_backtrace(false);

        $file = null;
        $line = null;

        foreach ($traces as $trace) {
            if (isset($trace['function']) && $trace['function'] == 'get' && isset($trace['class']) && $trace['class'] == __CLASS__) {
                $file = $trace['file'];
                $line = $trace['line'];
                break;
            }
        }

        $body = <<<EOF
An error occurred while trying to send an email.
You tried to use a reference that does not exist : "{$reference}"
in "{$file}" at line {$line}
EOF;

        $this->setSubject('An exception occurred')->setBody($body);
    }
}
