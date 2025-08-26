<?php

namespace App\Traits;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\Paginator;
use Illuminate\Contracts\Pagination\CursorPaginator;

/**
 * @template TModel of Model
 */
trait FormatsMeta
{
    /**
     * @param CursorPaginator<Model>|LengthAwarePaginator<Model>|Paginator<Model> $models
     * @return array<string, mixed>
     * @phpstan-ignore-next-line
     */
    protected function formatMeta(Paginator|LengthAwarePaginator|CursorPaginator $models): array
    {
        if ($models instanceof LengthAwarePaginator) {
            return [
                'current_page' => $models->currentPage(),
                'per_page' => $models->perPage(),
                'total' => $models->total(),
                'last_page' => $models->lastPage(),
                'from' => $models->firstItem(),
                'to' => $models->lastItem(),
            ];
        }

        if ($models instanceof CursorPaginator) {
            return [
                'next_cursor' => $models->nextCursor() ? $models->nextCursor()->encode() : null,
                'prev_cursor' => $models->previousCursor() ? $models->previousCursor()->encode() : null,
            ];
        }

        return [];
    }
}
