<?php

declare(strict_types=1);

namespace Akeneo\Pim\Enrichment\Component\Product\Connector\UseCase;

/**
 * @copyright 2019 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
final class ListProductsQuery
{
    /** @var array */
    public $search = [];

    /** @var string */
    public $channel;

    /** @var string[] */
    public $locales;

    /** @var string */
    public $searchLocale;

    /** @var string */
    public $searchScope;

    /** @var string[] */
    public $attributes;

    /** @var string */
    public $paginationType;

    /** @var int */
    public $page = 1;

    /** @var string */
    public $searchAfter;

    /** @var int */
    public $limit;

    /** @var bool */
    public $withCount = false;

    /** @var int */
    public $userId;
}