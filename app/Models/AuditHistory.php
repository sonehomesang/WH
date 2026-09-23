<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A row in the generic {@see audit_history} table — one action on one master-data
 * record (uom / unit / department / location / building / room). Append-only:
 * created_at only, never updated. record_label is denormalized so the audit log
 * needs no join back to the parent.
 */
class AuditHistory extends Model
{
    public const UPDATED_AT = null; // append-only — created_at ເທົ່ານັ້ນ

    protected $table = 'audit_history';

    protected $guarded = ['id'];
}
