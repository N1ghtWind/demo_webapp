<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property int $order_id
 * @property int $product_id
 * @property int $price
 * @property int $quantity
 * @property string|null $created_at
 * @property string|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read \App\Models\Order $order
 * @property-read \App\Models\Product $product
 * @method static \Database\Factories\OrderItemFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|OrderItem newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|OrderItem newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|OrderItem onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|OrderItem query()
 * @method static \Illuminate\Database\Eloquent\Builder|OrderItem whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderItem whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderItem whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderItem whereOrderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderItem wherePrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderItem whereProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderItem whereQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderItem whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderItem withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|OrderItem withoutTrashed()
 * @mixin \Eloquent
 */
class OrderItem extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'order_items';

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];
    protected $fillable = ['order_id', 'product_id', 'price', 'quantity'];

    public function getCreatedAtAttribute(?string $value): ?string
    {
        return $value
            ? Carbon::parse($value)->format('Y-m-d H:i:s')
            : null;
    }

    public function getUpdatedAtAttribute(?string $value): ?string
    {
        return $value
            ? Carbon::parse($value)->format('Y-m-d H:i:s')
            : null;
    }

    /** @return BelongsTo<Order, OrderItem> */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /** @return BelongsTo<Product, OrderItem> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
