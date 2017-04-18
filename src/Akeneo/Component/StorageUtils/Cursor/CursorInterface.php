<?php

namespace Akeneo\Component\StorageUtils\Cursor;

/**
 * Interface CursorInterface
 *
 * @author    Stephane Chapeau <stephane.chapeau@akeneo.com>
 * @copyright 2015 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
interface CursorInterface extends \Countable, \Iterator
{
    /**
     * Get the last item returned by the cursor
     *
     * @return string
     */
    public function getLastItem();

    /**
     * Get the count of items fetched
     *
     * @return int
     */
    public function getCountItemsFetched();
}
