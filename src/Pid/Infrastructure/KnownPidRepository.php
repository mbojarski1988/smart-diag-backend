<?php

declare(strict_types=1);

namespace App\Pid\Infrastructure;

use App\Pid\Domain\KnownPid;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<KnownPid>
 */
final class KnownPidRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, KnownPid::class);
    }

    /**
     * @return list<KnownPid>
     */
    public function findForAdmin(?string $model = null): array
    {
        $qb = $this->createQueryBuilder('pid')
            ->orderBy('pid.model', 'ASC')
            ->addOrderBy('pid.pid', 'ASC');

        if ($model !== null && trim($model) !== '') {
            $qb
                ->andWhere('LOWER(pid.model) = LOWER(:model)')
                ->setParameter('model', KnownPid::normalizeModel($model));
        }

        /** @var list<KnownPid> $result */
        $result = $qb->getQuery()->getResult();

        return $result;
    }

    /**
     * @return list<KnownPid>
     */
    public function findActiveByModel(string $model): array
    {
        /** @var list<KnownPid> $result */
        $result = $this->createQueryBuilder('pid')
            ->andWhere('LOWER(pid.model) = LOWER(:model)')
            ->andWhere('pid.active = true')
            ->setParameter('model', KnownPid::normalizeModel($model))
            ->orderBy('pid.pid', 'ASC')
            ->getQuery()
            ->getResult();

        return $result;
    }

    public function findOneByModelAndPid(string $model, string $pid): ?KnownPid
    {
        $knownPid = $this->findOneBy([
            'model' => KnownPid::normalizeModel($model),
            'pid' => KnownPid::normalizePid($pid),
        ]);

        return $knownPid instanceof KnownPid ? $knownPid : null;
    }
}
