<?php
/**
 * Created by PhpStorm.
 * Author: Alexey Tishchenko
 * Email: tischenkoalexey1@gmail.com
 * oDesk: https://www.odesk.com/users/%7E01ad7ed1a6ade4e02e
 * Date: 22.11.14
 */
namespace sjoorm\yii\components\interfaces;
use yii\mail\MessageInterface;
/**
 * Interface MessageTemplateInterface describes messages that can be sent as template ones
 * @package sjoorm\yii\components\mail
 */
interface MessageTemplateInterface extends MessageInterface {
    /**
     * Sets merge variables
     * @param array $data
     * @param string|null $email
     * @return mixed
     */
    public function setMergeVariables(array $data, $email);

    /**
     * Gets merge variables
     * @param string|null $email which email address's
     * variables should be returned. null for all
     * @return array
     */
    public function getMergeVariables($email);
}
