<?php

namespace App\Services;

use App\Models\AnsiApplication;
use App\Models\AnsiItem;
use App\Models\Department;
use App\Models\InventoryItem;
use App\Models\User;
use App\Notifications\AnsiStageNotification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * AnsiService — state machine for the "Application for New Stock Item" (ANSI).
 *
 * draft -> pending_hos -> pending_manager -> pending_warehouse -> completed
 *   submit         (originator)  draft -> pending_hos
 *   endorse        (named HoS)   pending_hos -> pending_manager
 *   approve        (named Mgr)   pending_manager -> pending_warehouse
 *   warehouseDone  (warehouse)   pending_warehouse -> completed  (item numbers + PR)
 *   reject         (stage owner) pending_* -> rejected
 *   cancel         (originator)  draft|pending_* -> cancelled
 */
class AnsiService
{
    /** Counter ANSI-{YYYY}-NNNN (transaction-safe — call inside DB::transaction). */
    public function nextNumber(int $year): string
    {
        $prefix = "ANSI-{$year}-";
        $max = DB::table('ansi_applications')->where('request_number', 'like', $prefix.'%')
            ->lockForUpdate()->max('request_number');
        $seq = $max ? ((int) substr($max, strlen($prefix))) + 1 : 1;

        return $prefix.str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }

    public function createDraft(array $data, User $actor): AnsiApplication
    {
        return DB::transaction(function () use ($data, $actor) {
            $date = Carbon::parse($data['app_date'] ?? Carbon::today());
            $hos = ! empty($data['hos_user_id']) ? User::find($data['hos_user_id']) : null;
            $mgr = ! empty($data['manager_user_id']) ? User::find($data['manager_user_id']) : null;

            $app = AnsiApplication::create([
                'request_number' => $this->nextNumber((int) $date->year),
                'originator_user_id' => $actor->id,
                'originator_name' => $actor->display_name ?? $actor->email,
                'originator_email' => mb_strtolower($actor->email),
                'owner_unit_id' => $actor->unit_id,
                'owner_dept_id' => $actor->department_id,
                'section_team' => $data['section_team'] ?? null,
                'phone' => $data['phone'] ?? ($actor->phone ?? null),
                'hos_user_id' => $hos?->id,
                'hos_name' => $hos ? ($hos->display_name ?? $hos->email) : null,
                'manager_user_id' => $mgr?->id,
                'manager_name' => $mgr ? ($mgr->display_name ?? $mgr->email) : null,
                'app_date' => $date->toDateString(),
                'sub_assembly' => $data['sub_assembly'] ?? null,
                'functional_system' => $data['functional_system'] ?? null,
                'purpose' => $data['purpose'] ?? null,
                'status' => 'draft',
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ]);

            $this->syncItems($app, $data['items'] ?? []);
            $this->recordHistory($app, 'create', $actor);

            return $app->refresh();
        });
    }

    /** Replace the application's item rows + recompute summary. */
    public function syncItems(AnsiApplication $app, array $items): void
    {
        $app->items()->delete();
        $summary = [];
        foreach (array_values($items) as $i => $it) {
            $desc = trim((string) ($it['description'] ?? ''));
            if ($desc === '') {
                continue;
            }
            $price = $it['price_usd'] ?? null;
            $min = $it['min_qty'] ?? null;
            $max = $it['max_qty'] ?? null;
            $storage = $it['special_storage'] ?? 'Normal';
            $app->items()->create([
                'stock' => (bool) ($it['stock'] ?? false),
                'description' => $desc,
                'price_usd' => ($price !== '' && $price !== null) ? $price : null,
                'qty_order' => max(1, (int) ($it['qty_order'] ?? 1)),
                'unit' => ($it['unit'] ?? null) ?: null,
                'min_qty' => ($min !== '' && $min !== null) ? (int) $min : null,
                'max_qty' => ($max !== '' && $max !== null) ? (int) $max : null,
                'suggested_supplier' => ($it['suggested_supplier'] ?? null) ?: null,
                'hazardous' => (bool) ($it['hazardous'] ?? false),
                'criticality' => (bool) ($it['criticality'] ?? false),
                'special_storage' => in_array($storage, ['Normal', 'Air Cond room'], true) ? $storage : 'Normal',
                'sort_order' => $i,
            ]);
            $summary[] = $desc;
        }
        $app->update(['summary_items' => $summary ? implode('; ', $summary).';' : null]);
    }

    public function submit(AnsiApplication $app, User $actor): void
    {
        $this->assert($app->status === 'draft', 'Only a draft can be submitted.');
        $this->assert($app->items()->exists(), 'At least one stock item is required.');
        $this->assert($app->hos_user_id && $app->manager_user_id, 'HoS/TL and Manager must be selected.');

        $app->update(['status' => 'pending_hos', 'submitted_at' => now(), 'updated_by' => $actor->id]);
        $this->recordHistory($app, 'submit', $actor);
        $this->notify($app->hos_user_id, 'info', $app, 'needs your endorsement');
        $this->notify($app->originator_user_id, 'info', $app, 'submitted for endorsement');
        $this->email($app, $app->hos_user_id, 'ລໍ ການ ຮັບຮອງ ຂອງ HoS/TL', actionNeeded: true);
        $this->email($app, $app->originator_user_id, 'ສົ່ງ ຂໍ ຮັບຮອງ ແລ້ວ');
    }

    public function endorse(AnsiApplication $app, User $actor): void
    {
        $this->assert($app->status === 'pending_hos', 'Not awaiting HoS/TL endorsement.');
        $this->assert($app->hos_user_id === $actor->id, 'Only the assigned HoS/TL may endorse.');

        $app->update(['status' => 'pending_manager', 'endorsed_by' => $actor->id, 'endorsed_at' => now(), 'updated_by' => $actor->id]);
        $this->recordHistory($app, 'endorse', $actor);
        $this->notify($app->manager_user_id, 'info', $app, 'needs your approval');
        $this->notify($app->originator_user_id, 'success', $app, 'endorsed by HoS/TL');
        $this->email($app, $app->manager_user_id, 'ລໍ ການ ອະນຸມັດ ຂອງ Manager', actionNeeded: true);
        $this->email($app, $app->originator_user_id, 'HoS/TL ຮັບຮອງ ແລ້ວ');
    }

    public function approve(AnsiApplication $app, User $actor): void
    {
        $this->assert($app->status === 'pending_manager', 'Not awaiting Manager approval.');
        $this->assert($app->manager_user_id === $actor->id, 'Only the assigned Manager may approve.');

        $app->update(['status' => 'pending_warehouse', 'approved_by' => $actor->id, 'approved_at' => now(), 'updated_by' => $actor->id]);
        $this->recordHistory($app, 'approve', $actor);
        $svc = app(NotificationService::class);
        $svc->notifyRole('warehouse_staff', 'info', 'ANSI '.$app->request_number.' approved - warehouse to process', 'Check duplicate, create item number, create PR.', route('ansi.show', $app));
        $this->notify($app->originator_user_id, 'success', $app, 'approved by Manager');
        $this->emailRole($app, 'warehouse_staff', 'ອະນຸມັດ ແລ້ວ — ໃຫ້ ສາງ ດຳເນີນ ການ (ໃສ່ ເລກ ລາຍການ + PR)', actionNeeded: true);
        $this->email($app, $app->originator_user_id, 'Manager ອະນຸມັດ ແລ້ວ');
    }

    /** Warehouse: record item numbers + PR, complete + closeout. */
    public function warehouseDone(AnsiApplication $app, User $actor, array $opts): void
    {
        $this->assert($app->status === 'pending_warehouse', 'Not awaiting warehouse processing.');

        $created = 0;
        DB::transaction(function () use ($app, $actor, $opts, &$created) {
            foreach ($app->items as $it) {
                $num = trim((string) ($opts['item_numbers'][$it->id] ?? ''));
                if ($num !== '') {
                    $it->update(['item_number' => $num]);
                }
                // v2 hook: the warehouse-assigned item_number IS the new material
                // number — mirror it into the WH Inventory master (SAP is the ERP
                // of record; this keeps WH in sync). Idempotent + duplicate-safe.
                if ($this->mirrorItemToInventory($it->fresh(), $app, $actor)) {
                    $created++;
                }
            }
            $app->update([
                'status' => 'completed',
                'pr_number' => ($opts['pr_number'] ?? null) ?: null,
                'warehouse_note' => ($opts['warehouse_note'] ?? null) ?: null,
                'warehoused_by' => $actor->id,
                'warehoused_at' => now(),
                'updated_by' => $actor->id,
            ]);
        });
        $this->recordHistory($app, 'warehouse_done', $actor, $opts['warehouse_note'] ?? null);
        $this->notify($app->originator_user_id, 'success', $app,
            "completed - item number & PR done ({$created} new inventory item".($created === 1 ? '' : 's').')');
        $this->email($app, $app->originator_user_id, 'ສຳເລັດ — ສາງ ໃສ່ ເລກ ລາຍການ + ສ້າງ ເຂົ້າ Inventory ແລ້ວ');
    }

    /**
     * Mirror one approved ANSI item into the WH Inventory master. Returns true
     * when a NEW InventoryItem was created (false when skipped or linked to an
     * existing one). Safe to call repeatedly: `created_inventory_id` guards
     * against re-creation, and a slug (= Material No.) collision links to the
     * existing record instead of throwing a unique-constraint error.
     */
    private function mirrorItemToInventory(AnsiItem $it, AnsiApplication $app, User $actor): bool
    {
        $slug = trim((string) $it->item_number);
        if ($slug === '' || $it->created_inventory_id) {
            return false;
        }

        // A record may already exist under this Material No. (incl. soft-deleted,
        // which still holds the unique slug) — link to it rather than duplicate.
        $existing = InventoryItem::withTrashed()->where('slug', $slug)->first();
        if ($existing) {
            if ($existing->trashed()) {
                $existing->restore();
            }
            $it->update(['created_inventory_id' => $existing->id]);

            return false;
        }

        $name = Str::limit(trim((string) $it->description), 250, '');
        $inv = InventoryItem::create([
            'slug' => $slug,
            'name' => $name !== '' ? $name : $slug,
            'description' => $it->description,
            'quantity' => 0,                       // master created empty; stock arrives on receiving
            'min_quantity' => (int) ($it->min_qty ?? 0),
            'unit' => $it->unit,
            'department_id' => $app->owner_dept_id,
            'status' => 'available',
            'is_active' => true,
            'created_by' => $actor->id,
        ]);
        $it->update(['created_inventory_id' => $inv->id]);

        return true;
    }

    public function reject(AnsiApplication $app, User $actor, string $stage, string $reason): void
    {
        $this->assert(in_array($app->status, ['pending_hos', 'pending_manager', 'pending_warehouse'], true), 'Cannot reject in this state.');
        $this->assert(trim($reason) !== '', 'A reject comment is required.');

        $app->update([
            'status' => 'rejected', 'reject_stage' => $stage, 'reject_reason' => $reason,
            'rejected_by' => $actor->id, 'rejected_at' => now(), 'updated_by' => $actor->id,
        ]);
        $this->recordHistory($app, 'reject', $actor, $reason);
        $this->notify($app->originator_user_id, 'error', $app, 'rejected at '.$stage);
        $this->email($app, $app->originator_user_id, 'ຖືກ ຕີ ກັບ ທີ່ ຂັ້ນ '.$stage.' — ເຫດຜົນ: '.Str::limit($reason, 80));
    }

    public function cancel(AnsiApplication $app, User $actor, ?string $reason = null): void
    {
        $this->assert(in_array($app->status, ['draft', 'pending_hos', 'pending_manager', 'pending_warehouse'], true), 'Cannot cancel in this state.');
        $app->update(['status' => 'cancelled', 'cancel_reason' => $reason, 'cancelled_at' => now(), 'updated_by' => $actor->id]);
        $this->recordHistory($app, 'cancel', $actor, $reason);
    }

    private function notify(?int $userId, string $type, AnsiApplication $app, string $what): void
    {
        if (! $userId) {
            return;
        }
        app(NotificationService::class)->notify($userId, $type, 'ANSI '.$app->request_number.' '.$what, Str::limit($app->summary_items ?? '', 80), route('ansi.show', $app));
    }

    /**
     * Best-effort stage email to one user (optional — silently no-ops when SMTP
     * is not configured or the user has no address, so it never blocks the flow).
     * Mirrors the DisposalEndorsementRequest pattern; the in-app bell always fires.
     */
    private function email(AnsiApplication $app, ?int $userId, string $headline, bool $actionNeeded = false): void
    {
        if (! $userId) {
            return;
        }
        $user = User::find($userId);
        if (! $user || ! $user->email) {
            return;
        }
        try {
            $user->notify(new AnsiStageNotification($app, $headline, $actionNeeded));
        } catch (\Throwable $e) {
            // email down (SMTP not set) — do not break the workflow.
        }
    }

    /** Best-effort stage email to every user holding a role (e.g. warehouse_staff). */
    private function emailRole(AnsiApplication $app, string $role, string $headline, bool $actionNeeded = false): void
    {
        foreach (User::role($role)->whereNotNull('email')->get() as $user) {
            try {
                $user->notify(new AnsiStageNotification($app, $headline, $actionNeeded));
            } catch (\Throwable $e) {
                // email down — skip, keep the workflow moving.
            }
        }
    }

    private function recordHistory(AnsiApplication $app, string $action, User $actor, ?string $comment = null): void
    {
        $app->history()->create([
            'action' => $action, 'status' => $app->status,
            'user_id' => $actor->id, 'user_name' => $actor->display_name ?? $actor->email,
            'comment' => $comment, 'created_at' => now(),
        ]);
    }

    private function assert(bool $ok, string $message): void
    {
        if (! $ok) {
            throw ValidationException::withMessages(['status' => $message]);
        }
    }
}
