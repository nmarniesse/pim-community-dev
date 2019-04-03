<?php

namespace spec\Akeneo\Tool\Component\Api\Pagination;

use Akeneo\Channel\Bundle\Doctrine\Repository\ChannelRepository;
use Akeneo\Channel\Component\Model\Channel;
use Akeneo\Tool\Component\Api\Pagination\ProductParameterValidator;
use Akeneo\Tool\Component\StorageUtils\Repository\IdentifiableObjectRepositoryInterface;
use PhpSpec\ObjectBehavior;
use Akeneo\Tool\Component\Api\Pagination\ParameterValidatorInterface;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class ProductParameterValidatorSpec extends ObjectBehavior
{
    function let(IdentifiableObjectRepositoryInterface $channelRepository)
    {
        $this->beConstructedWith($channelRepository);
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
}
