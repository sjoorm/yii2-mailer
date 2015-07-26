<?php
/**
 * Created by PhpStorm.
 * Author: Alexey Tishchenko
 * Email: tischenkoalexey1@gmail.com
 * oDesk: https://www.odesk.com/users/%7E01ad7ed1a6ade4e02e
 * Date: 22.11.14
 */
namespace sjoorm\yii\components\interfaces;
use yii\mail\MailerInterface;
/**
 * Class MailerTemplateInterface describes mailer that is able to send mail templates
 * @package sjoorm\yii\components\mail
 */
interface MailerTemplateInterface extends MailerInterface {

    /**
     * @param string $template name of the template
     * @param array $content template content variables
     * @return MessageTemplateInterface
     */
    public function composeTemplate($template, array $content = []);
}
