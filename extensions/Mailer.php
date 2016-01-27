<?php
/**
 * Created by PhpStorm.
 * Author: Alexey Tishchenko
 * Email: tischenkoalexey1@gmail.com
 * oDesk: https://www.odesk.com/users/%7E01ad7ed1a6ade4e02e
 * Date: 26.07.15
 */
namespace sjoorm\yii\extensions;
use sjoorm\yii\components\interfaces\MailerServiceInterface;
use sjoorm\yii\components\interfaces\MessageCopyInterface;
use yii\base\Application;
use yii\base\Component;
use yii\base\Event;
use yii\base\ViewContextInterface;
use yii\mail\MailEvent;
use yii\mail\MessageInterface;
use yii\web\Response;
use yii\web\View;
/**
 * Email helper extension
 * Allows to use different mailers
 * Possible to send with or without mail queue
 *
 * @property array $mailers available mailers with their configs
 * @property boolean $lastResult last sending result
 * @property string|boolean $textLayout TEXT message layout
 * @property string|boolean $htmlLayout HTML message layout
 * @property View $view View object
 */
class Mailer extends Component implements ViewContextInterface {

    const FROM = 'from',
        TO = 'to',
        REPLY_TO = 'reply-to',
        CC = 'cc',
        BCC = 'bcc';

    /**
     * @event MailEvent an event raised right before send.
     * You may set [[MailEvent::isValid]] to be false to cancel the send.
     */
    const EVENT_BEFORE_SEND = '\sjoorm\yii\extensions\Mailer_beforeSend';
    /**
     * @event MailEvent an event raised right after send.
     */
    const EVENT_AFTER_SEND = '\sjoorm\yii\extensions\Mailer_mailer_afterSend';

    /**
     * @var string|boolean HTML layout view name. This is the layout used to render HTML mail body.
     * The property can take the following values:
     *
     * - a relative view name: a view file relative to [[viewPath]], e.g., 'layouts/html'.
     * - a path alias: an absolute view file path specified as a path alias, e.g., '@app/mail/html'.
     * - a boolean false: the layout is disabled.
     */
    private $_htmlLayout = 'layouts/html';
    /**
     * @var string|boolean text layout view name. This is the layout used to render TEXT mail body.
     * Please refer to [[_htmlLayout]] for possible values that this property can take.
     */
    private $_textLayout = 'layouts/text';
    /** @var \yii\base\View|array view instance or its array configuration. */
    private $_view = [];
    /** @var string the directory containing view files for composing mail messages. */
    private $_viewPath;

    /** @var array[] email queue */
    private $_emailQueue;
    /** @var array list of available mailer wrappers; being used in specified priority */
    private $_mailers;
    /** @var boolean if email copy's body needs to be stored in DB */
    private $_needCopy = true;
    /** @var boolean if email should be tracked and stored in DB */
    private $_track = true;
    /** @var boolean last sending result */
    private $_lastResult;
    /** @var boolean if email should be queued, not sent immediately */
    private $_enableQueue = true;
    /** @var callable the callback to be executed to save message copy */
    private $_copyCallback;

    /**
     * Component initialization
     */
    public function init() {
        $this->_emailQueue = [];
        if(\Yii::$app->response->hasProperty('isSent')) {
            \Yii::$app->response->on(Response::EVENT_AFTER_SEND, [$this, 'onProcessQueueHandler']);
        } else {
            \Yii::$app->response->on(Application::EVENT_AFTER_ACTION, [$this, 'onProcessQueueHandler']);
        }
    }

    /**
     * Finishes current request, sends output to user and processes email queue
     * @param Event $event
     */
    public function onProcessQueueHandler(/** @noinspection PhpUnusedParameterInspection */$event) {
        if(function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        }

        foreach($this->_emailQueue as $email) {
            if(!call_user_func_array([$this, 'send'], $email)) {
                \Yii::error("Can not send email from the QUEUE [{$email[0]}]", 'Email');
            }
        }
    }

    /**
     * Sends simple TEXT & HTML email message
     * @param string $subject message subject
     * @param array|string $view in one of following formats:
     * "viewName",['viewName', 'param1'=>$param1, ...]
     * @param array $header
     * @param array $attachments
     * @param array|string $options
     * @return static self reference
     */
    public function template(
        $subject,
        $view,
        array $header = [],
        array $attachments = [],
        array $options = []
    ) {
        $content = [
            'text' => null,
            'html' => null,
        ];
        $this->view->title = $subject;

        if(!empty($view)) {
            if (is_array($view)) {
                if (isset($view['html'])) {
                    $html = $this->render($view['html'], $view, $this->_htmlLayout);
                }
                if (isset($view['text'])) {
                    $text = $this->render($view['text'], $view, $this->_textLayout);
                }
                if(!isset($html) && !isset($text)) {
                    $html = $this->render($view[0], $view, $this->_htmlLayout);
                }
            } else {
                $html = $this->render($view, [], $this->_htmlLayout);
            }

            if (isset($html)) {
                $content['html'] = $html;
            }
            if (isset($text)) {
                $content['text'] = $text;
            } elseif (isset($html)) {
                $text = null;
                if (is_array($view)) {
                    if (isset($view['html'])) {
                        $text = $this->render($view['html'], $view, $this->_textLayout);
                    } else {
                        $text = $this->render(array_shift($view), $view, $this->_textLayout);
                    }
                } else {
                    $text = $this->render($view, [], $this->_textLayout);
                }
                $content['text'] = $this->toText($text);
            }
        }

        return $this->email(
            $subject,
            $content,
            $header,
            $attachments,
            $options
        );
    }

    /**
     * Sends simple TEXT & HTML email message
     * @param string $subject message subject
     * @param array|string $body message HTML/HTML+TEXT body
     * @param array $header
     * @param array $attachments
     * @param array|string $options
     * @return static self reference
     */
    public function email(
        $subject,
        $body,
        array $header = [],
        array $attachments = [],
        array $options = []
    ) {
        $content = [];
        if(is_array($body)) {
            $content['html'] = isset($body['html']) ?
                $body['html'] : null;
            $content['text'] = isset($body['text']) ?
                $body['text'] : $this->toText($content['html']);
        } else {
            $content['html'] = $body;
            $content['text'] = $this->toText($body);
        }
        return $this->handle(
            $subject,
            $content,
            $header,
            $attachments,
            $options
        );
    }

    /**
     * Sends simple HTML email message
     * @param string $subject message subject
     * @param string $html message HTML body
     * @param array $header
     * @param array $attachments
     * @param array|string $options
     * @return static self reference
     */
    public function html(
        $subject,
        $html,
        array $header = [],
        array $attachments = [],
        array $options = []
    ) {
        return $this->handle(
            $subject,
            ['html' => $html],
            $header,
            $attachments,
            $options
        );
    }

    /**
     * Sends simple TEXT email message
     * @param string $subject message subject
     * @param string $text message TEXT body
     * @param array $header
     * @param array $attachments
     * @param array $options
     * @return static self reference
     */
    public function text(
        $subject,
        $text,
        array $header = [],
        array $attachments = [],
        array $options = []
    ) {
        return $this->handle(
            $subject,
            ['text' => $text],
            $header,
            $attachments,
            $options
        );
    }

    /**
     * Handles email sending process: decides if it should be sent immediately or not.
     * @param string $subject message subject
     * @param array $content ['text'=>'...', 'html'=>'...'] content values
     * @param array $header
     * @param array $attachments ['/path/to/file/1', '/path/to/file/2'] OR ['/path/to/file' => ['contentType' => 'text/csv', 'fileName' => 'newName.csv']]
     * @param array $options options be given to the created mailers
     * @return static self reference
     */
    protected function handle(
        $subject,
        $content,
        array $header,
        array $attachments,
        array $options
    ) {
        // prepare headers
        $isSystemNotification = false;
        if(!isset($header[self::TO])) {
            $header[self::TO] = [\Yii::$app->params['adminEmail'] => 'Administrator PMG'];
        }
        if(!isset($header[self::FROM])) {
            $header[self::FROM] = [\Yii::$app->params['supportEmail'] => \Yii::$app->name];
            $isSystemNotification = true;
        }
        if(!isset($header[self::REPLY_TO])) {
            $header[self::REPLY_TO] = $isSystemNotification ?
                [\Yii::$app->params['adminEmail'] => 'Administrator PMG'] : $header[self::FROM];
        }
        if(!isset($header[self::CC])) {
            $header[self::CC] = \Yii::$app->params['adminCc'];
        }
        if(!isset($header[self::BCC])) {
            $header[self::BCC] = \Yii::$app->params['adminBcc'];
        }
        // process message
        if($this->_enableQueue) {
            $this->_emailQueue[] = [
                $subject,
                $content,
                $header,
                $attachments,
                $options,
                $this->_track,
                $this->_needCopy
            ];
            $this->_lastResult = true; // message was ONLY accepted for delivery.
            $this->_needCopy = true;
            $this->_track = true;
        } else {
            $this->send($subject, $content, $header, $attachments, $options);
        }

        return $this;
    }

    /**
     * Sends message through first available MailerInterface specified by $this->mailers
     * @param string $subject message subject
     * @param array $content ['text'=>'...', 'html'=>'...'] content values
     * @param array $header
     * @param array $attachments
     * @param array $options options be given to the created mailers
     * @param boolean $needTracking if tracking copy is needed
     * @param boolean $needCopy if body copy is needed
     * @return boolean
     */
    protected function send(
        $subject,
        $content,
        array $header,
        array $attachments,
        array $options,
        $needTracking = null,
        $needCopy = null
    ) {
        $this->_lastResult = false;

        if(empty($this->_mailers)) {
            $to = is_array($header[self::TO]) ? array_shift($header[self::TO]) : $header[self::TO];
            \Yii::error("Message [$subject|$to] was not sent: no mailers configured.", static::className());
            return false;
        }

        $message = null;
        foreach($this->_mailers as $config) {
            $mailerClass = $config['class'];
            $object = isset($options[$mailerClass]) ? array_merge($config, $options[$mailerClass]) : $config;
            $mailer = \Yii::createObject($object);
            /** @var MailerServiceInterface $mailer */
            $message = $this
                ->compose($mailer, $content, $subject, $header, $attachments);
            if (!$this->beforeSend($message)) {
                break;
            }
            $this->_lastResult = $message->send();
            if($this->_lastResult) {
                if((isset($needTracking) ? $needTracking : $this->_track) && is_callable($this->_copyCallback)) {
                    /** @var MailerServiceInterface $mailer */
                    /** @var MessageCopyInterface $message */
                    $message->saveCopy($this->_copyCallback, isset($needCopy) ? $needCopy : $this->_needCopy);
                }

                $this->_needCopy = true;
                $this->_track = true;
                break;
            }
        }
        $this->afterSend($message, $this->_lastResult);

        if(!$this->_lastResult) {
            $to = is_array($header[self::TO]) ? array_shift($header[self::TO]) : $header[self::TO];
            \Yii::error("Message [$subject|$to] was not sent.", static::className());
        }

        return (boolean)$this->_lastResult; // force boolean result
    }

    /**
     * Creates message object with specified mailer
     * @param MailerServiceInterface $mailer
     * @param array $content
     * @param string $subject
     * @param string $subject
     * @param array $header
     * @param array $attachments
     *
     * @return MessageCopyInterface
     */
    protected function compose(
        $mailer,
        $content,
        $subject,
        array $header,
        array $attachments
    ) {
        $message = $mailer->compose()
            ->setReplyTo($header[self::REPLY_TO])
            ->setTo($header[self::TO])
            ->setFrom($header[self::FROM])
            ->setCc($header[self::CC])
            ->setBcc($header[self::BCC])
            ->setCharset(\Yii::$app->charset)
            ->setSubject($subject);

        if(!empty($attachments)) {
            foreach($attachments as $path => $details) {
                if(is_array($details)) {
                    $message->attach($path, $details);
                } elseif(is_string($details)) {
                    $message->attach($details);
                }
            }
        }

        if(is_array($content)) {
            if(isset($content['text'])) {
                $message->setTextBody($content['text']);
            }
            if(isset($content['html'])) {
                $message->setHtmlBody($content['html']);
            }
        }

        return $message;
    }

    /**
     * Sets up mailers configuration
     * @param array $mailers
     */
    public function setMailers($mailers) {
        $this->_mailers = [];
        if(is_array($mailers)) {
            foreach($mailers as $mailer => $config) {
                if(is_string($mailer) || is_integer($mailer)) {
                    $this->_mailers[$mailer] = $config;
                } else {
                    $this->_mailers[$config] = [];
                }
            }
        } else {
            $this->_mailers[] = $mailers;
        }
    }

    /**
     * Gets current mailers configuration
     * @return array
     */
    public function getMailers() {
        return $this->_mailers;
    }

    /**
     * Extracts message text from HTML code
     * @param string $html
     * @return string
     */
    protected function toText($html) {
        if (preg_match('|<body[^>]*>(.*?)</body>|is', $html, $matches)) {
            $html = $matches[1];
        }
        $html = preg_replace('|<style[^>]*>(.*?)</style>|is', '', $html);
        $html = preg_replace('| {2,}|is', '', $html);
        return trim(strip_tags($html));
    }

    /**
     * @return View view instance.
     */
    public function getView() {
        if (!is_object($this->_view)) {
            $this->_view = $this->createView($this->_view);
        }

        return $this->_view;
    }

    /**
     * Creates view instance from given configuration.
     * @param array $config view configuration.
     * @return View view instance.
     */
    protected function createView(array $config) {
        if (!array_key_exists('class', $config)) {
            $config['class'] = View::className();
        }

        return \Yii::createObject($config);
    }

    /**
     * Renders the specified view with optional parameters and layout.
     * The view will be rendered using the [[view]] component.
     * @param string $view the view name or the path alias of the view file.
     * @param array $params the parameters (name-value pairs) that will be extracted and made available in the view file.
     * @param string|boolean $layout layout view name or path alias. If false, no layout will be applied.
     * @return string the rendering result.
     */
    protected function render($view, $params = [], $layout = false) {
        $output = $this->getView()->render($view, $params, $this);
        if ($layout !== false) {
            return $this->getView()->render(
                $layout,
                ['content' => $output],
                $this
            );
        } else {
            return $output;
        }
    }

    /**
     * @return string the directory that contains the view files for composing mail messages
     * Defaults to '@app/mail'.
     */
    public function getViewPath() {
        if ($this->_viewPath === null) {
            $this->setViewPath('@app/mail');
        }
        return $this->_viewPath;
    }

    /**
     * @param string $path the directory that contains the view files for composing mail messages
     * This can be specified as an absolute path or a path alias.
     */
    protected function setViewPath($path) {
        $this->_viewPath = \Yii::getAlias($path);
    }

    /**
     * This method is invoked right before mail send.
     * You may override this method to do last-minute preparation for the message.
     * If you override this method, please make sure you call the parent implementation first.
     * @param MessageInterface $message
     * @return boolean whether to continue sending an email.
     */
    public function beforeSend($message) {
        $event = new MailEvent(['message' => $message]);
        $this->trigger(self::EVENT_BEFORE_SEND, $event);

        return $event->isValid;
    }

    /**
     * This method is invoked right after mail was send.
     * You may override this method to do some postprocessing or logging based on mail send status.
     * If you override this method, please make sure you call the parent implementation first.
     * @param MessageInterface $message
     * @param boolean $isSuccessful
     */
    public function afterSend($message, $isSuccessful) {
        $event = new MailEvent(['message' => $message, 'isSuccessful' => $isSuccessful]);
        $this->trigger(self::EVENT_AFTER_SEND, $event);
    }

    /**
     * Disables email body copy for next email message would be sent
     * @return $this static self reference
     */
    public function disableCopy() {
        $this->_needCopy = false;
        return $this;
    }

    /**
     * Disables email tracking copy for next email message would be sent
     * @return $this static self reference
     */
    public function disableTracking() {
        $this->_track = false;
        return $this;
    }

    /**
     * Gets last sending result
     * @return boolean
     */
    public function getLastResult() {
        return (boolean)$this->_lastResult;
    }

    /**
     * Sets HTML message layout
     * @param string|boolean $layout
     * @return $this static self reference
     */
    public function setHtmlLayout($layout) {
        $this->_htmlLayout = $layout;
        return $this;
    }

    /**
     * Gets HTML message layout
     * @return string|boolean
     */
    public function getHtmlLayout() {
        return $this->_htmlLayout;
    }

    /**
     * Sets TEXT message layout
     * @param string|boolean $layout
     * @return $this static self reference
     */
    public function setTextLayout($layout) {
        $this->_textLayout = $layout;
        return $this;
    }

    /**
     * Gets TEXT message layout
     * @return string|boolean
     */
    public function getTextLayout() {
        return $this->_textLayout;
    }

    /**
     * Sets View parameters
     * @param array $params
     * @return $this static self reference
     */
    public function setViewParams(array $params) {
        $this->view->params = $params;
        return $this;
    }

    /**
     * Disables queue for emails
     * @return $this static self reference
     */
    public function queueOff() {
        $this->_enableQueue = false;
        return $this;
    }

    /**
     * Enabled queue for emails
     * @return $this static self reference
     */
    public function queueOn() {
        $this->_enableQueue = true;
        return $this;
    }

    /**
     * Sets callback responsible for message copies saving
     * @param callable $callback
     * @return $this static self reference
     */
    public function setCopyCallback($callback) {
        $this->_copyCallback = $callback;
        return $this;
    }
}
