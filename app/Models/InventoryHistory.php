<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryHistory extends Model
{
    public const UPDATED_AT = null; // append-only — created_at ເທົ່ານັ້ນ

    protected $table = 'inventory_history';

    protected $guarded = ['id'];

    /** The inventory item this history row is about. */
    public function record(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'record_id');
    }
}
