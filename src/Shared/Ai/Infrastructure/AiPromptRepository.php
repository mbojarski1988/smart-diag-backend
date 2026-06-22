<?php

declare(strict_types=1);

namespace App\Shared\Ai\Infrastructure;

use App\Shared\Ai\Domain\AiPrompt;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AiPrompt>
 */
final class AiPromptRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AiPrompt::class);
    }

    /**
     * @return list<AiPrompt>
     */
    public function findAllOrdered(): array
    {
        /** @var list<AiPrompt> $result */
        $result = $this->createQueryBuilder('prompt')
            ->orderBy('prompt.id', 'DESC')
            ->getQuery()
            ->getResult();

        return $result;
    }
}
