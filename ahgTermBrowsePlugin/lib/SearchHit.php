<?php

namespace AhgTermBrowse;

/**
 * Lightweight wrapper around ES doc array.
 * Provides getData()/getId() methods compatible with Elastica\Result
 * so theme partials (search/searchResult) work without changes.
 */
class SearchHit
{
    protected array $data;
    protected string $id;

    public function __construct(array $data)
    {
        $this->id = (string) ($data['_id'] ?? '0');
        unset($data['_id']);
        $this->data = $data;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function getId(): string
    {
        return $this->id;
    }
}
