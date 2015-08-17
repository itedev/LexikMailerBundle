<?php
/**
 * This file is part of the EXP project.
 * (c) Alex Buturlakin alexbuturlakin@gmail.com
 */

namespace Lexik\Bundle\MailerBundle\Event;

use Lexik\Bundle\MailerBundle\Model\EmailInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Class MessageGenerationEvent
 *
 * @package Lexik\Bundle\MailerBundle\Event
 *
 * @author Alex Buturlakin <alexbuturlakin@gmail.com>
 */
class MessageGenerationEvent extends Event
{
    /**
     * @var array
     */
    private $parameters;

    /**
     * @var string
     */
    private $locale;

    /**
     * @var EmailInterface
     */
    private $email;

    /**
     * @var array
     */
    private $styles;


    public function __construct(EmailInterface $email, array $parameters = [], $locale = null, array $styles = [])
    {
        $this->email      = $email;
        $this->parameters = $parameters;
        $this->locale     = $locale;
        $this->styles     = $styles;
    }

    /**
     * Getter for $parameters
     *
     * @return array
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * Getter for $locale
     *
     * @return string
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * Getter for $email
     *
     * @return EmailInterface
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Getter for $styles
     *
     * @return array
     */
    public function getStyles()
    {
        return $this->styles;
    }

    /**
     * Setter for $parameters
     *
     * @param array $parameters
     */
    public function setParameters($parameters)
    {
        $this->parameters = $parameters;
    }

    /**
     * Setter for $locale
     *
     * @param string $locale
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;
    }

    /**
     * Setter for $styles
     *
     * @param array $styles
     */
    public function setStyles($styles)
    {
        $this->styles = $styles;
    }

    /**
     * Setter for $email
     *
     * @param EmailInterface $email
     */
    public function setEmail(EmailInterface $email)
    {
        $this->email = $email;
    }

    /**
     * @param string|int $key
     * @param mixed      $val
     */
    public function setTemplateParameter($key, $val)
    {
        $this->parameters[$key] = $val;
    }
}