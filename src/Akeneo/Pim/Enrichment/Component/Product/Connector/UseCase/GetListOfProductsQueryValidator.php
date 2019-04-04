<?php

declare(strict_types=1);

namespace Akeneo\Pim\Enrichment\Component\Product\Connector\UseCase;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/**
 * TODO Move ?
 *
 * @author Pierre Allard <pierre.allard@akeneo.com>
 */
class GetListOfProductsQueryValidator
{
    /**
     * @param GetListOfProductsQuery $query
     *
     * @throws BadRequestHttpException
     * @throws UnprocessableEntityHttpException
     */
    public function validate(GetListOfProductsQuery $query)
    {
        $this->validateCriterionParameters($query);
    }

    /**
     * @param GetListOfProductsQuery $query
     *
     * @throws BadRequestHttpException
     * @throws UnprocessableEntityHttpException
     */
    private function validateCriterionParameters(GetListOfProductsQuery $query) {

        if (null === $query->search) {
            throw new BadRequestHttpException('Search query parameter should be valid JSON.');
        }

        if (!is_array($query->search)) {
            throw new UnprocessableEntityHttpException(
                sprintf('Search query parameter has to be an array, "%s" given.', gettype($query->search))
            );
        }

        foreach ($query->search as $searchKey => $searchParameter) {
            if (!is_array($query->search) || !isset($searchParameter[0])) {
                throw new UnprocessableEntityHttpException(
                    sprintf(
                        'Structure of filter "%s" should respect this structure: %s',
                        $searchKey,
                        sprintf('{"%s":[{"operator": "my_operator", "value": "my_value"}]}', $searchKey)
                    )
                );
            }

            foreach ($searchParameter as $searchFilter) {
                if (!isset($searchFilter['operator'])) {
                    throw new UnprocessableEntityHttpException(
                        sprintf('Operator is missing for the property "%s".', $searchKey)
                    );
                }

                if (!is_string($searchFilter['operator'])) {
                    throw new UnprocessableEntityHttpException(
                        sprintf('Operator has to be a string, "%s" given.', gettype($searchFilter['operator']))
                    );
                }
            }
        }
    }
}
