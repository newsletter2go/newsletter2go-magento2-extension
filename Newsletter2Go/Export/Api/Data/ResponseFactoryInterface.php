<?php

namespace Newsletter2Go\Export\Api\Data;

interface ResponseFactoryInterface
{
    /**
     * @return \Newsletter2Go\Export\Api\Data\ResponseInterface
     */
    public function create();
}
