<?php

declare(strict_types=1);

namespace Akeneo\Pim\Enrichment\Bundle\Controller\ExternalApi;

use Akeneo\Pim\Enrichment\Component\Product\Query\Filter\Operators;
use Akeneo\Pim\Enrichment\Component\Product\Query\ProductQueryBuilderInterface;
use Akeneo\Tool\Bundle\ApiBundle\Checker\QueryParametersCheckerInterface;
use Akeneo\Tool\Component\StorageUtils\Repository\IdentifiableObjectRepositoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/**
 * @copyright 2019 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
final class ApplyProductSearchQueryParametersToPQB
{
    /** @var QueryParametersCheckerInterface */
    private $queryParametersChecker;

    /** @var IdentifiableObjectRepositoryInterface */
    private $channelRepository;

    public function __construct(
        QueryParametersCheckerInterface $queryParametersChecker,
        IdentifiableObjectRepositoryInterface $channelRepository
    ) {
        $this->queryParametersChecker = $queryParametersChecker;
        $this->channelRepository = $channelRepository;
    }

    /**
     * Set the PQB filters.
     * If a scope is requested, add a filter to return only products linked to its category tree
     *
     * @param ProductQueryBuilderInterface $pqb
     * @param Request                      $request
     *
     * @throws UnprocessableEntityHttpException
     */
    public function apply(
        ProductQueryBuilderInterface $pqb,
        Request $request
    ): void {
        $searchParameters = [];

        if ($request->query->has('search')) {
            $searchString = $request->query->get('search', '');
            $searchParameters = $this->queryParametersChecker->checkCriterionParameters($searchString);

            if (isset($searchParameters['categories'])) {
                $this->queryParametersChecker->checkCategoriesParameters($searchParameters['categories']);
            }
        }

        if (!isset($searchParameters['categories'])) {
            $channel = $this->channelRepository->findOneByIdentifier($request->get('scope', null));
            if (null !== $channel) {
                $searchParameters['categories'] = [
                    [
                        'operator' => Operators::IN_CHILDREN_LIST,
                        'value'    => [$channel->getCategory()->getCode()]
                    ]
                ];
            }
        }

        foreach ($searchParameters as $propertyCode => $filters) {
            foreach ($filters as $filter) {
                $searchLocale = $request->query->get('search_locale');
                $context['locale'] = isset($filter['locale']) ? $filter['locale'] : $searchLocale;

                if (null !== $context['locale'] && is_string($context['locale'])) {
                    $locales = explode(',', $context['locale']);
                    $this->queryParametersChecker->checkLocalesParameters($locales);
                }

                $context['scope'] = isset($filter['scope']) ? $filter['scope'] : $request->query->get('search_scope');

                if (isset($filter['locales']) && '' !== $filter['locales']) {
                    $context['locales'] = $filter['locales'];

                    $this->queryParametersChecker->checkLocalesParameters(
                        !is_array($context['locales']) ? [$context['locales']] : $context['locales']
                    );
                }

                $value = isset($filter['value']) ? $filter['value'] : null;

                if (in_array($propertyCode, ['created', 'updated'])) {
                    if (Operators::BETWEEN === $filter['operator'] && is_array($value)) {
                        $values = [];
                        foreach ($value as $date) {
                            $values[] = \DateTime::createFromFormat('Y-m-d H:i:s', $date);
                        }
                        $value = $values;
                    } elseif (!in_array($filter['operator'], [Operators::SINCE_LAST_N_DAYS, Operators::SINCE_LAST_JOB])) {
                        //PIM-7541 Create the date with the server timezone configuration. Do not force it to UTC timezone.
                        $value = \DateTime::createFromFormat('Y-m-d H:i:s', $value);
                    }
                }

                $this->queryParametersChecker->checkPropertyParameters($propertyCode, $filter['operator']);

                $pqb->addFilter($propertyCode, $filter['operator'], $value, $context);
            }
        }
    }
}
