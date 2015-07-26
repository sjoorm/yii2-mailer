<?php
/**
 * Created by PhpStorm.
 * Author: Alexey Tishchenko
 * Email: tischenkoalexey1@gmail.com
 * oDesk: https://www.odesk.com/users/%7E01ad7ed1a6ade4e02e
 * Date: 22.11.14
 */
namespace sjoorm\yii\components\mail;
use sjoorm\yii\components\interfaces\MessageCopyInterface;
use yii\swiftmailer\Message;
/**
 * Class SwiftMessage
 * @package sjoorm\yii\components\mail
 */
class SwiftMessage extends Message implements MessageCopyInterface {

    /** @var string HTML body content */
    private $_html;
    /** @var string TEXT body content */
    private $_text;
    /** @var SwiftMailer mailer */
    public $mailer;

    /**
     * Gets message unique ID
     * @return string
     */
    public function getId() {
        return $this->getSwiftMessage()->getId();
    }

    /**
     * Gets message content of specified format
     * @param string $format which content should be returned. 'html' or 'text'
     * @return mixed
     */
    public function getContent($format = null) {
        $result = isset($format) && $format === 'text' ? $this->_text : $this->_html;

        if(empty($result)) {
            $result = isset($format) && $format === 'text' ? $this->_html : $this->_text;
        }

        return $result;
    }

    /** @inheritdoc */
    public function setHtmlBody($html) {
        $this->_html = $html;
        return parent::setHtmlBody($html);
    }

    /** @inheritdoc */
    public function setTextBody($text) {
        $this->_text = $text;
        return parent::setTextBody($text);
    }

    /** @inheritdoc */
    public function saveCopy(callable $callback, $body = true) {
        $tos = $this->getTo();
        $froms = $this->getFrom();
        $to = empty($tos) ? null : array_keys($tos);
        $from = empty($froms) ? null : array_keys($froms);

        $inserted = $callback(
            $this->getId(),
            $to[0],
            $from[0],
            $this->getSubject(),
            $body ? $this->getContent() : null,
            $this->mailer->getService()
        );

        if ($inserted === 0) {
            \Yii::warning("Can not insert email copy of [{$this->getSubject()}|$to]");
        }
    }

    /**
     * Gets message return path
     */
    public function getReturnPath() {
        return $this->getSwiftMessage()->getReturnPath();
    }

    /**
     * Sets message return path
     * @param string $returnPath
     * @return static self reference
     */
    public function setReturnPath($returnPath) {
        $this->getSwiftMessage()->setReturnPath($returnPath);
        return $this;
    }
}
