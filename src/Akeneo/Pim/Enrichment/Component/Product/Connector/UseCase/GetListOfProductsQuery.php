<?php

declare(strict_types=1);

namespace Akeneo\Pim\Enrichment\Component\Product\Connector\UseCase;

/**
 * @copyright 2019 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
final class GetListOfProductsQuery
{
    /** @var array */
    public $search;

    /** @var string */
    public $channel;

    /** @var string[] */
    public $locales;

    /** @var string[] */
    public $attributes;

    /** @var string */
    public $paginationType;

    /** @var int */
    public $page;

    /** @var string */
    public $searchAfter;

    /** @var int */
    public $limit;

    /** @var bool */
    public $withCount;

    /** @var int */
    public $userId;
}
