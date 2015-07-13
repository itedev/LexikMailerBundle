<?php

namespace Lexik\Bundle\MailerBundle\Message;

use Doctrine\ORM\EntityManager;
use Lexik\Bundle\MailerBundle\Exception\ReferenceNotFoundException;
use Lexik\Bundle\MailerBundle\Model\EmailInterface;
use Lexik\Bundle\MailerBundle\Mapping\Driver\Annotation;
use Lexik\Bundle\MailerBundle\Signer\SignerFactory;
use Symfony\Bundle\AsseticBundle\Factory\AssetFactory;

/**
 * Create some swift messages from email templates.
 *
 * @author Cédric Girard <c.girard@lexik.fr>
 * @author Yoann Aparici <y.aparici@lexik.fr>
 * @author Alex Buturlakin <alexbuturlakin@gmail.com>
 */
class MessageFactory
{
    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var MessageRenderer
     */
    protected $renderer;

    /**
     * @var Annotation
     */
    protected $annotationDriver;

    /**
     * @var array
     */
    protected $options;

    /**
     * @var array
     */
    protected $emails;

    /**
     * @var SignerFactory
     */
    protected $signer;

    /**
     * @var AssetFactory
     */
    private $af;

    /**
     * Constructor.
     *
     * @param EntityManager                                        $entityManager
     * @param MessageRenderer                                      $renderer
     * @param \Lexik\Bundle\MailerBundle\Mapping\Driver\Annotation $annotationDriver
     * @param array                                                $defaultOptions
     * @param SignerFactory                                        $signer
     * @param AssetFactory                                         $af
     *
     * @internal param \Lexik\Bundle\MailerBundle\Mapping\Driver\Annotation $driver
     */
    public function __construct(EntityManager $entityManager, MessageRenderer $renderer, Annotation $annotationDriver, $defaultOptions, SignerFactory $signer, AssetFactory $af)
    {
        $this->em = $entityManager;
        $this->af = $af;
        $this->renderer = $renderer;
        $this->annotationDriver = $annotationDriver;
        $this->options = array_merge($this->getDefaultOptions(), $defaultOptions);
        $this->emails = array();
        $this->signer = $signer;
    }

    /**
     * Get default options
     *
     * @return array
     */
    protected function getDefaultOptions()
    {
        return array(
            'email_class'    => '',
            'admin_email'    => '',
            'default_locale' => 'en',
        );
    }

    /**
     * Find an email from database
     *
     * @param  string $reference
     *
     * @throws ReferenceNotFoundException
     *
     * @return  EmailInterface|null
     */
    public function getEmail($reference)
    {
        if (!isset($this->emails[$reference])) {
            $this->emails[$reference] = $this->em->getRepository($this->options['email_class'])->findOneByReference($reference);
        }

        $email = $this->emails[$reference];

        if (!$email instanceof EmailInterface) {
            throw new ReferenceNotFoundException($reference, sprintf('Reference "%s" does not exist for email.', $reference));
        }

        return $email;
    }

    /**
     * Find an email template and create a swift message.
     *
     * @param string $reference
     * @param mixed  $to
     * @param array  $parameters
     * @param string $locale
     * @param array  $styles
     *
     * @return \Swift_Message
     */
    public function get($reference, $to, array $parameters = array(), $locale = null, array $styles = [])
    {
        $email = $this->getEmail($reference);

        return $this->generateMessage($email, $to, $parameters, $locale, $styles);
    }

    /**
     * Create a swift message
     *
     * @param EmailInterface $email
     * @param mixed          $to
     * @param array          $parameters
     * @param string         $locale
     * @param array          $styles
     *
     * @return \Swift_Message
     */
    public function generateMessage(EmailInterface $email, $to, array $parameters = array(), $locale = null, array $styles = [])
    {
        if (null === $locale) {
            $locale = $this->options['default_locale'];
        }

        // Check for annotations
        if (is_object($to)) {
            $name = $this->annotationDriver->getName($to);
            $to = $this->annotationDriver->getEmail($to);

            if (null !== $name && '' !== $name) {
                $to = array($to => $name);
            }
        }

        $email->setLocale($locale);
        $this->renderer->loadTemplates($email);

        $message = $this->createMessageInstance()
                        ->setSubject($this->renderTemplate('subject', $parameters, $email->getChecksum()))
                        ->setFrom($this->renderFromAddress($email, $parameters), $this->renderTemplate('from_name', $parameters, $email->getChecksum()))
                        ->setTo($to)
                        ->setBody($this->renderTemplate('html_content', $parameters, $email->getChecksum()), 'text/html');

        $textContent = $this->renderTemplate('text_content', $parameters, $email->getChecksum());

        if (null !== $textContent && '' !== $textContent) {
            $message->addPart($textContent, 'text/plain');
        }

        foreach ($email->getBccs() as $bcc) {
            $message->addBcc($bcc);
        }

        if (count($email->getHeaders()) > 0) {
            $headers = $message->getHeaders();
            foreach ($email->getHeaders() as $header) {
                if (is_array($header) && isset($header['key'], $header['value'])) {
                    $headers->addTextHeader($header['key'], $header['value']);
                }
            }
        }

        if(!empty($styles)) {
            $this->addStyles($message, $styles);
        }

        return $message;
    }

    /**
     * Add styles to message.
     *
     * @param \Swift_Message $message
     * @param array          $styles
     */
    protected function addStyles(\Swift_Message $message, $styles)
    {
        $body = $message->getBody();
        $css  = $this->af->createAsset($styles)->dump();
        $processor = new CssToInlineStyles($body, $css);

        // process & restore encoded variables in href's
        $html = preg_replace('/%5B(.*)%5D/', '[$1]', $processor->convert());
        $message->setBody($html);
    }

    /**
     * Render template
     *
     * @param string $view
     * @param array  $parameters
     * @param string $checksum
     * @return string
     */
    protected function renderTemplate($view, array $parameters, $checksum)
    {
        $view = sprintf('%s_%s', $view, $checksum);

        return $this->renderer->renderTemplate($view, $parameters);
    }

    /**
     * Create Swiftf message instance
     *
     * @return \Swift_Message
     */
    protected function createMessageInstance()
    {
        $hasSigner = $this->signer->hasSigner();
        $class     = $hasSigner ? '\Swift_SignedMessage' : '\Swift_Message';

        $message = $class::newInstance();

        if ($hasSigner) {
            $message->attachSigner($this->signer->createSigner());
        }

        return $message;
    }

    /**
     * Render email from address
     *
     * @param  EmailInterface $email
     * @param  array          $parameters
     *
     * @return string
     */
    protected function renderFromAddress(EmailInterface $email, array $parameters = array())
    {
        if (null === $email->getFromAddress()) {
            return $this->options['admin_email'];
        }

        return $this->renderTemplate('from_address', $parameters, $email->getChecksum());
    }
}
