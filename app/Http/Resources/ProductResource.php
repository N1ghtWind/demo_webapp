<?php

namespace App\Http\Resources;

use App\Models\Image;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\Product;

/**
 * @property Product $resource
 */
class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {


        return [
            'id' => $this->resource->id,
            'category' => $this->resource->category ? $this->resource->category->name : null,
            'category_id' => $this->resource->category_id,
            'price' => $this->resource->price,
            'name' => $this->resource->name,
            'description' => $this->resource->description,
            'created_at' => $this->resource->created_at,
            'updated_at' => $this->resource->updated_at,
            /** @phpstan-ignore-next-line */
            'images' => $this->resource->images->map(function ($image): array {
                /** @var Image $image */
                return [
                    'id' => $image->id,
                    'presets' => $image->presets,
                    'first' => $image->pivot?->first ?? false,
                ];
            }),
            'firstImage' => $this->resource->images->firstWhere('pivot.first', true) ? [
                'presets' => $this->resource->images->firstWhere('pivot.first', true)->presets,
                'first' => 1,
            ] : null,
        ];
    }

}
