<?php

declare(strict_types=1);

namespace Ameos\Scim\Domain\Repository;

use Ameos\Scim\Service\FilterService;
use Ameos\Scim\Service\MappingService;
use Symfony\Component\Uid\UuidV6;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;

abstract class AbstractResourceRepository
{
    /**
     * @param MappingService $mappingService
     * @param FilterService $filterService
     * @param ConnectionPool $connectionPool
     */
    public function __construct(
        private readonly MappingService $mappingService,
        private readonly FilterService $filterService,
        private readonly ConnectionPool $connectionPool
    ) {
    }

    /**
     * return table name
     *
     * @return string
     */
    abstract protected function getTable(): string;

    /**
     * list resources
     *
     * @param array $queryParams
     * @param array $mapping
     * @param int $pid
     * @return array
     */
    public function search(array $queryParams, array $mapping, int $pid): array
    {
        $startIndex = isset($queryParams['startIndex']) ? (int)$queryParams['startIndex'] : 1;
        $itemsPerPage = isset($queryParams['itemsPerPage']) ? (int)$queryParams['itemsPerPage'] : 10;
        $sortBy = null;
        $sortOrder = 'ASC';

        if (isset($queryParams['sortBy'])) {
            $sortBy = $this->mappingService->findField($queryParams['sortBy'], $mapping);
        }
        if (isset($queryParams['sortOrder'])) {
            $sortOrder = $queryParams['sortOrder'] === 'ascending' ? 'ASC' : 'DESC';
        }

        $qb = $this->connectionPool->getQueryBuilderForTable($this->getTable());

        $constraints = [];
        $constraints[] = $qb->expr()->eq('pid', $qb->createNamedParameter($pid, Connection::PARAM_INT));

        if (isset($queryParams['filter'])) {
            $filters = $this->filterService->convertFilter($queryParams['filter'], $qb, $mapping);
            if ($filters) {
                $constraints[] = $qb->expr()->and(...$filters);
            }
        }

        $totalResults = $qb
            ->count('uid')
            ->from($this->getTable())
            ->where(...$constraints)
            ->executeQuery()
            ->fetchOne();

        $qb
            ->select('*')
            ->setMaxResults($itemsPerPage)
            ->setFirstResult($startIndex - 1);

        if ($sortBy) {
            $qb->orderBy($sortBy, $sortOrder);
        } else {
            $qb->orderBy('uid', $sortOrder);
        }

        $result = $qb->executeQuery();

        return [$totalResults, $result];
    }

    /**
     * detail an user
     *
     * @param string $userId
     * @param array $queryParams
     * @param array $configuration
     * @return array|false
     */
    public function read(string $userId): array|false
    {
        $qb = $this->connectionPool->getQueryBuilderForTable($this->getTable());
        return $qb
            ->select('*')
            ->from($this->getTable())
            ->where($qb->expr()->eq('scim_id', $qb->createNamedParameter($userId)))
            ->executeQuery()
            ->fetchAssociative();
    }

    /**
     * create an user
     *
     * @param array $data
     * @param int $pid
     * @return string
     */
    public function create(array $data, int $pid): string
    {
        $data['scim_id'] = UuidV6::generate();
        $data['crdate'] = time();
        $data['tstamp'] = time();
        $data['pid'] = $pid;
        $connection = $this->connectionPool->getConnectionForTable($this->getTable());
        $connection->insert($this->getTable(), $data);
        return $data['scim_id'];
    }

    /**
     * update an user
     *
     * @param string $userId
     * @param array $data
     * @return string
     */
    public function update(string $userId, array $data): string
    {
        $data['tstamp'] = time();
        $connection = $this->connectionPool->getConnectionForTable($this->getTable());
        $connection->update($this->getTable(), $data, ['scim_id' => $userId]);
        return $userId;
    }

    /**
     * delete  an user
     *
     * @param string $userId
     * @return void
     */
    public function delete(string $userId): void
    {
        $qb = $this->connectionPool->getQueryBuilderForTable($this->getTable());
        $qb
            ->update($this->getTable())
            ->set('deleted', 1, true, Connection::PARAM_INT)
            ->where($qb->expr()->eq('scim_id', $qb->createNamedParameter($userId)))
            ->executeStatement();
    }
}
