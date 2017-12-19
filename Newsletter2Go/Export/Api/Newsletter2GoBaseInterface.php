<?php

namespace Newsletter2Go\Export\Api;

/**
 * @api
 */
interface Newsletter2GoBaseInterface
{
    /**
     * Test connection call
     *
     * @return \Newsletter2Go\Export\Api\Data\ResponseInterface
     */
    public function testConnection();

    /**
     * Returns plugin version
     *
     * @return \Newsletter2Go\Export\Api\Data\ResponseInterface
     */
    public function pluginVersion();

    /**
     * Returns list of store views with language codes
     *
     * @return \Newsletter2Go\Export\Api\Data\ResponseInterface
     */
    public function getStores();
}
