<?php

namespace WS\Core\Library\CRUD;

use Doctrine\ORM\NoResultException;
use WS\Core\Entity\Domain;
use WS\Core\Library\Domain\DomainRepositoryTrait;
use WS\Core\Service\ContextService;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

abstract class AbstractRepository extends ServiceEntityRepository
{
    use DomainRepositoryTrait;

    protected ContextService $contextService;

    public function __construct(ContextService $contextService, ManagerRegistry $registry)
    {
        $this->contextService = $contextService;

        parent::__construct($registry, $this->getEntityClass());
    }

    abstract public function getEntityClass(): string;

    abstract public function getFilterFields(): array;

    public function processFilterExtended(QueryBuilder $qb, ?array $filter): void
    {
    }

    public function getAll(
        Domain $domain,
        ?string $search,
        ?array $filter,
        array $orderBy = null,
        int $limit = null,
        int $offset = null
    ): array {
        $qb = $this->getAllQueryBuilder();
        $alias = $qb->getRootAliases()[0];

        $this->setFilter($alias, $qb, $search);

        if (isset($orderBy) && count($orderBy)) {
            foreach ($orderBy as $field => $dir) {
                $qb->addOrderBy(sprintf('%s.%s', $alias, $field), $dir);
            }
        }

        if (isset($limit) && isset($offset)) {
            $qb->setFirstResult($offset);
            $qb->setMaxResults($limit);
        }

        $this->processFilterExtended($qb, $filter);

        $this->setDomainRestriction($alias, $qb, $domain);

        return $qb->getQuery()->execute();
    }

    public function getAvailableByIds(?Domain $domain = null, array $ids): array
    {
        $alias = 't';
        $qb = $this->createQueryBuilder($alias)
            ->where(sprintf('%s.id IN (:ids)', $alias))
            ->setParameter('ids', $ids);

        if (null !== $domain) {
            $this->setDomainRestriction($alias, $qb, $domain);
        }

        return $qb->getQuery()->execute();
    }

    public function getAllCount(Domain $domain, ?string $search, ?array $filter): int
    {
        $qb = $this->getAllCountQueryBuilder();
        $alias = $qb->getRootAliases()[0];

        $qb = $qb->select(sprintf(sprintf('count(%s.id)', $alias)));

        $this->setFilter($alias, $qb, $search);

        $this->processFilterExtended($qb, $filter);

        $this->setDomainRestriction($alias, $qb, $domain);

        try {
            return $qb->getQuery()->getSingleScalarResult();
        } catch (NonUniqueResultException $e) {
            return 0;
        } catch (NoResultException $e) {
            return 0;
        }
    }

    protected function setFilter(string $alias, QueryBuilder $qb, ?string $search): void
    {
        if (!$search) {
            return;
        }

        $filterConditions = [];
        $filterParameters = [];
        foreach ($this->getFilterFields() as $field) {
            $filterConditions[] = sprintf('%s LIKE :%s_filter', sprintf('%s.%s', $alias, $field), $field);
            $filterParameters[sprintf('%s_filter', $field)] = sprintf('%%%s%%', $search);
        }

        if (count($filterConditions) > 0) {
            $qb->andWhere(implode(' OR ', $filterConditions));
            foreach ($filterParameters as $key => $val) {
                $qb->setParameter($key, $val);
            }
        }
    }

    protected function setFilters(string $alias, QueryBuilder $qb, array $filters): void
    {
        foreach ($filters as $field => $value) {
            $qb->andWhere(sprintf('%s LIKE :%s_filter', sprintf('%s.%s', $alias, $field), $field));
            $qb->setParameter(sprintf('%s_filter', $field), sprintf('%%%s%%', $value));
        }
    }

    public function batchDelete(array $ids): void
    {
        $alias = 't';

        $qb = $this->createQueryBuilder($alias)
            ->delete($this->getEntityClass(), $alias)
            ->where(sprintf('%s.id IN (:ids)', $alias))
            ->setParameter('ids', $ids);

        $qb->getQuery()->execute();
    }

    protected function getAllQueryBuilder(): QueryBuilder
    {
        $alias = 't';
        return $this->createQueryBuilder($alias);
    }

    protected function getAllCountQueryBuilder(): QueryBuilder
    {
        $alias = 't';
        return $this->createQueryBuilder($alias);
    }
}
