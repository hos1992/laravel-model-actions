<?php

namespace HosnyAdeeb\ModelActions\Traits;

use Illuminate\Database\Eloquent\Builder;

trait Filterable
{
    /**
     * Filter parameters from request or custom input.
     */
    protected array $filters = [];

    /**
     * Columns that can be searched.
     */
    protected array $searchable = [];

    /**
     * Default sort column.
     */
    protected string $defaultSort = 'created_at';

    /**
     * Default sort direction.
     */
    protected string $defaultSortDirection = 'desc';

    /**
     * Column to use for date range filtering.
     */
    protected string $dateColumn = 'created_at';

    /**
     * Set the filters.
     *
     * @param array $filters
     * @return static
     */
    public function setFilters(array $filters): static
    {
        $this->filters = $filters;
        return $this;
    }

    /**
     * Apply all filters to the query builder.
     *
     * @param Builder $query
     * @return Builder
     */
    protected function applyFilters(Builder $query): Builder
    {
        return $query
            ->when($this->getSearch(), fn($q, $search) => $this->applySearch($q, $search))
            ->when($this->getSort(), fn($q, $sort) => $q->orderBy($sort, $this->getSortDirection()))
            ->when($this->getDateFrom(), fn($q, $date) => $q->where($this->dateColumn, '>=', $date))
            ->when($this->getDateTo(), fn($q, $date) => $q->where($this->dateColumn, '<=', $date))
            ->when($this->getWhereFilters(), fn($q, $filters) => $this->applyWhereFilters($q, $filters));
    }

    /**
     * Apply search to the query.
     *
     * @param Builder $query
     * @param string $search
     * @return Builder
     */
    protected function applySearch(Builder $query, string $search): Builder
    {
        if (empty($this->searchable)) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($search) {
            foreach ($this->searchable as $index => $column) {
                $method = $index === 0 ? 'where' : 'orWhere';

                if (str_contains($column, '.')) {
                    // Relationship column: relation.column
                    [$relation, $relationColumn] = explode('.', $column, 2);
                    $q->{$method . 'Has'}($relation, function (Builder $subQuery) use ($relationColumn, $search) {
                        $subQuery->where($relationColumn, 'LIKE', "%{$search}%");
                    });
                } else {
                    $q->{$method}($column, 'LIKE', "%{$search}%");
                }
            }
        });
    }

    /**
     * Apply where filters to the query.
     *
     * @param Builder $query
     * @param array $filters
     * @return Builder
     */
    protected function applyWhereFilters(Builder $query, array $filters): Builder
    {
        foreach ($filters as $column => $value) {
            if ($value === null || $value === '') {
                continue;
            }

            if (is_array($value)) {
                $query->whereIn($column, $value);
            } else {
                $query->where($column, $value);
            }
        }

        return $query;
    }

    /**
     * Get the search term.
     */
    protected function getSearch(): ?string
    {
        return $this->filters['search'] ?? $this->filters['q'] ?? null;
    }

    /**
     * Get the sort column.
     */
    protected function getSort(): ?string
    {
        return $this->filters['sort'] ?? $this->filters['order_by'] ?? $this->defaultSort;
    }

    /**
     * Get the sort direction.
     */
    protected function getSortDirection(): string
    {
        $direction = $this->filters['direction'] ?? $this->filters['order_dir'] ?? $this->defaultSortDirection;
        return in_array(strtolower($direction), ['asc', 'desc']) ? strtolower($direction) : 'desc';
    }

    /**
     * Get the date from filter.
     */
    protected function getDateFrom(): ?string
    {
        return $this->filters['date_from'] ?? $this->filters['from'] ?? null;
    }

    /**
     * Get the date to filter.
     */
    protected function getDateTo(): ?string
    {
        return $this->filters['date_to'] ?? $this->filters['to'] ?? null;
    }

    /**
     * Get additional where filters.
     */
    protected function getWhereFilters(): array
    {
        $reserved = ['search', 'q', 'sort', 'order_by', 'direction', 'order_dir', 'date_from', 'from', 'date_to', 'to', 'page', 'per_page'];

        return array_filter(
            $this->filters,
            fn($key) => !in_array($key, $reserved),
            ARRAY_FILTER_USE_KEY
        );
    }

    /**
     * Set the searchable columns.
     *
     * @param array $columns
     * @return static
     */
    public function setSearchable(array $columns): static
    {
        $this->searchable = $columns;
        return $this;
    }

    /**
     * Set the default sort.
     *
     * @param string $column
     * @param string $direction
     * @return static
     */
    public function setDefaultSort(string $column, string $direction = 'desc'): static
    {
        $this->defaultSort = $column;
        $this->defaultSortDirection = $direction;
        return $this;
    }
}
