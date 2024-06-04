<?php

declare(strict_types=1);

namespace Ameos\Scim\Domain\Repository;

class BackendUserRepository extends AbstractResourceRepository
{
    /**
     * return table name
     *
     * @return string
     */
    protected function getTable(): string
    {
        return 'be_users';
    }
}