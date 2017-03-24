<?php

namespace spec\Pim\Component\Catalog\Query;

use Akeneo\Bundle\ElasticsearchBundle\Client as ElasticSearchClient;
use Akeneo\Component\StorageUtils\Cursor\CursorFactoryInterface;
use Akeneo\Component\StorageUtils\Cursor\CursorInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use PhpSpec\ObjectBehavior;
use Pim\Bundle\CatalogBundle\Elasticsearch\SearchQueryBuilder;
use Pim\Component\Catalog\Model\AttributeInterface;
use Pim\Component\Catalog\Model\Product;
use Pim\Component\Catalog\Query\Filter\AttributeFilterInterface;
use Pim\Component\Catalog\Query\Filter\FieldFilterInterface;
use Pim\Component\Catalog\Query\Filter\FilterRegistryInterface;
use Pim\Component\Catalog\Query\Sorter\AttributeSorterInterface;
use Pim\Component\Catalog\Query\Sorter\FieldSorterInterface;
use Pim\Component\Catalog\Query\Sorter\SorterRegistryInterface;
use Pim\Component\Catalog\Repository\AttributeRepositoryInterface;
use Prophecy\Argument;

class ProductQueryBuilderSpec extends ObjectBehavior
{
    function let(
        AttributeRepositoryInterface $repository,
        FilterRegistryInterface $filterRegistry,
        SorterRegistryInterface $sorterRegistry,
        CursorFactoryInterface $cursorFactory,
        ElasticSearchClient $searchEngine,
        SearchQueryBuilder $searchQb,
        EntityManagerInterface $entityManager
    ) {
        $this->beConstructedWith(
            $repository,
            $filterRegistry,
            $sorterRegistry,
            $cursorFactory,
            $searchEngine,
            $entityManager,
            ['locale' => 'en_US', 'scope' => 'print']
        );
        $this->setQueryBuilder($searchQb);
    }

    function it_is_a_product_query_builder()
    {
        $this->shouldImplement('Pim\Component\Catalog\Query\ProductQueryBuilderInterface');
    }

    function it_adds_a_field_filter($repository, $filterRegistry, FieldFilterInterface $filter)
    {
        $repository->findOneByIdentifier('id')->willReturn(null);
        $filterRegistry->getFieldFilter('id', '=')->willReturn($filter);
        $filter->setQueryBuilder(Argument::any())->shouldBeCalled();
        $filter->addFieldFilter(
            'id',
            '=',
            '42',
            'en_US',
            'print',
            ['locale' => 'en_US', 'scope' => 'print']
        )->shouldBeCalled();

        $this->addFilter('id', '=', '42', []);
    }

    function it_adds_a_field_filter_even_if_an_attribute_is_similar(
        $repository,
        $filterRegistry,
        AttributeInterface $attribute,
        FieldFilterInterface $filter
    ) {
        $repository->findOneByIdentifier('id')->willReturn($attribute);
        $attribute->getCode()->willReturn('ID');
        $filterRegistry->getFieldFilter('id', '=')->willReturn($filter);
        $filter->setQueryBuilder(Argument::any())->shouldBeCalled();
        $filter->addFieldFilter(
            'id',
            '=',
            '42',
            'en_US',
            'print',
            ['locale' => 'en_US', 'scope' => 'print']
        )->shouldBeCalled();

        $this->addFilter('id', '=', '42', []);
    }

    function it_adds_an_attribute_filter(
        $repository,
        $filterRegistry,
        AttributeFilterInterface $filter,
        AttributeInterface $attribute
    ) {
        $repository->findOneByIdentifier('sku')->willReturn($attribute);
        $attribute->getCode()->willReturn('sku');
        $filterRegistry->getAttributeFilter($attribute, '=')->willReturn($filter);
        $attribute->isScopable()->willReturn(true);
        $attribute->isLocalizable()->willReturn(true);
        $filter->setQueryBuilder(Argument::any())->shouldBeCalled();
        $filter->addAttributeFilter(
            $attribute,
            '=', '42',
            'en_US',
            'print',
            ['locale' => 'en_US', 'scope' => 'print', 'field' => 'sku']
        )->shouldBeCalled();

        $this->addFilter('sku', '=', '42', []);
    }

    function it_adds_a_field_sorter($repository, $sorterRegistry, FieldSorterInterface $sorter)
    {
        $repository->findOneBy(['code' => 'id'])->willReturn(null);
        $sorterRegistry->getFieldSorter('id')->willReturn($sorter);
        $sorter->setQueryBuilder(Argument::any())->shouldBeCalled();
        $sorter->addFieldSorter('id', 'DESC', 'en_US', 'print')->shouldBeCalled();

        $this->addSorter('id', 'DESC', []);
    }

    function it_adds_an_attribute_sorter(
        $repository,
        $sorterRegistry,
        AttributeSorterInterface $sorter,
        AttributeInterface $attribute
    ) {
        $repository->findOneBy(['code' => 'sku'])->willReturn($attribute);
        $sorterRegistry->getAttributeSorter($attribute)->willReturn($sorter);
        $sorter->setQueryBuilder(Argument::any())->shouldBeCalled();
        $sorter->addAttributeSorter($attribute, 'DESC', 'en_US', 'print')->shouldBeCalled();

        $this->addSorter('sku', 'DESC', []);
    }

    function it_provides_a_query_builder_once_configured($searchQb)
    {
        $this->getQueryBuilder()->shouldReturn($searchQb);
    }

    function it_configures_the_query_builder($searchQb)
    {
        $this->setQueryBuilder($searchQb)->shouldReturn($this);
    }

    function it_executes_the_query(
        $searchQb,
        $searchEngine,
        $entityManager,
        CursorFactoryInterface $cursorFactory,
        CursorInterface $cursor,
        QueryBuilder $queryBuilder
    ) {
        $searchQb->getQuery()->willReturn(['a native ES query']);

        $searchEngineResults1 = [
            '_scroll_id' => 'first_batch',
            'hits' => [
                'hits' => [
                    ['_source' => ['identifier' => 'result1']],
                    ['_source' => ['identifier' => 'result2']],
                    ['_source' => ['identifier' => 'result3']],
                ]
            ]
        ];
        $searchEngine->search('pim_catalog_product', ['a native ES query'])->willReturn($searchEngineResults1);

        $searchEngineResults2 = [
            '_scroll_id' => 'second_batch',
            'hits' => [
                'hits' => [
                    ['_source' => ['identifier' => 'result4']],
                    ['_source' => ['identifier' => 'result5']],
                    ['_source' => ['identifier' => 'result6']],
                ]
            ]
        ];
        $searchEngine->scroll('first_batch')->willReturn($searchEngineResults2);

        $searchEngineResults3 = [
            '_scroll_id' => 'third_batch',
            'hits' => [
                'hits' => []
            ]
        ];
        $searchEngine->scroll('second_batch')->willReturn($searchEngineResults3);

        $entityManager->createQueryBuilder()->willReturn($queryBuilder);
        $queryBuilder->select('p')->willReturn($queryBuilder);
        $queryBuilder->from(Product::class, 'p')->willReturn($queryBuilder);
        $queryBuilder->where('p.identifier IN (:identifiers)')->willReturn($queryBuilder);
        $queryBuilder->setParameter('identifiers',
            ['result1', 'result2', 'result3', 'result4', 'result5', 'result6']
        )->willReturn($queryBuilder);

        $cursorFactory->createCursor(Argument::any())->shouldBeCalled()->willReturn($cursor);

        $this->execute()->shouldReturn($cursor);
    }
}
