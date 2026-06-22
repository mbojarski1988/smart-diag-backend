<?php

declare(strict_types=1);

namespace App\Shared\Ai\Domain;

use App\Shared\Ai\Infrastructure\AiPromptRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AiPromptRepository::class)]
#[ORM\Table(name: 'ai_prompts')]
class AiPrompt
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    /** @phpstan-ignore-next-line Doctrine assigns generated identifiers. */
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private string $name;

    #[ORM\Column(type: Types::TEXT)]
    private string $prompt;

    public function __construct(string $name, string $prompt)
    {
        $this->name = $name;
        $this->prompt = $prompt;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getPrompt(): string
    {
        return $this->prompt;
    }

    public function update(string $name, string $prompt): void
    {
        $this->name = $name;
        $this->prompt = $prompt;
    }

    /**
     * @return array{id: int|null, name: string, prompt: string}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'prompt' => $this->prompt,
        ];
    }
}
