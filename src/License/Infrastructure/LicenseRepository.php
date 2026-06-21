<?php

declare(strict_types=1);

namespace App\License\Infrastructure;

use App\License\Application\LicenseLookup;
use App\License\Domain\License;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<License>
 */
final class LicenseRepository extends ServiceEntityRepository implements LicenseLookup
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, License::class);
    }

    public function findByLicenseKey(string $licenseKey): ?License
    {
        return $this->findOneBy(['licenseKey' => $licenseKey]);
    }

    /**
     * @return list<License>
     */
    public function findVisible(): array
    {
        /** @var list<License> $result */
        $result = $this->createQueryBuilder('license')
            ->andWhere('license.deletedAt IS NULL')
            ->orderBy('license.createdAt', 'DESC')
            ->getQuery()
            ->getResult();

        return $result;
    }
}
