<?php

namespace Newsletter2Go\Export\Api\Data;

/**
 * @api
 */
interface ResponseInterface
{
    /**
     * @return boolean
     */
    public function isSuccess();

    /**
     * @param boolean $success
     *
     * @return $this
     */
    public function setSuccess($success);

    /**
     * @return string
     */
    public function getMessage();

    /**
     * @param string $message
     *
     * @return $this
     */
    public function setMessage($message);

    /**
     * @return string
     */
    public function getErrorCode();

    /**
     * @param string $errorCode
     *
     * @return $this
     */
    public function setErrorCode($errorCode);

    /**
     * @return string
     */
    public function getData();

    /**
     * @param string $data
     *
     * @return $this
     */
    public function setData($data);
}
