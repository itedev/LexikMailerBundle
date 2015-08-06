<?php
/**
 * This file is part of the EXP project.
 * (c) Alex Buturlakin alexbuturlakin@gmail.com
 */

namespace Lexik\Bundle\MailerBundle\Event;

use Symfony\Component\EventDispatcher\Event;
use \Swift_Message as Message;

/**
 * Class BeforeSendEvent
 *
 * @package Lexik\Bundle\MailerBundle\Event
 *
 * @author Alex Buturlakin <alexbuturlakin@gmail.com>
 */
class MessageEvent extends Event
{
    /**
     * @var Message
     */
    private $message;

    /**
     * Getter for $message
     *
     * @return Message
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * Setter for $message
     *
     * @param Message $message
     */
    public function setMessage(Message $message)
    {
        $this->message = $message;
    }
}