<?php

namespace Specification\Akeneo\Pim\Enrichment\Bundle\Controller\ExternalApi;

use Akeneo\Channel\Component\Model\ChannelInterface;
use Akeneo\Pim\Enrichment\Component\Category\Model\CategoryInterface;
use Akeneo\Pim\Enrichment\Component\Product\Query\Filter\Operators;
use Akeneo\Pim\Enrichment\Component\Product\Query\ProductQueryBuilderInterface;
use Akeneo\Tool\Bundle\ApiBundle\Checker\QueryParametersCheckerInterface;
use Akeneo\Tool\Component\StorageUtils\Repository\IdentifiableObjectRepositoryInterface;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;

class ApplyProductSearchQueryParametersToPQBSpec extends ObjectBehavior {

    function let(
        IdentifiableObjectRepositoryInterface $channelRepository
    ) {
        $this->beConstructedWith($channelRepository);
    }

    function it_adds_no_filter(
        ProductQueryBuilderInterface $pqb,
        Request $request,
        ParameterBag $query
    ) {
        $request->query = $query;
        $pqb->addFilter(Argument::cetera())->shouldNotBeCalled();

        $this->apply($pqb, $request);
    }

    function it_adds_search_filter(
        ProductQueryBuilderInterface $pqb,
        Request $request,
        ParameterBag $query
    ) {
        $request->query = $query;
        $query->has('search')->willReturn(true);
        $query->get('search', '')->willReturn(json_encode(['propertyCode' => [[
            'operator' => 'op',
            'value' => 'val'
        ]]]));
        $query->get('search_locale')->willReturn('en_US')->shouldBeCalled();
        $query->get('search_scope')->willReturn('ecommerce')->shouldBeCalled();

        $pqb->addFilter('propertyCode', 'op', 'val', ['locale' => 'en_US', 'scope' => 'ecommerce'])->shouldBeCalled();

        $this->apply($pqb, $request);
    }

    function it_adds_default_category_from_scope(
        ProductQueryBuilderInterface $pqb,
        IdentifiableObjectRepositoryInterface $channelRepository,
        Request $request,
        ParameterBag $query,
        ChannelInterface $channel,
        CategoryInterface $category
    ) {
        $request->query = $query;
        $query->has('search')->willReturn(false);
        $request->get('scope', null)->willReturn('ecommerce');
        $channelRepository->findOneByIdentifier('ecommerce')->willReturn($channel)->shouldBeCalled();
        $channel->getCategory()->willReturn($category)->shouldBeCalled();
        $category->getCode()->willReturn('categoryCode')->shouldBeCalled();
        $query->get('search_locale')->willReturn('en_US');
        $query->get('search_scope')->willReturn('ecommerce');

        $pqb->addFilter('categories', Operators::IN_CHILDREN_LIST, ['categoryCode'], ['locale' => 'en_US', 'scope' => 'ecommerce'])->shouldBeCalled();

        $this->apply($pqb, $request);
    }

    function it_adds_search_filter_specifying_scope_and_locale(
        ProductQueryBuilderInterface $pqb,
        Request $request,
        ParameterBag $query
    ) {
        $request->query = $query;
        $query->has('search')->willReturn(true);
        $query->get('search', '')->willReturn(json_encode(['propertyCode' => [[
            'operator' => 'op',
            'value' => 'val',
            'scope' => 'mobile',
            'locale'=> 'fr_FR'
        ]]]));
        $query->get('search_locale')->willReturn('en_US')->shouldBeCalled();

        $pqb->addFilter('propertyCode', 'op', 'val', ['locale' => 'fr_FR', 'scope' => 'mobile'])->shouldBeCalled();

        $this->apply($pqb, $request);
    }

    function it_adds_search_filter_for_datetimes(
        ProductQueryBuilderInterface $pqb,
        Request $request,
        ParameterBag $query
    ) {
        $request->query = $query;
        $query->has('search')->willReturn(true);
        $query->get('search', '')->willReturn(json_encode(['created' => [[
            'operator' => Operators::BETWEEN,
            'value' => ['2019-01-28 12:12:12', '2019-02-28 13:13:13'],
        ]], 'updated' => [[
            'operator' => Operators::LOWER_THAN,
            'value' => '2020-03-38 14:14:14'
        ]]]));
        $query->get('search_locale')->willReturn('en_US')->shouldBeCalled();
        $query->get('search_scope')->willReturn('ecommerce')->shouldBeCalled();

        $pqb->addFilter('created', Operators::BETWEEN, Argument::any(), ['locale' => 'en_US', 'scope' => 'ecommerce'])->shouldBeCalled();
        $pqb->addFilter('updated', Operators::LOWER_THAN, Argument::type(\DateTime::class), ['locale' => 'en_US', 'scope' => 'ecommerce'])->shouldBeCalled();

        $this->apply($pqb, $request);
    }
}
