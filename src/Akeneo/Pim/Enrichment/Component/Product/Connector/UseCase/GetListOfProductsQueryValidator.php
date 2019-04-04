<?php

declare(strict_types=1);

namespace Akeneo\Pim\Enrichment\Component\Product\Connector\UseCase;

use Akeneo\Tool\Component\StorageUtils\Repository\IdentifiableObjectRepositoryInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/**
 * TODO Move ?
 *
 * @author Pierre Allard <pierre.allard@akeneo.com>
 */
class GetListOfProductsQueryValidator
{
    /** @var IdentifiableObjectRepositoryInterface */
    private $categoryRepository;

    /** @var IdentifiableObjectRepositoryInterface */
    private $localeRepository;

    /**
     * @param IdentifiableObjectRepositoryInterface $categoryRepository
     * @param IdentifiableObjectRepositoryInterface $localeRepository
     */
    public function __construct(
        IdentifiableObjectRepositoryInterface $categoryRepository,
        IdentifiableObjectRepositoryInterface $localeRepository
    ) {
        $this->categoryRepository = $categoryRepository;
        $this->localeRepository = $localeRepository;
    }

    /**
     * @param GetListOfProductsQuery $query
     *
     * @throws BadRequestHttpException
     * @throws UnprocessableEntityHttpException
     */
    public function validate(GetListOfProductsQuery $query)
    {
        $this->validateCriterionParameters($query);
        $this->validateCategoriesParameters($query);
        $this->validateLocalesParameters($query);
    }



    /**
     * @param GetListOfProductsQuery $query
     *
     * @throws BadRequestHttpException
     * @throws UnprocessableEntityHttpException
     */
    private function validateCriterionParameters(GetListOfProductsQuery $query): void
    {
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

    /**
     * @param GetListOfProductsQuery $query
     *
     * @throws UnprocessableEntityHttpException
     */
    private function validateCategoriesParameters(GetListOfProductsQuery $query): void
    {
        if (!isset($query->search['categories'])) {
            return;
        }

        $categories = $query->search['categories'];
        if (!is_array($categories)) {
            throw new UnprocessableEntityHttpException(
                sprintf('Search query parameter "categories" has to be an array, "%s" given.', gettype($categories))
            );
        }

        $errors = [];
        foreach ($categories as $category) {
            foreach ($category['value'] as $categoryCode) {
                $categoryCode = trim($categoryCode);
                if (null === $this->categoryRepository->findOneByIdentifier($categoryCode)) {
                    $errors[] = $categoryCode;
                }
            }
        }

        if (!empty($errors)) {
            $plural = count($errors) > 1 ? 'Categories "%s" do not exist.' : 'Category "%s" does not exist.';
            throw new UnprocessableEntityHttpException(sprintf($plural, implode(', ', $errors)));
        }
    }

    /**
     * @param GetListOfProductsQuery $query
     *
     * @throws UnprocessableEntityHttpException
     */
    private function validateLocalesParameters(GetListOfProductsQuery $query): void
    {
        foreach ($query->search as $propertyCode => $filters) {
            foreach ($filters as $filter) {
                $localesFilter = isset($filter['locale']) ? $filter['locale'] : $query->searchLocale;

                if (null !== $localesFilter && is_string($localesFilter)) {
                    $localeCodes = array_map('trim', explode(',', $localesFilter));

                    $errors = [];
                    foreach ($localeCodes as $localeCode) {
                        $locale = $this->localeRepository->findOneByIdentifier($localeCode);
                        if (null === $locale || !$locale->isActivated()) {
                            $errors[] = $localeCode;
                        }
                    }

                    if (!empty($errors)) {
                        $plural = count($errors) > 1 ?
                            'Locales "%s" do not exist or are not activated.' : 'Locale "%s" does not exist or is not activated.';
                        throw new UnprocessableEntityHttpException(sprintf($plural, implode(', ', $errors)));
                    }
                }
            }
        }
    }
}
