<?php

namespace Newsletter2Go\Export\Api;

/**
 * @api
 */
interface Newsletter2GoItemInterface
{
    /**
     * Retrieves product by id or sku
     *
     * @param string $itemId
     *
     * @return \Newsletter2Go\Export\Api\Data\ResponseInterface
     */
    public function getItem($itemId);

    /**
     * Retrieves product fields
     *
     * @return \Newsletter2Go\Export\Api\Data\ResponseInterface
     */
    public function getItemFields();
}
