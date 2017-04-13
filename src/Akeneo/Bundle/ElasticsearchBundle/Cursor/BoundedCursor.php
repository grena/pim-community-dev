<?php

namespace Akeneo\Bundle\ElasticsearchBundle\Cursor;

use Akeneo\Bundle\ElasticsearchBundle\Client;
use Akeneo\Component\StorageUtils\Cursor\CursorInterface;
use Akeneo\Component\StorageUtils\Repository\CursorableRepositoryInterface;

/**
 * Bounded cursor to iterate on items where a start and a limit are defined
 *
 * @author    Julien Janvier <jjanvier@akeneo.com>
 * @author    Marie Bochu <marie.bochu@akeneo.com>
 * @copyright 2017 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class BoundedCursor extends Cursor implements CursorInterface
{
    /** @var string|null */
    protected $searchAfterIdentifier;

    /** @var int */
    protected $limit;

    /** @var int */
    protected $fetchedItemsCount;

    /** @var string|null */
    protected $lastItem;

    /** @var int */
    protected $countItemsFetched;

    /**
     * @param Client                        $esClient
     * @param CursorableRepositoryInterface $repository
     * @param array                         $esQuery
     * @param array                         $searchAfter
     * @param string                        $indexType
     * @param int                           $pageSize
     * @param int                           $limit
     * @param string|null                   $searchAfterIdentifier
     */
    public function __construct(
        Client $esClient,
        CursorableRepositoryInterface $repository,
        array $esQuery,
        array $searchAfter = [],
        $indexType,
        $pageSize,
        $limit,
        $searchAfterIdentifier = null
    ) {
        $this->limit = $limit;
        $this->searchAfter = $searchAfter;
        $this->searchAfterIdentifier = $searchAfterIdentifier;

        if (null !== $searchAfterIdentifier) {
            array_push($this->searchAfter, $indexType . '#' . $searchAfterIdentifier);
        }

        parent::__construct($esClient, $repository, $esQuery, $indexType, $pageSize);
    }

    /**
     * @return string
     */
    public function getLastItem()
    {
        return $this->lastItem;
    }

    /**
     * @return int
     */
    public function getCountItemsFetched()
    {
        return $this->countItemsFetched;
    }

    /**
     * {@inheritdoc}
     */
    protected function getItemsCountToFetch()
    {
        $itemsCountToFetch = $this->limit > $this->pageSize ? $this->pageSize : $this->limit;
        if (null !== $this->fetchedItemsCount && ($this->fetchedItemsCount + $itemsCountToFetch) > $this->limit) {
            $itemsCountToFetch = $this->fetchedItemsCount - $this->limit;
        }
        $this->fetchedItemsCount += $itemsCountToFetch;

        return $itemsCountToFetch;
    }

    /**
     * {@inheritdoc}
     */
    protected function getNextIdentifiers(array $esQuery)
    {
        $identifiers = parent::getNextIdentifiers($esQuery);

        $this->countItemsFetched += count($identifiers);

        if (!empty($identifiers)) {
            $this->lastItem = end($identifiers);
        }

        return $identifiers;
    }
}
