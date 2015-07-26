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
 * Interface MailerServiceInterface
 * @package sjoorm\yii\components\mail
 *
 * @property string $service mailer service name
 */
interface MailerServiceInterface extends MailerInterface {

    /**
     * Sets mailer service name
     * @param $service
     * @return mixed
     */
    public function setService($service);

    /**
     * Gets mailer service name
     * @return string
     */
    public function getService();
}
