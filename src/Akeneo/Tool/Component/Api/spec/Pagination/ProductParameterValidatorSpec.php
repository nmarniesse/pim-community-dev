<?php

namespace spec\Akeneo\Tool\Component\Api\Pagination;

use Akeneo\Channel\Bundle\Doctrine\Repository\ChannelRepository;
use Akeneo\Channel\Component\Model\Channel;
use Akeneo\Tool\Bundle\ApiBundle\Checker\QueryParametersCheckerInterface;
use Akeneo\Tool\Component\Api\Pagination\PaginationParameterValidator;
use Akeneo\Tool\Component\Api\Pagination\ProductParameterValidator;
use Akeneo\Tool\Component\StorageUtils\Repository\IdentifiableObjectRepositoryInterface;
use PhpSpec\ObjectBehavior;
use Akeneo\Tool\Component\Api\Pagination\ParameterValidatorInterface;
use Prophecy\Argument;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class ProductParameterValidatorSpec extends ObjectBehavior
{
    function let(
        IdentifiableObjectRepositoryInterface $channelRepository,
        PaginationParameterValidator $paginationParameterValidator,
        QueryParametersCheckerInterface $queryParametersChecker
    ) {
        $this->beConstructedWith($channelRepository, $paginationParameterValidator, $queryParametersChecker);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType(ProductParameterValidator::class);
    }

    function it_is_a_parameter_validator()
    {
        $this->shouldImplement(ParameterValidatorInterface::class);
    }

    public function it_throws_an_exception_with_non_existing_channel(ChannelRepository $channelRepository)
    {
        $channelRepository->findOneByIdentifier('foo')->willReturn(null)->shouldBeCalled();

        $this->shouldThrow(UnprocessableEntityHttpException::class)->during('validate', [['scope' => 'foo']]);
    }

    public function it_does_not_throw_an_exception_with_an_existing_channel(ChannelRepository $channelRepository)
    {
        $channelRepository->findOneByIdentifier('foo')->willReturn(new Channel())->shouldBeCalled();

        $this->shouldNotThrow(UnprocessableEntityHttpException::class)->during('validate', [['scope' => 'foo']]);
    }

    public function it_does_not_throw_an_exception_when_there_is_no_channel_provided(ChannelRepository $channelRepository)
    {
        $channelRepository->findOneByIdentifier('foo')->shouldNotBeCalled();

        $this->shouldNotThrow(UnprocessableEntityHttpException::class)->during('validate', [[]]);
    }

    public function it_validates_pagination_parameters(PaginationParameterValidator $paginationParameterValidator)
    {
        $paginationParameterValidator->validate([], [])->shouldBeCalledOnce();
        $this->validate([], []);
    }

    public function it_validates_that_locales_exist_and_are_activated(QueryParametersCheckerInterface $queryParametersChecker)
    {
        $queryParametersChecker->checkLocalesParameters(['en_US', 'fr_FR'], null)->shouldBeCalled();
        $this->validate(['locales' => 'en_US,fr_FR'],[]);
    }

    public function it_validates_that_locales_are_activated_for_the_provided_channel(
        QueryParametersCheckerInterface $queryParametersChecker,
        ChannelRepository $channelRepository
    ) {
        $channel = new Channel();
        $channelRepository->findOneByIdentifier('ecommerce')->willReturn($channel);

        $queryParametersChecker->checkLocalesParameters(['en_US', 'fr_FR'], $channel)->shouldBeCalled();
        $this->validate(['locales' => 'en_US,fr_FR', 'scope' => 'ecommerce'],[]);
    }

}
