<?php
/**
 * Created by PhpStorm.
 * Author: Alexey Tishchenko
 * Email: tischenkoalexey1@gmail.com
 * oDesk: https://www.odesk.com/users/%7E01ad7ed1a6ade4e02e
 * Date: 22.11.14
 */
namespace sjoorm\yii\components\mail;
use sjoorm\yii\components\interfaces\MailerServiceInterface;
use yii\swiftmailer\Mailer;
/**
 * Class SwiftMailer
 * @package sjoorm\yii\components\mail
 *
 * @property string $service name of SwiftMailer service
 * @property string $returnPath bounce return path
 * @property string $serverName mailer server name
 */
class SwiftMailer extends Mailer implements MailerServiceInterface {

    /** @var string mailer service name */
    private $_service;
    /** @var string mailer service name */
    private $_returnPath;
    /** @var string mailer server name */
    private $_serverName;
    /** @var string key string */
    public $key;

    /**
     * Sets mailer service name
     * @param string $service
     * @return static self reference
     */
    public function setService($service) {
        $this->_service = $service;
        return $this;
    }

    /**
     * Gets mailer service name
     * @return string
     */
    public function getService() {
        return $this->_service;
    }

    /**
     * Sets message return path
     * @param string $returnPath
     * @return static self reference
     */
    public function setReturnPath($returnPath) {
        $this->_returnPath = $returnPath;
        return $this;
    }

    /**
     * Gets mailer service name
     * @return string
     */
    public function getReturnPath() {
        return $this->_returnPath;
    }

    /**
     * Sets message server name
     * @param string $serverName
     * @return static self reference
     */
    public function setServerName($serverName) {
        $this->_serverName = $serverName;
        return $this;
    }

    /**
     * Gets mailer server name
     * @return string
     */
    public function getServerName() {
        return $this->_serverName;
    }

    /** @inheritdoc */
    public function compose($view = null, array $params = []) {
        if($this->_serverName) {
            $_SERVER['SERVER_NAME'] = $this->_serverName;
        }
        $message = parent::compose($view, $params);
        /** @var SwiftMessage $message */
        if($this->_returnPath) {
            $message->setReturnPath($this->_returnPath);
        }
        return $message;
    }
}
