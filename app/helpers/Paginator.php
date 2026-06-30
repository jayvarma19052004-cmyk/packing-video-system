<?php
/**
 * Pagination Helper
 */

class Paginator {
    private int $total;
    private int $perPage;
    private int $currentPage;
    private array $items;
    private string $path;

    public function __construct(array $items, int $total, int $perPage, int $currentPage, string $path = '') {
        $this->items = $items;
        $this->total = $total;
        $this->perPage = $perPage;
        $this->currentPage = max(1, $currentPage);
        $this->path = $path;
    }

    /**
     * Get paginated items
     */
    public function items(): array {
        return $this->items;
    }

    /**
     * Get current page
     */
    public function currentPage(): int {
        return $this->currentPage;
    }

    /**
     * Get last page
     */
    public function lastPage(): int {
        return (int) ceil($this->total / $this->perPage);
    }

    /**
     * Check if has previous page
     */
    public function hasPreviousPage(): bool {
        return $this->currentPage > 1;
    }

    /**
     * Check if has next page
     */
    public function hasNextPage(): bool {
        return $this->currentPage < $this->lastPage();
    }

    /**
     * Get previous page URL
     */
    public function previousPageUrl(): ?string {
        if (!$this->hasPreviousPage()) {
            return null;
        }
        return $this->path . '?page=' . ($this->currentPage - 1);
    }

    /**
     * Get next page URL
     */
    public function nextPageUrl(): ?string {
        if (!$this->hasNextPage()) {
            return null;
        }
        return $this->path . '?page=' . ($this->currentPage + 1);
    }

    /**
     * Get total count
     */
    public function total(): int {
        return $this->total;
    }

    /**
     * Get per page
     */
    public function perPage(): int {
        return $this->perPage;
    }
}
