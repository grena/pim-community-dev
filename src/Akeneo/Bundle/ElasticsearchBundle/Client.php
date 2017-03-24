<?php

namespace Akeneo\Bundle\ElasticsearchBundle;

use Akeneo\Bundle\ElasticsearchBundle\Exception\MissingIdentifierException;
use Elasticsearch\Client as NativeClient;
use Elasticsearch\ClientBuilder;

/**
 * Wrapper for the PHP Elasticsearch client.
 * To learn more, please see {@link https://www.elastic.co/guide/en/elasticsearch/client/php-api/current/index.html}.
 *
 * @author    Julien Janvier <j.janvier@gmail.com>
 * @copyright 2017 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Client
{
    /** @var ClientBuilder */
    private $builder;

    /** @var array */
    private $hosts;

    /** @var string */
    private $indexName;

    /** @var NativeClient */
    private $client;

    /** @var string */
    private $scroll;

    /** @var int */
    private $size;

    /**
     * Configure the PHP Elasticsearch client.
     * To learn more, please see {@link https://www.elastic.co/guide/en/elasticsearch/client/php-api/current/_configuration.html}
     * and {@link https://www.elastic.co/guide/en/elasticsearch/client/php-api/current/_search_operations.html#_scan_scroll}
     *
     * @param ClientBuilder $builder
     * @param array         $hosts
     * @param string        $indexName
     * @param string        $scroll
     * @param int           $size
     */
    public function __construct(ClientBuilder $builder, array $hosts, $indexName, $scroll, $size)
    {
        $this->builder = $builder;
        $this->hosts = $hosts;
        $this->indexName = $indexName;
        $this->scroll = $scroll;
        $this->size = $size;

        $builder->setHosts($hosts);
        $this->client = $builder->build();
    }

    /**
     * @param string $indexType
     * @param string $id
     * @param array  $body
     *
     * @return array see {@link https://www.elastic.co/guide/en/elasticsearch/client/php-api/current/_quickstart.html#_index_a_document}
     */
    public function index($indexType, $id, array $body)
    {
        $params = [
            'index' => $this->indexName,
            'type'  => $indexType,
            'id'    => $id,
            'body'  => $body
        ];

        return $this->client->index($params);
    }

    /**
     * @param string $indexType
     * @param array  $documents
     * @param string $keyAsId
     *
     * @throws MissingIdentifierException
     *
     * @return array see {@link https://www.elastic.co/guide/en/elasticsearch/client/php-api/current/_indexing_documents.html#_bulk_indexing}
     */
    public function bulkIndexes($indexType, $documents, $keyAsId)
    {
        $params = [];

        foreach ($documents as $document) {
            if (!isset($document[$keyAsId])) {
                throw new MissingIdentifierException(sprintf('Missing "%s" key in document', $keyAsId));
            }

            $params['body'][] = [
                'index' => [
                    '_index' => $this->indexName,
                    '_type'  => $indexType,
                    '_id'    => $document[$keyAsId]
                ]
            ];

            $params['body'][] = $document;
        }

        return $this->client->bulk($params);
    }

    /**
     * @param string $indexType
     * @param string $id
     *
     * @return array see {@link https://www.elastic.co/guide/en/elasticsearch/client/php-api/current/_quickstart.html#_get_a_document}
     */
    public function get($indexType, $id)
    {
        $params = [
            'index' => $this->indexName,
            'type'  => $indexType,
            'id'    => $id,
        ];

        return $this->client->get($params);
    }

    /**
     * @param string $indexType
     * @param array  $body
     *
     * @return array see {@link https://www.elastic.co/guide/en/elasticsearch/client/php-api/current/_quickstart.html#_search_for_a_document}
     */
    public function search($indexType, array $body)
    {
        $params = [
            'scroll' => $this->scroll,
            'size'  => $this->size,
            'index' => $this->indexName,
            'type'  => $indexType,
            'body'  => $body
        ];

        return $this->client->search($params);
    }

    /**
     * @param string $scrollId
     *
     * @return array see {@link https://www.elastic.co/guide/en/elasticsearch/client/php-api/current/ElasticsearchPHP_Endpoints.html#Elasticsearch_Clientscroll_scroll}
     */
    public function scroll($scrollId)
    {
        $params = [
            'scroll_id' => $scrollId,
            'scroll' => $this->scroll
        ];

        return $this->client->scroll($params);
    }

    /**
     * @param string $scrollId
     *
     * @return array see {@link https://www.elastic.co/guide/en/elasticsearch/client/php-api/current/ElasticsearchPHP_Endpoints.html#Elasticsearch_Clientscroll_scroll}
     */
    public function clearScroll($scrollId)
    {
        $params = [
            'scroll_id' => $scrollId,
            'scroll' => $this->scroll
        ];

        return $this->client->clearScroll($params);
    }

    /**
     * @param string $indexType
     * @param string $id
     *
     * @return array see {@link https://www.elastic.co/guide/en/elasticsearch/client/php-api/current/_quickstart.html#_delete_a_document}
     */
    public function delete($indexType, $id)
    {
        $params = [
            'index' => $this->indexName,
            'type'  => $indexType,
            'id'    => $id
        ];

        return $this->client->delete($params);
    }

    /**
     * @return array see {@link https://www.elastic.co/guide/en/elasticsearch/client/php-api/current/_quickstart.html#_delete_an_index}
     */
    public function deleteIndex()
    {
        return $this->client->indices()->delete(['index' => $this->indexName]);
    }

    /**
     * @param array $body
     *
     * @return array see {@link https://www.elastic.co/guide/en/elasticsearch/client/php-api/current/_quickstart.html#_create_an_index}
     */
    public function createIndex(array $body)
    {
        $params = [
            'index' => $this->indexName,
            'body'  => $body,
        ];

        return $this->client->indices()->create($params);
    }

    /**
     * See {@link https://www.elastic.co/guide/en/elasticsearch/client/php-api/current/ElasticsearchPHP_Endpoints.html#Elasticsearch_Namespaces_IndicesNamespaceexists_exists}
     *
     * @return bool
     */
    public function hasIndex()
    {
        return $this->client->indices()->exists(['index' => $this->indexName]);
    }

    /**
     * @return array see {@link https://www.elastic.co/guide/en/elasticsearch/reference/current/indices-refresh.html}
     */
    public function refreshIndex()
    {
        return $this->client->indices()->refresh(['index' => $this->indexName]);
    }
}
