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
 * Interface MessageCopyInterface describes message which copy
 * can be stored in DB after sending
 * @package sjoorm\yii\components\mail
 *
 * @property integer $id message unique ID
 * @property string $content message content (HTML)
 */
interface MessageCopyInterface extends MessageInterface {

    /**
     * Gets message unique ID
     * @return string
     */
    public function getId();

    /**
     * Gets message content of specified format
     * @param string $format which content should be returned. 'html' or 'text'
     * @return mixed
     */
    public function getContent($format = null);

    /**
     * Saves message copy in the DB
     * @param callable $callback callback responsible for message copy saving
     * @param boolean $body if body content should be included into the copy
     */
    public function saveCopy(callable $callback, $body = true);
}
