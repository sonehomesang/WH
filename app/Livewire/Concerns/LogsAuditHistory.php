<?php

namespace App\Livewire\Concerns;

use App\Models\AuditHistory;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Database\Eloquent\Model;

/**
 * Reusable audit-trail + admin-notification flow for master-data modules that
 * share the generic {@see audit_history} table (uom, org units/departments,
 * facility locations/buildings/rooms). Extracted from the per-module logic first
 * built into Inventory + Suppliers so several small entities can log to one table
 * without a dedicated *_history table each.
 *
 * A component using this trait typically:
 *   - calls logAudit($module, $record, 'create'|'update'|…, $reason) from
 *     save()/toggle() and the delete/restore hooks;
 *   - calls notifyAdmins(...) on the significant actions (update / deactivate /
 *     delete / restore) — NOT create/activate, to avoid noise.
 *
 * $module is the value stored in audit_history.module and used as the Settings ›
 * Audit filter key (e.g. 'uom', 'unit', 'department', 'location', …).
 */
trait LogsAuditHistory
{
    /**
     * Append one audit row for $record under $module. status is derived from the
     * record's is_active flag when present; $label defaults to name/slug/#id.
     */
    protected function logAudit(string $module, Model $record, string $action, ?string $comment = null, ?string $label = null): void
    {
        $actor = auth()->user();
        AuditHistory::create([
            'module' => $module,
            'record_id' => $record->getKey(),
            'record_label' => $label ?? $this->auditLabel($record),
            'action' => $action,
            'status' => $this->auditStatus($record),
            'user_id' => $actor?->id,
            'user_name' => $actor?->display_name ?: $actor?->email,
            'role' => $actor?->roles->first()?->name,
            'comment' => $comment,
            'created_at' => now(),
        ]);
    }

    /**
     * Alert other admins (never the actor) so multi-level management can see who
     * changed what. Recipients = active users with the admin/super_admin role or
     * the super-admin flag. Call for significant actions only.
     */
    protected function notifyAdmins(string $moduleTitle, string $label, string $action, ?string $reason = null, ?string $link = null): void
    {
        $actor = auth()->user();
        $adminIds = User::query()->where('status', 'active')->where('id', '!=', $actor?->id)
            ->where(fn ($q) => $q
                ->whereHas('roles', fn ($r) => $r->whereIn('name', ['admin', 'super_admin']))
                ->orWhere('is_super_admin', true))
            ->pluck('id')->all();
        if (empty($adminIds)) {
            return;
        }
        $verb = ['update' => 'ແກ້ໄຂ', 'deactivate' => 'ປິດໃຊ້', 'delete' => 'ລຶບ', 'restore' => 'ກູ້ຄືນ'][$action] ?? $action;
        $who = $actor?->display_name ?: $actor?->email;
        $msg = $who.' · '.$label.($reason ? ' · ເຫດຜົນ: '.$reason : '');
        app(NotificationService::class)->notifyMany($adminIds, 'info', "{$moduleTitle}: {$verb} {$label}", $msg, $link);
    }

    /** Human label of the record for the audit row + notification. */
    protected function auditLabel(Model $record): string
    {
        return (string) ($record->name ?? $record->slug ?? ('#'.$record->getKey()));
    }

    /** Resulting status string — active/inactive when the model has is_active, else null. */
    protected function auditStatus(Model $record): ?string
    {
        if (! array_key_exists('is_active', $record->getAttributes())) {
            return null;
        }

        return $record->is_active ? 'active' : 'inactive';
    }
}
