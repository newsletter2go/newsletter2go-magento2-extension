<?php

namespace Newsletter2Go\Export\Model\Api;

use Newsletter2Go\Export\Api\Data\ResponseFactoryInterface;

abstract class AbstractNewsletter2Go
{

    /**
     * err-number for all other (intern) errors. More Details to the failure should be added to error-message
     */
    const ERRNO_PLUGIN_OTHER = 'int-1-600';

    /**
     * @var ResponseFactoryInterface
     */
    protected $responseFactory;

    /**
     * AbstractNewsletter2Go constructor.
     *
     * @param ResponseFactoryInterface $responseFactory
     */
    public function __construct(ResponseFactoryInterface $responseFactory)
    {
        $this->responseFactory = $responseFactory;
    }

    /**
     * Sends error response body
     * @param string $message
     * @return \Newsletter2Go\Export\Api\Data\ResponseInterface
     */
    public function generateErrorResponse($message)
    {
        $response = $this->responseFactory->create();
        $response->setSuccess(false);
        $response->setMessage($message);
        $response->setErrorCode(self::ERRNO_PLUGIN_OTHER);

        return $response;
    }

    /**
     * Sends success response body
     * @param array $data
     * @return \Newsletter2Go\Export\Api\Data\ResponseInterface
     */
    public function generateSuccessResponse($data = [])
    {
        $response = $this->responseFactory->create();
        $response->setSuccess(true);
        $response->setMessage('OK');
        $response->setData(json_encode($data));

        return $response;
    }

    /**
     * Helper function to create field array
     * @param $id
     * @param $name
     * @param $description
     * @param $type
     * @return array
     */
    protected function createArray($id, $name, $description = '', $type = 'String')
    {
        return [
            'id' => $id,
            'name' => $name,
            'description' => $description,
            'type' => $type,
        ];
    }
}
