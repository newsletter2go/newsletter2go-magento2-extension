<?php

namespace Newsletter2Go\Export\Model\Api;

use Newsletter2Go\Export\Api\Data\ResponseInterface;

/**
 * @api
 */
class Response implements ResponseInterface
{
    /**
     * @var boolean
     */
    private $success;

    /**
     * @var string
     */
    private $message;

    /**
     * @var string
     */
    private $errorCode;

    /**
     * @var string
     */
    private $data;

    public function getData()
    {
        return $this->data;
    }

    public function setData($data)
    {
        $this->data = $data;

        return $this;
    }

    public function isSuccess()
    {
        return $this->success;
    }

    public function setSuccess($success)
    {
        $this->success = $success;

        return $this;
    }

    public function getMessage()
    {
        return $this->message;
    }

    public function setMessage($message)
    {
        $this->message = $message;

        return $this;
    }

    public function getErrorcode()
    {
        return $this->errorCode;
    }

    public function setErrorCode($errorCode)
    {
        $this->errorCode = $errorCode;

        return $this;
    }
}
