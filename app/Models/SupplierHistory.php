<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierHistory extends Model
{
    public const UPDATED_AT = null; // append-only — created_at ເທົ່ານັ້ນ

    protected $table = 'supplier_history';

    protected $guarded = ['id'];

    /** The supplier this history row is about. */
    public function record(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'record_id');
    }
}
