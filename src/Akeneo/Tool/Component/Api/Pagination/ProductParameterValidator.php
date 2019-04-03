<?php

namespace Akeneo\Tool\Component\Api\Pagination;

use Akeneo\Tool\Bundle\ApiBundle\Checker\QueryParametersCheckerInterface;
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

    /** @var PaginationParameterValidator */
    private $paginationParametersValidator;

    /** @var QueryParametersCheckerInterface */
    private $queryParametersChecker;

    public function __construct(
        IdentifiableObjectRepositoryInterface $channelRepository,
        PaginationParameterValidator $paginationParametersValidator,
        QueryParametersCheckerInterface $queryParametersChecker
    ) {
        $this->channelRepository = $channelRepository;
        $this->paginationParametersValidator = $paginationParametersValidator;
        $this->queryParametersChecker = $queryParametersChecker;
    }

    /**
     * {@inheritdoc}
     */
    public function validate(array $parameters, array $options = [])
    {
        $this->paginationParametersValidator->validate($parameters, $options);

        $channel = null;
        if (isset($parameters['scope'])) {
            $channel = $this->channelRepository->findOneByIdentifier($parameters['scope']);
            if (null === $channel) {
                throw new UnprocessableEntityHttpException(
                    sprintf('Scope "%s" does not exist.', $parameters['scope'])
                );
            }
        }

        if (isset($parameters['locales'])) {
            $locales = explode(',', $parameters['locales']);
            $this->queryParametersChecker->checkLocalesParameters($locales, $channel);
        }
    }
}
