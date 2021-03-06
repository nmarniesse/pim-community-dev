<?php

namespace spec\Oro\Bundle\PimDataGridBundle\Manager;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\DataGridBundle\Datagrid\Manager as DatagridManager;
use PhpSpec\ObjectBehavior;

class DatagridViewManagerSpec extends ObjectBehavior
{
    function let(
        EntityRepository $repository,
        DatagridManager $manager
    ) {
        $this->beConstructedWith($repository, $manager);
    }
}
