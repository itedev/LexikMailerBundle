<?php
/**
 * This file is part of the EXP project.
 * (c) Alex Buturlakin alexbuturlakin@gmail.com
 */

namespace Lexik\Bundle\MailerBundle\Entity;

use DateTime;
use Exception;
use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ORM\Mapping as ORM;

/**
 * Class EmailLog
 *
 * @package Lexik\Bundle\MailerBundle\Entity
 *
 * @author Alex Buturlakin <alexbuturlakin@gmail.com>
 *
 * @ORM\Entity
 * @ORM\Table(name="lexik_email_log")
 */
class EmailLog
{
    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @var string
     *
     *  @ORM\Column(type="string")
     */
    protected $reference;

    /**
     * @var boolean
     *
     * @ORM\Column(type="boolean")
     */
    protected $success = true;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=2, nullable=true)
     */
    protected $lang;

    /**
     * @var string
     *
     * @ORM\Column(type="string", name="from_address", nullable=true)
     */
    protected $from;

    /**
     * @var string
     *
     * @ORM\Column(type="string", name="to_address", nullable=true)
     */
    protected $to;

    /**
     * @var array
     *
     * @ORM\Column(type="json_array", nullable=true)
     */
    protected $error;

    /**
     * @var array
     *
     * @ORM\Column(type="json_array", nullable=true)
     */
    protected $params;

    /**
     * @var DateTime $created
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(name="created_at", type="datetime")
     */
    private $createdAt;

    /**
     * Getter for $id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Setter for $id
     *
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * Getter for $reference
     *
     * @return string
     */
    public function getReference()
    {
        return $this->reference;
    }

    /**
     * Setter for $reference
     *
     * @param string $reference
     */
    public function setReference($reference)
    {
        $this->reference = $reference;
    }

    /**
     * Getter for $success
     *
     * @return boolean
     */
    public function getSuccess()
    {
        return $this->success;
    }

    /**
     * Setter for $success
     *
     * @param boolean $success
     */
    public function setSuccess($success)
    {
        $this->success = $success;
    }

    /**
     * Getter for $lang
     *
     * @return string
     */
    public function getLang()
    {
        return $this->lang;
    }

    /**
     * Setter for $lang
     *
     * @param string $lang
     */
    public function setLang($lang)
    {
        $this->lang = $lang;
    }

    /**
     * Getter for $from
     *
     * @return string
     */
    public function getFrom()
    {
        return $this->from;
    }

    /**
     * Setter for $from
     *
     * @param string $from
     */
    public function setFrom($from)
    {
        $this->from = $from;
    }

    /**
     * Getter for $to
     *
     * @return string
     */
    public function getTo()
    {
        return $this->to;
    }

    /**
     * Setter for $to
     *
     * @param string $to
     */
    public function setTo($to)
    {
        $this->to = $to;
    }

    /**
     * Getter for $error
     *
     * @return array
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * Set log error from exception.
     *
     * @param Exception $e
     */
    public function setErrorFromException(Exception $e)
    {
        $this->error = [
            'error' => $e->getMessage(),
            'trace' => $e->getTrace(),
            'file'  => $e->getFile(),
            'line'  => $e->getLine()
        ];
    }

    /**
     * Setter for $error
     *
     * @param array $error
     */
    public function setError($error)
    {
        $this->error = $error;
    }

    /**
     * Getter for $params
     *
     * @return array
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * Setter for $params
     *
     * @param array $params
     */
    public function setParams($params)
    {
        $this->params = $params;
    }

    /**
     * Getter for $createdAt
     *
     * @return DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Setter for $createdAt
     *
     * @param DateTime $createdAt
     */
    public function setCreatedAt(DateTime $createdAt)
    {
        $this->createdAt = $createdAt;
    }

    /**
     * Create log instance.
     *
     * @param string $reference
     * @param string $from
     * @param string $to
     * @param string $lang
     * @param array  $params
     * @param Exception|null $e
     * @param array $styles
     *
     * @return EmailLog
     */
    public static function createInstance($reference, $from, $to, $lang, array $params = [],Exception $e = null, array $styles = [])
    {
        $log = new EmailLog();

        $log->setReference($reference);
        $log->setTo($to);
        $log->setFrom($from);
        $log->setLang($lang);

        $log->setParams([
          'email_variables' => $params,
          'email_styles'    => $styles
        ]);

        if($e) {
            $log->setErrorFromException($e);
            $log->success = false;
        }

        return $log;
    }
}