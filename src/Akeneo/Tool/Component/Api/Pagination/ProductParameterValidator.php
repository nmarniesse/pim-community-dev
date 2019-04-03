<?php

namespace Akeneo\Tool\Component\Api\Pagination;

use Akeneo\Tool\Component\StorageUtils\Repository\IdentifiableObjectRepositoryInterface;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/**
 * TODO: to move it enrichment
 * TODO: remove http exception as it's busisness stuff
 * @copyright 2017 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class ProductParameterValidator implements ParameterValidatorInterface
{
    /** @var IdentifiableObjectRepositoryInterface */
    private $channelRepository;

    public function __construct(IdentifiableObjectRepositoryInterface $channelRepository)
    {
        $this->channelRepository = $channelRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function validate(array $parameters, array $options = [])
    {
        $channel = null;
        if (isset($parameters['scope'])) {
            $channel = $this->channelRepository->findOneByIdentifier($parameters['scope']);
            if (null === $channel) {
                throw new UnprocessableEntityHttpException(
                    sprintf('Scope "%s" does not exist.', $parameters['scope'])
                );
            }
        }
    }
}
