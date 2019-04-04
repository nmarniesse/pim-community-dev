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
    private static $productFields = [
        'family',
        'categories',
        'completeness',
        'identifier',
        'created',
        'updated',
        'enabled',
        'groups',
    ];

    /** @var IdentifiableObjectRepositoryInterface */
    private $categoryRepository;

    /** @var IdentifiableObjectRepositoryInterface */
    private $localeRepository;

    /** @var IdentifiableObjectRepositoryInterface */
    private $attributeRepository;

    /**
     * @param IdentifiableObjectRepositoryInterface $categoryRepository
     * @param IdentifiableObjectRepositoryInterface $localeRepository
     * @param IdentifiableObjectRepositoryInterface $attributeRepository
     */
    public function __construct(
        IdentifiableObjectRepositoryInterface $categoryRepository,
        IdentifiableObjectRepositoryInterface $localeRepository,
        IdentifiableObjectRepositoryInterface $attributeRepository
    ) {
        $this->categoryRepository = $categoryRepository;
        $this->localeRepository = $localeRepository;
        $this->attributeRepository = $attributeRepository;
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
        $this->validatePropertyParameters($query);
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
        $localeCodes = [];
        foreach ($query->search as $propertyCode => $filters) {
            foreach ($filters as $filter) {
                $localesFilter = isset($filter['locale']) ? $filter['locale'] : $query->searchLocale;

                if (null !== $localesFilter && is_string($localesFilter)) {
                    $localeCodes = array_merge($localeCodes, array_map('trim', explode(',', $localesFilter)));
                }

                // For completeness filter only
                if (isset($filter['locales'])) {
                    if (!is_array($filter['locales'])) {
                        throw new UnprocessableEntityHttpException(
                            sprintf('Property "%s" expects an array with the key "locales".', $propertyCode)
                        );
                    }

                    $localeCodes = array_merge($localeCodes, $filter['locales']);
                }
            }
        }

        $localeCodes = array_unique($localeCodes);
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

    /**
     * TODO: the support of the operator is not validated in this method (we only check the property)
     *       We should change the exception message or update this method's behaviour
     *
     * @param GetListOfProductsQuery $query
     *
     * @throws UnprocessableEntityHttpException
     */
    public function validatePropertyParameters(GetListOfProductsQuery $query): void
    {
        foreach ($query->search as $propertyCode => $filters) {
            foreach ($filters as $filter) {
                if (
                    !in_array($propertyCode, self::$productFields) &&
                    null === $this->attributeRepository->findOneByIdentifier($propertyCode)
                ) {
                    throw new UnprocessableEntityHttpException(
                        sprintf(
                            'Filter on property "%s" is not supported or does not support operator "%s"',
                            $propertyCode,
                            $filter['operator']
                        )
                    );
                }
            }
        }
    }
}
