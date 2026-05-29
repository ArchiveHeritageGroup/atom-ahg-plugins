<?php

declare(strict_types=1);

/**
 * AhgAiContextHints
 *
 * Value object carrying embedded-metadata context hints (capture date, place,
 * creator, subjects) surfaced from EXIF/IPTC so AI services (NER/HTR/LLM) can
 * be primed — e.g. "this photograph was taken on 1969-07-20 in Pretoria" — to
 * reduce hallucination. Heratio #750 parity.
 *
 * @package    ahgAIPlugin
 * @subpackage Services
 */
class AhgAiContextHints
{
    /** @var string[] */
    public array $subjectHints = [];

    public function __construct(
        public ?string $dateHint = null,
        public ?string $placeHint = null,
        public ?string $creatorHint = null,
        array $subjectHints = []
    ) {
        $this->subjectHints = array_values(array_filter(array_map('trim', $subjectHints), fn ($v) => $v !== ''));
    }

    public static function empty(): self
    {
        return new self();
    }

    public function isEmpty(): bool
    {
        return $this->dateHint === null
            && $this->placeHint === null
            && $this->creatorHint === null
            && $this->subjectHints === [];
    }

    /**
     * Render as a compact, model-friendly context prefix. Empty string when
     * there are no hints (so callers can safely prepend unconditionally).
     */
    public function toPromptPrefix(): string
    {
        if ($this->isEmpty()) {
            return '';
        }
        $parts = [];
        if ($this->dateHint) {
            $parts[] = 'capture date: ' . $this->dateHint;
        }
        if ($this->placeHint) {
            $parts[] = 'location: ' . $this->placeHint;
        }
        if ($this->creatorHint) {
            $parts[] = 'creator: ' . $this->creatorHint;
        }
        if ($this->subjectHints) {
            $parts[] = 'subjects: ' . implode(', ', $this->subjectHints);
        }

        return '[Embedded metadata context — ' . implode('; ', $parts) . ']';
    }

    public function toArray(): array
    {
        return [
            'date'     => $this->dateHint,
            'place'    => $this->placeHint,
            'creator'  => $this->creatorHint,
            'subjects' => $this->subjectHints,
        ];
    }
}
