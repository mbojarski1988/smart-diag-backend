<?php

declare(strict_types=1);

namespace App\License\Domain;

use App\License\Infrastructure\LicenseRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LicenseRepository::class)]
#[ORM\Table(name: 'licenses')]
#[ORM\UniqueConstraint(name: 'uniq_license_key', columns: ['license_key'])]
#[ORM\HasLifecycleCallbacks]
class License
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    /** @phpstan-ignore-next-line Doctrine assigns generated identifiers. */
    private ?int $id = null;

    #[ORM\Column(name: 'license_key', type: Types::STRING, length: 80)]
    private string $licenseKey;

    #[ORM\Column(name: 'client_name', type: Types::STRING, length: 255)]
    private string $clientName;

    #[ORM\Column(name: 'client_email', type: Types::STRING, length: 255)]
    private string $clientEmail;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $note;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $active = true;

    #[ORM\Column(name: 'valid_until', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $validUntil;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'updated_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $updatedAt;

    #[ORM\Column(name: 'deactivated_at', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $deactivatedAt = null;

    #[ORM\Column(name: 'deleted_at', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $deletedAt = null;

    public function __construct(
        string $licenseKey,
        string $clientName,
        string $clientEmail,
        ?string $note,
        \DateTimeImmutable $validUntil,
    ) {
        $now = new \DateTimeImmutable();

        $this->licenseKey = $licenseKey;
        $this->clientName = $clientName;
        $this->clientEmail = $clientEmail;
        $this->note = $note;
        $this->validUntil = $validUntil;
        $this->createdAt = $now;
        $this->updatedAt = $now;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLicenseKey(): string
    {
        return $this->licenseKey;
    }

    public function getClientName(): string
    {
        return $this->clientName;
    }

    public function getClientEmail(): string
    {
        return $this->clientEmail;
    }

    public function getNote(): ?string
    {
        return $this->note;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function getValidUntil(): \DateTimeImmutable
    {
        return $this->validUntil;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function getDeactivatedAt(): ?\DateTimeImmutable
    {
        return $this->deactivatedAt;
    }

    public function getDeletedAt(): ?\DateTimeImmutable
    {
        return $this->deletedAt;
    }

    public function update(
        string $clientName,
        string $clientEmail,
        ?string $note,
        \DateTimeImmutable $validUntil,
        bool $active,
    ): void {
        $wasActive = $this->active;

        $this->clientName = $clientName;
        $this->clientEmail = $clientEmail;
        $this->note = $note;
        $this->validUntil = $validUntil;
        $this->active = $active;

        if ($wasActive && !$active) {
            $this->deactivatedAt = new \DateTimeImmutable();
        }

        if (!$wasActive && $active) {
            $this->deactivatedAt = null;
        }
    }

    public function deactivate(): void
    {
        $this->active = false;
        $this->deactivatedAt = new \DateTimeImmutable();
    }

    public function softDelete(): void
    {
        $this->deletedAt = new \DateTimeImmutable();
        $this->active = false;
    }

    public function isUsableAt(\DateTimeImmutable $now): bool
    {
        return $this->active
            && $this->deletedAt === null
            && $this->validUntil >= $now;
    }

    #[ORM\PreUpdate]
    public function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    /**
     * @return array<string, bool|int|string|null>
     */
    public function toArray(bool $includeKey = false): array
    {
        $data = [
            'id' => $this->id,
            'clientName' => $this->clientName,
            'clientEmail' => $this->clientEmail,
            'note' => $this->note,
            'active' => $this->active,
            'validUntil' => $this->validUntil->format(DATE_ATOM),
            'createdAt' => $this->createdAt->format(DATE_ATOM),
            'updatedAt' => $this->updatedAt->format(DATE_ATOM),
            'deactivatedAt' => $this->deactivatedAt?->format(DATE_ATOM),
            'deletedAt' => $this->deletedAt?->format(DATE_ATOM),
        ];

        if ($includeKey) {
            $data['licenseKey'] = $this->licenseKey;
        }

        return $data;
    }
}
