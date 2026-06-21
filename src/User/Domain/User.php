<?php

declare(strict_types=1);

namespace App\User\Domain;

use App\User\Infrastructure\UserRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'users')]
#[ORM\UniqueConstraint(name: 'uniq_user_email', columns: ['email'])]
#[ORM\HasLifecycleCallbacks]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    /** @phpstan-ignore-next-line */
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 255)]
    /** @var non-empty-string */
    private string $email;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private string $password;

    #[ORM\Column(name: 'first_name', type: Types::STRING, length: 100)]
    private string $firstName;

    #[ORM\Column(name: 'last_name', type: Types::STRING, length: 100)]
    private string $lastName;

    #[ORM\Column(type: Types::STRING, length: 20)]
    private string $role;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $active = true;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'updated_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $updatedAt;

    #[ORM\Column(name: 'deleted_at', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $deletedAt = null;

    public function __construct(
        string $email,
        string $firstName,
        string $lastName,
        string $role,
    ) {
        $now = new \DateTimeImmutable();
        $this->email = $email;
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->role = $role;
        $this->password = '';
        $this->createdAt = $now;
        $this->updatedAt = $now;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function getRole(): string
    {
        return $this->role;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function getDeletedAt(): ?\DateTimeImmutable
    {
        return $this->deletedAt;
    }

    /** @return list<string> */
    public function getRoles(): array
    {
        return [$this->role];
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     * @return non-empty-string
     */
    public function getUserIdentifier(): string
    {
        /** @var non-empty-string $email */
        $email = $this->email;
        return $email;
    }

    public function eraseCredentials(): void
    {
    }

    public function updatePassword(string $hashedPassword): void
    {
        $this->password = $hashedPassword;
    }

    public function update(string $firstName, string $lastName, string $role, bool $active): void
    {
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->role = $role;
        $this->active = $active;
    }

    public function softDelete(): void
    {
        $this->deletedAt = new \DateTimeImmutable();
        $this->active = false;
    }

    #[ORM\PreUpdate]
    public function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    /**
     * @return array<string, bool|int|string|null>
     */
    public function toArray(): array
    {
        return [
            'id'        => $this->id,
            'email'     => $this->email,
            'firstName' => $this->firstName,
            'lastName'  => $this->lastName,
            'role'      => $this->role,
            'active'    => $this->active,
            'createdAt' => $this->createdAt->format(\DATE_ATOM),
            'updatedAt' => $this->updatedAt->format(\DATE_ATOM),
            'deletedAt' => $this->deletedAt?->format(\DATE_ATOM),
        ];
    }
}
