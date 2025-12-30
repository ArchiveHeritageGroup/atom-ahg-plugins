<?php

declare(strict_types=1);

namespace arMuseumMetadataPlugin\Models;

/**
 * Getty Vocabulary Link Model.
 *
 * Represents a link between a local term (in AtoM taxonomy) and a
 * Getty Vocabulary URI. Supports linking to AAT, TGN, and ULAN.
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */
class GettyVocabularyLink
{
    public const VOCABULARY_AAT = 'aat';
    public const VOCABULARY_TGN = 'tgn';
    public const VOCABULARY_ULAN = 'ulan';

    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_SUGGESTED = 'suggested';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_PENDING = 'pending';

    private ?int $id = null;
    private int $termId;
    private string $vocabulary;
    private string $gettyUri;
    private string $gettyId;
    private ?string $gettyPrefLabel = null;
    private ?string $gettyScopeNote = null;
    private string $status = self::STATUS_PENDING;
    private float $confidence = 0.0;
    private ?int $confirmedByUserId = null;
    private ?\DateTimeInterface $confirmedAt = null;
    private \DateTimeInterface $createdAt;
    private ?\DateTimeInterface $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    // Getters

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTermId(): int
    {
        return $this->termId;
    }

    public function getVocabulary(): string
    {
        return $this->vocabulary;
    }

    public function getGettyUri(): string
    {
        return $this->gettyUri;
    }

    public function getGettyId(): string
    {
        return $this->gettyId;
    }

    public function getGettyPrefLabel(): ?string
    {
        return $this->gettyPrefLabel;
    }

    public function getGettyScopeNote(): ?string
    {
        return $this->gettyScopeNote;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getConfidence(): float
    {
        return $this->confidence;
    }

    public function getConfirmedByUserId(): ?int
    {
        return $this->confirmedByUserId;
    }

    public function getConfirmedAt(): ?\DateTimeInterface
    {
        return $this->confirmedAt;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    // Setters

    public function setId(int $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function setTermId(int $termId): self
    {
        $this->termId = $termId;

        return $this;
    }

    public function setVocabulary(string $vocabulary): self
    {
        if (!in_array($vocabulary, [self::VOCABULARY_AAT, self::VOCABULARY_TGN, self::VOCABULARY_ULAN])) {
            throw new \InvalidArgumentException("Invalid vocabulary: {$vocabulary}");
        }
        $this->vocabulary = $vocabulary;

        return $this;
    }

    public function setGettyUri(string $uri): self
    {
        $this->gettyUri = $uri;
        // Extract ID from URI
        if (preg_match('/\/(\d+)$/', $uri, $matches)) {
            $this->gettyId = $matches[1];
        }

        return $this;
    }

    public function setGettyId(string $id): self
    {
        $this->gettyId = $id;
        $this->gettyUri = "http://vocab.getty.edu/{$this->vocabulary}/{$id}";

        return $this;
    }

    public function setGettyPrefLabel(?string $label): self
    {
        $this->gettyPrefLabel = $label;

        return $this;
    }

    public function setGettyScopeNote(?string $note): self
    {
        $this->gettyScopeNote = $note;

        return $this;
    }

    public function setStatus(string $status): self
    {
        $validStatuses = [
            self::STATUS_CONFIRMED,
            self::STATUS_SUGGESTED,
            self::STATUS_REJECTED,
            self::STATUS_PENDING,
        ];

        if (!in_array($status, $validStatuses)) {
            throw new \InvalidArgumentException("Invalid status: {$status}");
        }
        $this->status = $status;
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    public function setConfidence(float $confidence): self
    {
        $this->confidence = max(0.0, min(1.0, $confidence));

        return $this;
    }

    public function confirm(int $userId): self
    {
        $this->status = self::STATUS_CONFIRMED;
        $this->confirmedByUserId = $userId;
        $this->confirmedAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    public function reject(): self
    {
        $this->status = self::STATUS_REJECTED;
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    // Utility methods

    public function isConfirmed(): bool
    {
        return self::STATUS_CONFIRMED === $this->status;
    }

    public function isSuggested(): bool
    {
        return self::STATUS_SUGGESTED === $this->status;
    }

    public function isRejected(): bool
    {
        return self::STATUS_REJECTED === $this->status;
    }

    public function getHumanUrl(): string
    {
        return "http://vocab.getty.edu/page/{$this->vocabulary}/{$this->gettyId}";
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'term_id' => $this->termId,
            'vocabulary' => $this->vocabulary,
            'getty_uri' => $this->gettyUri,
            'getty_id' => $this->gettyId,
            'getty_pref_label' => $this->gettyPrefLabel,
            'getty_scope_note' => $this->gettyScopeNote,
            'status' => $this->status,
            'confidence' => $this->confidence,
            'confirmed_by_user_id' => $this->confirmedByUserId,
            'confirmed_at' => $this->confirmedAt?->format('Y-m-d H:i:s'),
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt?->format('Y-m-d H:i:s'),
            'human_url' => $this->getHumanUrl(),
        ];
    }

    public static function fromArray(array $data): self
    {
        $link = new self();

        if (isset($data['id'])) {
            $link->setId((int) $data['id']);
        }
        if (isset($data['term_id'])) {
            $link->setTermId((int) $data['term_id']);
        }
        if (isset($data['vocabulary'])) {
            $link->setVocabulary($data['vocabulary']);
        }
        if (isset($data['getty_uri'])) {
            $link->setGettyUri($data['getty_uri']);
        } elseif (isset($data['getty_id'], $data['vocabulary'])) {
            $link->setGettyId($data['getty_id']);
        }
        if (isset($data['getty_pref_label'])) {
            $link->setGettyPrefLabel($data['getty_pref_label']);
        }
        if (isset($data['getty_scope_note'])) {
            $link->setGettyScopeNote($data['getty_scope_note']);
        }
        if (isset($data['status'])) {
            $link->setStatus($data['status']);
        }
        if (isset($data['confidence'])) {
            $link->setConfidence((float) $data['confidence']);
        }

        return $link;
    }
}
