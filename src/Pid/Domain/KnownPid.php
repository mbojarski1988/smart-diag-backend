<?php

declare(strict_types=1);

namespace App\Pid\Domain;

use App\Pid\Infrastructure\KnownPidRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: KnownPidRepository::class)]
#[ORM\Table(name: 'known_pids')]
#[ORM\UniqueConstraint(name: 'uniq_known_pid_model_pid', columns: ['model', 'pid'])]
class KnownPid
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    /** @phpstan-ignore-next-line Doctrine assigns generated identifiers. */
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 120)]
    private string $model;

    #[ORM\Column(type: Types::STRING, length: 40)]
    private string $pid;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private string $name;

    #[ORM\Column(type: Types::STRING, length: 40, nullable: true)]
    private ?string $unit;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $active;

    public function __construct(
        string $model,
        string $pid,
        string $name,
        ?string $unit,
        ?string $description,
        bool $active = true,
    ) {
        $this->model = self::normalizeModel($model);
        $this->pid = self::normalizePid($pid);
        $this->name = trim($name);
        $this->unit = self::nullableTrim($unit);
        $this->description = self::nullableTrim($description);
        $this->active = $active;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getModel(): string
    {
        return $this->model;
    }

    public function getPid(): string
    {
        return $this->pid;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getUnit(): ?string
    {
        return $this->unit;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function update(
        string $model,
        string $pid,
        string $name,
        ?string $unit,
        ?string $description,
        bool $active,
    ): void {
        $this->model = self::normalizeModel($model);
        $this->pid = self::normalizePid($pid);
        $this->name = trim($name);
        $this->unit = self::nullableTrim($unit);
        $this->description = self::nullableTrim($description);
        $this->active = $active;
    }

    /**
     * @return array{
     *     id: int|null,
     *     model: string,
     *     pid: string,
     *     name: string,
     *     unit: string|null,
     *     description: string|null,
     *     active: bool,
     * }
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'model' => $this->model,
            'pid' => $this->pid,
            'name' => $this->name,
            'unit' => $this->unit,
            'description' => $this->description,
            'active' => $this->active,
        ];
    }

    public static function normalizeModel(string $model): string
    {
        return trim(preg_replace('/\s+/', ' ', $model) ?? $model);
    }

    public static function normalizePid(string $pid): string
    {
        return strtoupper(trim($pid));
    }

    private static function nullableTrim(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }
}
