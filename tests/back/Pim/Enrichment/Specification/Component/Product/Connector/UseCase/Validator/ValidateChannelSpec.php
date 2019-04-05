<?php

namespace Specification\Akeneo\Pim\Enrichment\Component\Product\Connector\UseCase\Validator;

use Akeneo\Channel\Bundle\Doctrine\Repository\ChannelRepository;
use Akeneo\Channel\Component\Model\Channel;
use Akeneo\Pim\Enrichment\Component\Product\Connector\UseCase\Validator\ValidateChannel;
use Akeneo\Tool\Component\StorageUtils\Repository\IdentifiableObjectRepositoryInterface;
use PhpSpec\ObjectBehavior;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class ValidateChannelSpec extends ObjectBehavior
{
    function let(IdentifiableObjectRepositoryInterface $channelRepository)
    {
        $this->beConstructedWith($channelRepository);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType(ValidateChannel::class);
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

    public function it_does_not_throw_an_exception_when_there_is_no_channel_provided(
        ChannelRepository $channelRepository
    ) {
        $channelRepository->findOneByIdentifier('foo')->shouldNotBeCalled();

        $this->shouldNotThrow(UnprocessableEntityHttpException::class)->during('validate', [[]]);
    }
}
