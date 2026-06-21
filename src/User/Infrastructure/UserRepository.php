<?php

declare(strict_types=1);

namespace App\User\Infrastructure;

use App\User\Application\UserLookup;
use App\User\Domain\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<User>
 */
final class UserRepository extends ServiceEntityRepository implements UserLookup
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function findByEmail(string $email): ?User
    {
        return $this->findOneBy(['email' => $email]);
    }

    public function findById(int $id): ?User
    {
        return $this->find($id);
    }

    /**
     * @return list<User>
     */
    public function findVisible(): array
    {
        /** @var list<User> $result */
        $result = $this->createQueryBuilder('u')
            ->andWhere('u.deletedAt IS NULL')
            ->orderBy('u.createdAt', 'DESC')
            ->getQuery()
            ->getResult();

        return $result;
    }
}
