<?php
/**
 * This file is part of the EXP project.
 * (c) Alex Buturlakin alexbuturlakin@gmail.com
 */

namespace Lexik\Bundle\MailerBundle\Manager;

use Exception;
use Lexik\Bundle\MailerBundle\Entity\EmailLog;
use Lexik\Bundle\MailerBundle\Event\MessageEvent;
use Lexik\Bundle\MailerBundle\Exception\NoTranslationException;
use Lexik\Bundle\MailerBundle\Exception\ReferenceNotFoundException;
use Lexik\Bundle\MailerBundle\Message\DefaultErrorMessage;
use Lexik\Bundle\MailerBundle\Message\NoTranslationMessage;
use Lexik\Bundle\MailerBundle\Message\ReferenceNotFoundMessage;
use Lexik\Bundle\MailerBundle\Message\TwigErrorMessage;
use Lexik\Bundle\MailerBundle\Message\UndefinedVariableMessage;
use Swift_Mailer;
use Doctrine\ORM\EntityManager;
use Lexik\Bundle\MailerBundle\Message\MessageFactory;
use Swift_Message;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Twig_Error;
use Twig_Error_Runtime;


/**
 * Class MailerManager
 *
 * @package Lexik\Bundle\MailerBundle\Manager
 *
 * @author Alex Buturlakin <alexbuturlakin@gmail.com>
 */
class MailerManager
{
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var MessageFactory
     */
    private $mf;

    /**
     * @var Swift_Mailer
     */
    private $mailer;

    /**
     * @var array
     */
    private $config;

    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    /**
     * @param EntityManager            $em
     * @param MessageFactory           $mf
     * @param Swift_Mailer             $mailer
     * @param EventDispatcherInterface $dispatcher
     * @param array                    $config
     */
    public function __construct(EntityManager $em, MessageFactory $mf, Swift_Mailer $mailer, EventDispatcherInterface $dispatcher, array $config = [])
    {
        $this->em = $em;
        $this->mf = $mf;
        $this->mailer = $mailer;
        $this->config = $config;
        $this->dispatcher = $dispatcher;
    }

    /**
     * Send email.
     *
     * @param string $reference
     * @param string $to
     * @param array $params
     * @param string $from
     * @param array $styles
     * @param string $locale
     * @param array $attachments
     *
     * @return bool
     */
    public function sendEmail($reference, $to, $params, $from = null, array $styles = [], $locale = null, array $attachments = [])
    {
        $styles = $styles && !empty($styles) ? $styles : $this->config['default_styles'];
        $locale = $locale ? $locale : $this->config['default_locale'];
        $log    = EmailLog::createInstance($reference, $from, $to, $locale, $params, null, $styles);

        try {
            $message = $this->mf->get($reference, $to, $params, $locale, $styles);

            if($from) {
                $message->setFrom($from);
            }

            // before sent event
            $event = new MessageEvent();
            $event->setMessage($message);

            $this->dispatcher->dispatch('lexik.before.send', $event);

            $this->mailer->send($event->getMessage());
        } catch (\Exception $e) {
            $log->setErrorFromException($e);
            $log->setSuccess(false);

            // send error notification to admin email address
            if($this->config['error_notifications']==false) {
                $this->sendErrorNotification($e, $reference, $locale);
            }
        }

        $this->em->persist($log);
        $this->em->flush();

        return $log->getSuccess();
    }

    /**
     * Send error notification to admin email address.
     *
     * @param Exception $e
     * @param string    $reference
     * @param string    $locale
     *
     * @return Swift_Message
     */
    public function sendErrorNotification(Exception $e, $reference, $locale)
    {
        $adminEmail = $this->config['admin_email'];
        $message    = $this->generateExceptionMessage($e, $reference, $locale);

        $message->setFrom($adminEmail);
        $message->setTo($adminEmail);

        $this->mailer->send($message);

        return $message;
    }

    /**
     * Send email to admin.
     *
     * @param string      $reference
     * @param array       $params
     * @param null|string $locale
     * @param array       $styles
     */
    public function sendAdminEmail($reference, array $params = [], $locale = null, array $styles = [])
    {
        $styles = $styles && !empty($styles) ? $styles : $this->config['default_styles'];
        $locale = $locale ? $locale : $this->config['default_locale'];

        $message = $this->mf->get($reference, $this->config['admin_email'], $params, $locale, $styles);

        $this->mailer->send($message);
    }

    /**
     * Create swift message when Email is not found.
     *
     * @param Exception $e
     * @param string    $reference
     * @param string    $locale
     *
     * @return Swift_Message
     */
    protected function generateExceptionMessage(Exception $e, $reference, $locale)
    {
        // Email reference was not found
        if($e instanceof ReferenceNotFoundException) {
            return new ReferenceNotFoundMessage($reference, $e);
        }

        // Email does not have translation
        if($e instanceof NoTranslationException) {
            return new NoTranslationMessage($reference, $locale);
        }

        // twig variable not exists error
        if($e instanceof Twig_Error_Runtime) {
            return new UndefinedVariableMessage($e->getMessage(), $reference);
        }

        // other twig error
        if($e instanceof Twig_Error) {
            return new TwigErrorMessage($e->getRawMessage(), $reference);
        }

        // default error
        return new DefaultErrorMessage($reference, $e);
    }
}