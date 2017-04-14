<?php

namespace spec\Pim\Bundle\CatalogBundle\Elasticsearch\Sorter\Attributes;

use Pim\Bundle\CatalogBundle\Elasticsearch\SearchQueryBuilder;
use Pim\Bundle\CatalogBundle\Elasticsearch\Sorter\Attribute\TextAreaSorter;
use PhpSpec\ObjectBehavior;
use Pim\Component\Catalog\Exception\InvalidDirectionException;
use Pim\Component\Catalog\Model\AttributeInterface;
use Pim\Component\Catalog\Query\Sorter\AttributeSorterInterface;
use Pim\Component\Catalog\Query\Sorter\Directions;

class TextAreaSorterSpec extends ObjectBehavior
{
    function let()
    {
        $this->beConstructedWith(['pim_catalog_textarea']);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType(TextAreaSorter::class);
    }

    function it_is_an_attribute_sorter()
    {
        $this->shouldImplement(AttributeSorterInterface::class);
    }

    function it_adds_a_sorter_with_operator_ascendant_no_locale_and_no_scope(
        AttributeInterface $aTextArea,
        SearchQueryBuilder $sqb
    ) {
        $aTextArea->getCode()->willReturn('a_text_area');
        $aTextArea->getBackendType()->willReturn('text');
        $sqb->addSort([
            'values.a_text_area-text.<all_channels>.<all_locales>.preprocessed' => [
                'order' => 'ASC',
                'missing' => '_last'
            ]
        ])->shouldBeCalled();

        $this->setQueryBuilder($sqb);
        $this->addAttributeSorter($aTextArea, DIRECTIONS::ASCENDING, null, null);
    }

    function it_adds_a_sorter_with_operator_ascendant_locale_and_scope(
        AttributeInterface $aTextArea,
        SearchQueryBuilder $sqb
    ) {
        $aTextArea->getCode()->willReturn('a_text_area');
        $aTextArea->getBackendType()->willReturn('text');

        $sqb->addSort([
            'values.a_text_area-text.ecommerce.fr_FR.preprocessed' => [
                'order' => 'ASC',
                'missing' => '_last'
            ]
        ])->shouldBeCalled();

        $this->setQueryBuilder($sqb);
        $this->addAttributeSorter($aTextArea, DIRECTIONS::ASCENDING, 'fr_FR', 'ecommerce');
    }

    function it_adds_a_sorter_with_operator_descendant_locale_and_scope(
        AttributeInterface $aTextArea,
        SearchQueryBuilder $sqb
    ) {
        $aTextArea->getCode()->willReturn('a_text_area');
        $aTextArea->getBackendType()->willReturn('text');

        $sqb->addSort([
            'values.a_text_area-text.ecommerce.fr_FR.preprocessed' => [
                'order' => 'DESC',
                'missing' => '_last'
            ]
        ])->shouldBeCalled();

        $this->setQueryBuilder($sqb);
        $this->addAttributeSorter($aTextArea, DIRECTIONS::DESCENDING, 'fr_FR', 'ecommerce');
    }

    function it_supports_only_text_area_attribute(
        AttributeInterface $aTextArea,
        AttributeInterface $aPrice
    ) {
        $aTextArea->getType()->willReturn('pim_catalog_textarea');
        $aPrice->getType()->willReturn('pim_catalog_price');

        $this->supportsAttribute($aTextArea)->shouldReturn(true);
        $this->supportsAttribute($aPrice)->shouldReturn(false);
    }

    function it_throws_an_exception_when_the_search_query_builder_is_not_initialized(
        AttributeInterface $aTextArea
    ) {
        $this->shouldThrow(
            new \LogicException('The search query builder is not initialized in the sorter.')
        )->during('addAttributeSorter', [$aTextArea, Directions::ASCENDING]);
    }

    function it_throws_an_exception_when_the_directions_does_not_exist(
        AttributeInterface $aTextArea,
        SearchQueryBuilder $sqb
    ) {
        $this->setQueryBuilder($sqb);

        $this->shouldThrow(
            InvalidDirectionException::notSupported(
                'A_BAD_DIRECTION',
                TextAreaSorter::class
            )
        )->during('addAttributeSorter', [$aTextArea, 'A_BAD_DIRECTION']);
    }

}