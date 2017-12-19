<?php

namespace Newsletter2Go\Export\Model\Api;

use Newsletter2Go\Export\Api\Data\ResponseFactoryInterface;

class ResponseFactory implements ResponseFactoryInterface
{
    public function create()
    {
        return new Response();
    }
}
