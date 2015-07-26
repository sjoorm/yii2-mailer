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
use sjoorm\yii\components\interfaces\MessageTemplateInterface;
use yii\helpers\FileHelper;
use yii\mail\BaseMessage;
/**
 * Class MandrillMessage
 * @package sjoorm\yii\components\mail
 */
class MandrillMessage extends BaseMessage implements MessageTemplateInterface, MessageCopyInterface {

    /** @var array Mandrill API message object as associative array */
    private $_message = [
        'important' => true,
        'track_opens' => true,
        'track_clicks' => true,
        'auto_text' => true,
        'subaccount' => '',
    ];
    /** @var string message unique ID */
    private $_id;
    /** @var MandrillMailer mailer object */
    public $mailer;

    /**
     * Gets configuration parameter from $this->_message configuration array
     * @param string $key
     * @param mixed $default
     * @return null
     */
    private function getMessageParam($key, $default = null) {
        return isset($this->_message[$key]) ? $this->_message[$key] : $default;
    }

    /**
     * Sets or adds configuration parameter to $this->_message configuration array
     * @param $key
     * @param $value
     * @return static self reference
     */
    private function setMessageParam($key, $value) {
        $this->_message[$key] = $value;
        return $this;
    }

    /**
     * Returns the character set of this message.
     * @return string the character set of this message.
     */
    public function getCharset() {
        return \Yii::$app->charset;
    }

    /**
     * Sets the character set of this message.
     * @param string $charset character set name.
     * @return static self reference.
     */
    public function setCharset($charset) {
        return $this;
    }

    /**
     * Returns the message sender.
     * @return string the sender
     */
    public function getFrom() {
        return [
            $this->getMessageParam('from_email', '') => $this->getMessageParam('from_name')
        ];
    }

    /**
     * Sets the message sender.
     * @param string|array $from sender email address.
     * You may pass an array of addresses if this message is from multiple people.
     * You may also specify sender name in addition to email address using format:
     * `[email => name]`.
     * @return static self reference.
     */
    public function setFrom($from) {
        $fromEmail = null;
        $fromName = null;

        if(is_array($from)) {
            foreach($from as $email => $name) {
                $fromEmail = $email;
                $fromName = $name;
                break;
            }
        } else {
            $fromEmail = $from;
            $fromName = $from;
        }

        return $this
            ->setMessageParam('from_email', $fromEmail)
            ->setMessageParam('from_name', $fromName);
    }

    /**
     * Gets TO recipients corresponding specified type
     * @param string $type
     * @return array
     */
    private function getToType($type) {
        $result = [];

        foreach ($this->getMessageParam('to') as $record) {
            if ($record['type'] === $type) {
                $result[] = [$record['email'] => $record['name']];
            }
        }

        return $result;
    }

    /**
     * Sets TO recipients of specified type
     * @param array|string $to
     * @param $type
     * @return static self reference
     */
    private function setToType($to, $type) {
        $result = $this->getMessageParam('to', []);

        if(is_array($to)) {
            foreach($to as $email => $name) {
                $result[] = ['email' => $email, 'name' => $name, 'type' => $type];
            }
        } elseif(isset($to)) {
            $result[] = ['email' => $to, 'name' => $to, 'type' => $type];
        }

        return $this->setMessageParam('to', $result);
    }

    /**
     * Returns the message recipient(s).
     * @return array the message recipients
     */
    public function getTo() {
        return $this->getToType('to');
    }

    /**
     * Sets the message recipient(s).
     * @param string|array $to receiver email address.
     * You may pass an array of addresses if multiple recipients should receive this message.
     * You may also specify receiver name in addition to email address using format:
     * `[email => name]`.
     * @return static self reference.
     */
    public function setTo($to) {
        return $this->setToType($to, 'to');
    }

    /**
     * Returns the reply-to address of this message.
     * @return string the reply-to address of this message.
     */
    public function getReplyTo() {
        return isset($this->_message['headers']) ?
            (isset($this->_message['Reply-To']) ? $this->_message['Reply-To'] : null) : null;
    }

    /**
     * Sets the reply-to address of this message.
     * @param string|array $replyTo the reply-to address.
     * You may pass an array of addresses if this message should be replied to multiple people.
     * You may also specify reply-to name in addition to email address using format:
     * `[email => name]`.
     * @return static self reference.
     */
    public function setReplyTo($replyTo) {
        $headers = $this->getMessageParam('headers', []);
        $headers['Reply-To'] = $replyTo;
        $this->setMessageParam('headers', $headers);
        return $this;
    }

    /**
     * Returns the Cc (additional copy receiver) addresses of this message.
     * @return array the Cc (additional copy receiver) addresses of this message.
     */
    public function getCc() {
        return $this->getToType('cc');
    }

    /**
     * Sets the Cc (additional copy receiver) addresses of this message.
     * @param string|array $cc copy receiver email address.
     * You may pass an array of addresses if multiple recipients should receive this message.
     * You may also specify receiver name in addition to email address using format:
     * `[email => name]`.
     * @return static self reference.
     */
    public function setCc($cc) {
        return $this->setToType($cc, 'cc');
    }

    /**
     * Returns the Bcc (hidden copy receiver) addresses of this message.
     * @return array the Bcc (hidden copy receiver) addresses of this message.
     */
    public function getBcc() {
        return $this->getToType('bcc');
    }

    /**
     * Sets the Bcc (hidden copy receiver) addresses of this message.
     * @param string|array $bcc hidden copy receiver email address.
     * You may pass an array of addresses if multiple recipients should receive this message.
     * You may also specify receiver name in addition to email address using format:
     * `[email => name]`.
     * @return static self reference.
     */
    public function setBcc($bcc) {
        return $this->setToType($bcc, 'bcc');
    }

    /**
     * Returns the message subject.
     * @return string the message subject
     */
    public function getSubject() {
        return $this->getMessageParam('subject');
    }

    /**
     * Sets the message subject.
     * @param string $subject message subject
     * @return static self reference.
     */
    public function setSubject($subject) {
        return $this->setMessageParam('subject', $subject);
    }

    /**
     * Sets message plain text content.
     * @param string $text message plain text content.
     * @return static self reference.
     */
    public function setTextBody($text) {
        return $this->setMessageParam('text', $text);
    }

    /**
     * Sets message HTML content.
     * @param string $html message HTML content.
     * @return static self reference.
     */
    public function setHtmlBody($html) {
        return $this->setMessageParam('html', $html);
    }

    /**
     * Attaches existing file to the email message.
     * @param string $fileName full file name
     * @param array $options options for embed file. Valid options are:
     *
     * - fileName: name, which should be used to attach file.
     * - contentType: attached file MIME type.
     *
     * @return static self reference.
     */
    public function attach($fileName, array $options = []) {
        $attachments = $this->getMessageParam('attachments', []);
        $type = isset($options['contentType']) ?
            $options['contentType'] : FileHelper::getMimeType($fileName);
        $name = isset($options['fileName']) ? $options['fileName'] : $fileName;
        $content = base64_encode(file_get_contents($fileName));
        $attachments[] = [
            'type' => $type,
            'name' => $name,
            'content' => $content,
        ];

        return $this->setMessageParam('attachments', $attachments);
    }

    /**
     * Attach specified content as file for the email message.
     * @param string $content attachment file content.
     * @param array $options options for embed file. Valid options are:
     *
     * - fileName: name, which should be used to attach file.
     * - contentType: attached file MIME type.
     *
     * @return static self reference.
     */
    public function attachContent($content, array $options = []) {
        $attachments = $this->getMessageParam('attachments', []);
        $type = isset($options['contentType']) ? $options['contentType'] : 'text/plain';
        $name = isset($options['fileName']) ? $options['fileName'] : 'content.txt';
        $content = base64_encode($content);
        $attachments[] = [
            'type' => $type,
            'name' => $name,
            'content' => $content,
        ];

        return $this->setMessageParam('attachments', $attachments);
    }

    /**
     * Attach a file and return it's CID source.
     * This method should be used when embedding images or other data in a message.
     * @param string $fileName file name.
     * @param array $options options for embed file. Valid options are:
     *
     * - fileName: name, which should be used to attach file.
     * - contentType: attached file MIME type.
     *
     * @return string attachment CID.
     */
    public function embed($fileName, array $options = []) {
        $images = $this->getMessageParam('images', []);
        $type = isset($options['contentType']) ?
            $options['contentType'] : FileHelper::getMimeType($fileName);
        $name = isset($options['fileName']) ? $options['fileName'] : $fileName;
        $content = base64_encode(file_get_contents($fileName));
        $images[] = [
            'type' => $type,
            'name' => $name,
            'content' => $content,
        ];

        $this->setMessageParam('images', $images);

        return $name;
    }

    /**
     * Attach a content as file and return it's CID source.
     * This method should be used when embedding images or other data in a message.
     * @param string $content attachment file content.
     * @param array $options options for embed file. Valid options are:
     *
     * - fileName: name, which should be used to attach file.
     * - contentType: attached file MIME type.
     *
     * @return string attachment CID.
     */
    public function embedContent($content, array $options = []) {
        $attachments = $this->getMessageParam('images', []);
        $type = isset($options['contentType']) ? $options['contentType'] : 'text/plain';
        $name = isset($options['fileName']) ? $options['fileName'] : 'content.txt';
        $content = base64_encode($content);
        $attachments[] = [
            'type' => $type,
            'name' => $name,
            'content' => $content,
        ];

        return $this->setMessageParam('images', $attachments);
    }

    /**
     * Gets subaccount name
     * @return string
     */
    public function getSubaccount() {
        return $this->getMessageParam('subaccount');
    }

    /**
     * Sets subaccount name
     * @param string $subaccount
     * @return MandrillMessage
     */
    public function setSubaccount($subaccount) {
        return $this->setMessageParam('subaccount', $subaccount);
    }

    /**
     * Sets basic message configuration
     * @param array $message
     * @return $this
     */
    public function setMessage(array $message) {
        if(is_array($message)) {
            foreach($message as $key => $value) {
                $this->setMessageParam($key, $value);
            }
        }
        return $this;
    }

    /**
     * Prepares data associative array to be sent via mandrill system
     * @param array $data
     * @return array
     */
    private function prepareData(array $data) {
        $result = [];

        if(is_array($data)) {
            foreach($data as $key => $value) {
                $result[] = [
                    'name' => $key,
                    'content' => $value,
                ];
            }
        }

        return $result;
    }

    /**
     * Sets merge variables
     * @param array $data
     * @param string|null $email
     * @return $this static self reference
     */
    public function setMergeVariables(array $data, $email) {
        if(is_array($data)) {
            if(empty($email)) {
                $this->setMessageParam('merge_vars', $data);
            } else {
                $merge_vars = $this->getMessageParam('merge_vars', []);
                $merge_vars[] = [
                    'rcpt' => $email,
                    'vars' => $this->prepareData($data)
                ];
            }
        }
        return $this;
    }

    /**
     * Gets merge variables
     * @param string|null $email which email address's
     * variables should be returned. null for all
     * @return array
     */
    public function getMergeVariables($email) {
        $merge_vars = $this->getMessageParam('merge_vars', []);
        if(empty($email)) {
            return $merge_vars;
        } else {
            $result = [];

            foreach ($merge_vars as $record) {
                if (is_array($record) && isset($record['rcpt']) && $record['rcpt'] === $email) {
                    $result[$email] = $record['vars'];
                }
            }

            return $result;
        }
    }

    /**
     * Gets resulting message configuration array
     * @return array
     */
    public function getMessage() {
        return $this->_message;
    }

    /**
     * Returns string representation of this message.
     * @return string the string representation of this message.
     */
    public function toString() {
        return json_encode($this->_message);
    }

    /**
     * Gets message unique ID
     * @return string
     */
    public function getId() {
        return $this->_id;
    }

    /**
     * Sets message unique ID randomly or by specified value
     * @param string $id
     * @return static self reference
     */
    public function setId($id) {
        $this->_id = $id;
        return $this;
    }

    /**
     * Gets message content of specified format
     * @param string $format which content should be returned. 'html' or 'text'
     * @return mixed
     */
    public function getContent($format = null) {
        $result = isset($format) && $format === 'text' ?
            $this->getMessageParam('text', $this->getMessageParam('html')) :
            $this->getMessageParam('html', $this->getMessageParam('text'));

        return $result;
    }

    /** @inheritdoc */
    public function saveCopy(callable $callback, $body = true) {
        $tos = $this->getTo();
        $froms = $this->getFrom();
        $to = empty($tos) ? null : array_keys($tos[0]);
        $from = empty($froms) ? null : array_keys($froms[0]);

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
}
