<?php

namespace AhgRepositoryManage;

/**
 * Lightweight pager compatible with the theme's default/pager partial.
 */
class SimplePager
{
    protected int $page;
    protected int $maxPerPage;
    protected int $nbResults;
    protected int $lastPage;
    protected array $results;
    protected int $currentMaxLink = 1;

    public function __construct(array $results, int $total, int $page, int $maxPerPage)
    {
        $this->results = $results;
        $this->nbResults = $total;
        $this->maxPerPage = max(1, $maxPerPage);
        $this->lastPage = max(1, (int) ceil($total / $this->maxPerPage));
        $this->page = max(1, min($page, $this->lastPage));
    }

    public function haveToPaginate(): bool
    {
        return $this->maxPerPage > 0 && $this->nbResults > $this->maxPerPage;
    }

    public function getNbResults(): int
    {
        return $this->nbResults;
    }

    public function getFirstIndice(): int
    {
        if (0 === $this->page) {
            return 1;
        }

        return ($this->page - 1) * $this->maxPerPage + 1;
    }

    public function getLastIndice(): int
    {
        if ($this->page * $this->maxPerPage >= $this->nbResults) {
            return $this->nbResults;
        }

        return $this->page * $this->maxPerPage;
    }

    public function getPage(): int
    {
        return $this->page;
    }

    public function getLastPage(): int
    {
        return $this->lastPage;
    }

    public function getFirstPage(): int
    {
        return 1;
    }

    public function getLinks(int $nbLinks = 5): array
    {
        $links = [];
        $tmp = $this->page - (int) floor($nbLinks / 2);
        $check = $this->lastPage - $nbLinks + 1;
        $limit = $check > 0 ? $check : 1;
        $begin = $tmp > 0 ? ($tmp > $limit ? $limit : $tmp) : 1;

        $i = (int) $begin;
        while ($i < $begin + $nbLinks && $i <= $this->lastPage) {
            $links[] = $i++;
        }

        $this->currentMaxLink = count($links) ? $links[count($links) - 1] : 1;

        return $links;
    }

    public function getResults(): array
    {
        return $this->results;
    }

    public function getMaxPerPage(): int
    {
        return $this->maxPerPage;
    }

    public function getNextPage(): int
    {
        return min($this->page + 1, $this->lastPage);
    }

    public function getPreviousPage(): int
    {
        return max($this->page - 1, 1);
    }

    public function isFirstPage(): bool
    {
        return 1 === $this->page;
    }

    public function isLastPage(): bool
    {
        return $this->page === $this->lastPage;
    }
}
