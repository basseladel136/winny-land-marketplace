<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'coupon_id',
        'order_number',
        'status',
        'subtotal',
        'discount_amount',
        'total',
        'coupon_code',
        'payment_method',
        'customer_name',
        'customer_email',
        'customer_phone',
        'shipping_address',
        'notes',
        'locale',
    ];

    // payment_status and payment_reference are intentionally excluded from
    // $fillable — they must only be set by the payment gateway / OrderService
    // via direct assignment, never through user-controlled mass-assignment paths.
    protected $guarded = ['payment_status', 'payment_reference'];

    protected $casts = [
        'subtotal'        => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total'           => 'decimal:2',
    ];

    // Status constants
    const STATUS_PENDING    = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_SHIPPED    = 'shipped';
    const STATUS_DELIVERED  = 'delivered';
    const STATUS_CANCELLED  = 'cancelled';

    const PAYMENT_UNPAID  = 'unpaid';
    const PAYMENT_PENDING = 'pending';
    const PAYMENT_PAID    = 'paid';
    const PAYMENT_FAILED  = 'failed';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public static function generateOrderNumber(): string
    {
        $year = date('Y');
        $last = static::whereYear('created_at', $year)->latest()->first();
        $seq  = $last ? (intval(substr($last->order_number, -5)) + 1) : 1;
        return "WL-{$year}-" . str_pad($seq, 5, '0', STR_PAD_LEFT);
    }
}
