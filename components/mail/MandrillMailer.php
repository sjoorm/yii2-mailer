<?php
/**
 * Created by PhpStorm.
 * Author: Alexey Tishchenko
 * Email: tischenkoalexey1@gmail.com
 * oDesk: https://www.odesk.com/users/%7E01ad7ed1a6ade4e02e
 * Date: 21.11.14
 */
namespace sjoorm\yii\components\mail;
use sjoorm\yii\components\interfaces\MailerServiceInterface;
use sjoorm\yii\components\interfaces\MailerTemplateInterface;
use yii\mail\BaseMailer;
/**
 * Class MandrillMailer wrapper for Mandrill API usage
 * @package sjoorm\yii\components\mail
 */
class MandrillMailer extends BaseMailer implements MailerTemplateInterface, MailerServiceInterface {

    const TYPE_SIMPLE   = 0;
    const TYPE_TEMPLATE = 1;

    /** @var \Mandrill instance */
    private $_mandrill;
    /** @var integer message type - simple or template */
    private $_type = self::TYPE_SIMPLE;
    /** @var string template name */
    private $_template;
    /** @var array template content variables */
    private $_templateContent;
    /** @var string Mandrill API token */
    private $_token;
    /** @var string message class */
    public $messageClass = 'sjoorm\yii\components\mail\MandrillMessage';
    /** @var string mailer service name */
    private $_service;
    /** @var string mandrill sub account name */
    public $subaccount;

    /** @inheritdoc */
    public function init() {
        $this->_mandrill = new \Mandrill($this->_token);
        parent::init();
    }

    /**
     * Sets Mandrill API token
     * @param string $token
     */
    public function setToken($token) {
        $this->_token = $token;
    }

    /**
     * Sends the specified message.
     * This method should be implemented by child classes with the actual email sending logic.
     * @param MandrillMessage $message the message to be sent
     * @return boolean whether the message is sent successfully
     */
    protected function sendMessage($message) {
        $result = false;
        if($this->subaccount) {
            $message->setSubaccount($this->subaccount);
        }

        try {
            $response = null;
            switch ($this->_type) {
                case self::TYPE_TEMPLATE:
                    $response = $this->_mandrill->messages->sendTemplate(
                        $this->_template,
                        $this->_templateContent,
                        $message->getMessage()
                    );
                    break;
                default:
                    $response = $this->_mandrill->messages->send(
                        $message->getMessage()
                    );
                    break;
            }
            $response = array_shift($response);
            $result = $response['_id'];
            $message->setId($result);
        } catch (\Mandrill_Error $exception) {
            \Yii::error(get_class($exception) . ' - ' . $exception->getMessage(), 'Mandrill');
        }

        return $result;
    }

    /** @inheritdoc */
    public function composeTemplate($template, array $content = []) {
        $this->_type = self::TYPE_TEMPLATE;
        $this->_template = $template;
        $this->_templateContent = $content;
        return $this->compose();
    }

    /**
     * Sets mailer service name
     * @param $service
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
}
