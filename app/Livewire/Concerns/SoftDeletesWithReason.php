<?php

namespace App\Livewire\Concerns;

use Illuminate\Database\Eloquent\Model;
use Livewire\Attributes\Computed;

/**
 * Reusable delete-with-reason + Deleted Log flow for Livewire index components.
 *
 * A component using this trait gets: a confirm modal that requires a reason
 * before soft-deleting (storing deleted_reason + deleted_by), a "Deleted Log"
 * toggle (onlyTrashed), and restore. Pair with:
 *   - a model that useS SoftDeletes and has deleted_reason + deleted_by columns
 *     + a deletedBy() belongsTo(User) relation;
 *   - the shared partial resources/views/partials/_delete-modal.blade.php;
 *   - a list query that adds ->onlyTrashed()->with('deletedBy') when
 *     ($this->showDeleted && $this->canManageDeleted()).
 *
 * The component MUST implement deleteModelClass(). Permission-based modules also
 * implement deletePermission(); role-based modules instead override
 * canManageDeleted(). It MAY override deleteGuard() (extra scope),
 * deleteLabel() (modal/flash text) and deleteNoun() (flash noun).
 */
trait SoftDeletesWithReason
{
    public bool $showDeleted = false;

    public ?int $deletingId = null;

    public string $deleteReason = '';

    /** FQCN of the soft-deletable model. */
    abstract protected function deleteModelClass(): string;

    /** Permission required to delete / restore / view the log. Return '' for role-based modules that override canManageDeleted(). */
    protected function deletePermission(): string
    {
        return '';
    }

    /** Hook for extra authorization (e.g. department scope). Override as needed. */
    protected function deleteGuard(Model $record): void {}

    /** Hook after a successful soft-delete (e.g. write an audit-history row). Override as needed. */
    protected function afterDeleted(Model $record): void {}

    /** Hook after a successful restore. Override as needed. */
    protected function afterRestored(Model $record): void {}

    /** Human label of the record shown in the modal + flash. */
    protected function deleteLabel(Model $record): string
    {
        return (string) ($record->name ?? $record->title ?? $record->asset_code ?? ('#'.$record->getKey()));
    }

    /** Flash-message noun, e.g. "ສິນຄ້າ". */
    protected function deleteNoun(): string
    {
        return 'ລາຍການ';
    }

    protected function canManageDeleted(): bool
    {
        $u = auth()->user();
        $perm = $this->deletePermission();

        return $u->is_super_admin || ($perm !== '' && $u->can($perm));
    }

    /** ສະຫຼັບ ລາຍການ ປົກກະຕິ ↔ Deleted Log. */
    public function toggleDeleted(): void
    {
        abort_unless($this->canManageDeleted(), 403);
        $this->showDeleted = ! $this->showDeleted;
        if (method_exists($this, 'resetPage')) {
            $this->resetPage();
        }
    }

    /** ເປີດ modal ຖາມ ເຫດຜົນ ກ່ອນ ລຶບ. */
    public function openDelete(int $id): void
    {
        abort_unless($this->canManageDeleted(), 403);
        $model = $this->deleteModelClass();
        $this->deleteGuard($model::findOrFail($id));
        $this->deletingId = $id;
        $this->deleteReason = '';
        $this->resetValidation();
    }

    /** ຢືນຢັນ ລຶບ — ບັນທຶກ ເຫດຜົນ + ຜູ້ລຶບ ແລ້ວ soft-delete. */
    public function deleteRecord(): void
    {
        abort_unless($this->canManageDeleted(), 403);
        $this->validate(
            ['deleteReason' => ['required', 'string', 'max:500']],
            ['deleteReason.required' => 'ກະລຸນາ ໃສ່ ເຫດຜົນ ການ ລຶບ.']
        );
        $model = $this->deleteModelClass();
        $record = $model::findOrFail($this->deletingId);
        $this->deleteGuard($record);
        $record->forceFill(['deleted_reason' => $this->deleteReason, 'deleted_by' => auth()->id()])->save();
        $record->delete();
        $this->afterDeleted($record);
        $this->deletingId = null;
        $this->deleteReason = '';
        session()->flash('ok', '✓ ລຶບ '.$this->deleteNoun().' '.$this->deleteLabel($record).' (ຍ້າຍ ໄປ Deleted Log)');
    }

    /** ກູ້ຄືນ ຈາກ Deleted Log. */
    public function restore(int $id): void
    {
        abort_unless($this->canManageDeleted(), 403);
        $model = $this->deleteModelClass();
        $record = $model::onlyTrashed()->find($id);
        if ($record) {
            $this->deleteGuard($record);
            $record->restore();
            $record->forceFill(['deleted_reason' => null, 'deleted_by' => null])->save();
            $this->afterRestored($record);
            session()->flash('ok', '✓ ກູ້ຄືນ '.$this->deleteNoun().' '.$this->deleteLabel($record));
        }
    }

    /** ບັນທຶກ ທີ່ ກຳລັງ ຈະ ລຶບ — ໃຊ້ ໃນ modal partial. */
    #[Computed]
    public function deletingRecord(): ?Model
    {
        if (! $this->deletingId) {
            return null;
        }
        $model = $this->deleteModelClass();

        return $model::find($this->deletingId);
    }
}
