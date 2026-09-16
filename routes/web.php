<?php

use App\Livewire\Borrow\Create;
use App\Livewire\Borrow\Show;
use App\Livewire\Dashboard;
use App\Livewire\Disposal\Summary;
use App\Livewire\Equipment\Categories;
use App\Livewire\Equipment\InspectionTemplates;
use App\Livewire\Equipment\MaintenanceTemplates;
use App\Livewire\Inventory\Index;
use App\Livewire\Settings\Audit;
use App\Livewire\Settings\Backup;
use App\Livewire\Settings\ClearTestData;
use App\Livewire\Settings\ConditionStatuses;
use App\Livewire\Settings\Email;
use App\Livewire\Settings\Ldap;
use App\Livewire\Settings\Facilities;
use App\Livewire\Survey\Form as SurveyForm;
use App\Livewire\Survey\Results as SurveyResults;
use App\Livewire\Settings\NotificationLog;
use App\Livewire\Settings\Notifications;
use App\Livewire\Settings\Organization;
use App\Livewire\Settings\Reports;
use App\Livewire\Settings\RolesPermissions;
use App\Livewire\Settings\SupplierDetail;
use App\Livewire\Settings\Suppliers;
use App\Livewire\Settings\System;
use App\Livewire\Settings\Translations;
use App\Livewire\Settings\Uom;
use App\Livewire\Settings\Users;
use App\Models\AreaInspection;
use App\Models\BorrowRecord;
use App\Models\Department;
use App\Models\DepositItem;
use App\Models\DepositRecord;
use App\Models\DiscrepancyAdvice;
use App\Models\DisposalItem;
use App\Models\DisposalRecord;
use App\Models\Equipment;
use App\Models\EquipmentInspection;
use App\Models\EquipmentMaintenance;
use App\Models\ExpoContact;
use App\Models\ExpoEvent;
use App\Models\InventoryItem;
use App\Models\MaterialRequest;
use App\Models\OutwardsGoodsAdvice;
use App\Models\Setting;
use App\Support\PdfExport;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

// ໜ້າ ທຳອິດ: login ແລ້ວ → dashboard, ຍັງ → ໜ້າ login (ບໍ່ ມີ ໜ້າ welcome ເກົ່າ ແລ້ວ).
Route::get('/', fn () => redirect()->route(auth()->check() ? 'dashboard' : 'login'))->name('home');

// UI language toggle (Lao default / English). Stored in the session; the
// SetLocale middleware applies it. Available to guests too (login page).
Route::get('locale/{locale}', function (\Illuminate\Http\Request $request, string $locale) {
    if (in_array($locale, \App\Http\Middleware\SetLocale::SUPPORTED, true)) {
        session(['app_locale' => $locale]);
    }

    // Return the user to where they were — but only to a LOCAL path, never a
    // forged Referer host (no open redirect). We keep just the path+query of the
    // referer and redirect relative, so the browser resolves it same-origin.
    // Reject protocol-relative ("//evil.com") and anything not starting with "/".
    $referer = (string) $request->headers->get('referer', '');
    $path = $referer !== '' ? parse_url($referer, PHP_URL_PATH) : null;

    // Accept only a plain local path: one leading "/", not protocol-relative
    // ("//host"), and no backslash (some browsers fold "/\host" into "//host").
    if (is_string($path) && str_starts_with($path, '/')
        && ! str_starts_with($path, '//')
        && ! str_contains($path, '\\')) {
        $query = parse_url($referer, PHP_URL_QUERY);

        return redirect()->to($path.($query ? '?'.$query : ''));
    }

    return redirect()->route('home');
})->name('locale.switch');

// PWA manifest — dynamic so it reflects the General › app name setting.
Route::get('manifest.webmanifest', function () {
    $name = Setting::get('general', [])['app_name'] ?? 'WH — Warehouse';

    return response()->json([
        'name' => $name,
        'short_name' => 'WH',
        'description' => 'Warehouse Information System',
        'start_url' => '/dashboard',
        'scope' => '/',
        'display' => 'standalone',
        'orientation' => 'portrait-primary',
        'background_color' => '#f1f5f9',
        'theme_color' => '#0284c7',
        'icons' => [
            ['src' => '/icons/icon-192.png', 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any'],
            ['src' => '/icons/icon-512.png', 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any'],
            ['src' => '/icons/maskable-512.png', 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'maskable'],
        ],
    ], 200, ['Content-Type' => 'application/manifest+json']);
})->name('manifest');

Route::get('dashboard', Dashboard::class)
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::get('inventory', Index::class)
    ->middleware(['auth', 'verified'])
    ->name('inventory');

Route::get('borrow', App\Livewire\Borrow\Index::class)
    ->middleware(['auth', 'verified'])
    ->name('borrow');

Route::get('borrow/create', Create::class)
    ->middleware(['auth', 'verified'])
    ->name('borrow.create');

Route::get('borrow/{record}/pdf', function (BorrowRecord $record) {
    $u = auth()->user();
    abort_unless($u->can('borrow.view'), 403);
    // ownership scope — mirrors Borrow\Show::canSee() (ກັນ IDOR: download ຂອງ ຄົນ ອື່ນ)
    $email = mb_strtolower($u->email);
    abort_unless(
        $u->is_super_admin || $u->hasAnyRole(['admin', 'warehouse_staff'])
        || $record->borrower_user_id === $u->id || $record->approver_email === $email || $record->acknowledge_email === $email
        || ($u->transactionScope() === 'department' && $u->department_id && $record->borrower_dept_id === $u->department_id),
        403
    );
    $record->load(['items.inventoryItem.primaryPhoto', 'items.photos', 'unit', 'department']);

    return PdfExport::download('borrow.pdf', ['record' => $record], "borrow-{$record->request_number}.pdf");
})->middleware(['auth', 'verified'])->name('borrow.pdf');

Route::get('borrow/{record}', Show::class)
    ->middleware(['auth', 'verified'])
    ->name('borrow.show');

// ── Deposit (ການຝາກເຄື່ອງ) — Phase 6.8a ──
Route::get('deposit', App\Livewire\Deposit\Index::class)
    ->middleware(['auth', 'verified'])
    ->name('deposit');

Route::get('deposit/create', App\Livewire\Deposit\Create::class)
    ->middleware(['auth', 'verified'])
    ->name('deposit.create');

Route::get('deposit/{record}/pdf', function (DepositRecord $record) {
    $u = auth()->user();
    abort_unless($u->can('deposit.view'), 403);
    // ownership scope — mirrors Deposit\Show::mount()
    abort_unless(
        $u->is_super_admin || $u->hasAnyRole(['admin', 'warehouse_staff']) || $record->owner_user_id === $u->id
        || ($u->transactionScope() === 'department' && $u->department_id && $record->owner_dept_id === $u->department_id),
        403
    );
    $record->load(['items.photos', 'unit', 'department', 'history']);

    return PdfExport::download('deposit.pdf', ['record' => $record], "deposit-{$record->request_number}.pdf");
})->middleware(['auth', 'verified'])->name('deposit.pdf');

// Letterhead index/report of the (filtered) deposit list — printable document.
Route::get('deposit/report', function (\Illuminate\Http\Request $request) {
    $u = auth()->user();
    abort_unless($u->can('deposit.view'), 403);

    $q = DepositRecord::query()->with(['items.photos', 'unit']);
    // ownership scope — mirrors Deposit\Index::scopedQuery()
    if (! ($u->is_super_admin || $u->hasAnyRole(['admin', 'warehouse_staff']))) {
        if ($u->transactionScope() === 'department' && $u->department_id) {
            $q->where('owner_dept_id', $u->department_id);
        } else {
            $q->where('owner_user_id', $u->id);
        }
    }

    $search = trim((string) $request->query('search', ''));
    $status = (string) $request->query('status', '');
    $type = (string) $request->query('type', '');
    $unitId = (int) $request->query('unit', 0);
    $condition = (string) $request->query('condition', '');

    $q->when($search !== '', fn ($w) => $w->where(fn ($x) => $x->where('request_number', 'like', "%{$search}%")->orWhere('owner_name', 'like', "%{$search}%")))
        ->when($status === 'needs_info', fn ($w) => $w->needsOfficeInfo())
        ->when($status !== '' && $status !== 'needs_info', fn ($w) => $w->where('status', $status))
        ->when($type !== '', fn ($w) => $w->where('request_type', $type))
        ->when($unitId > 0, fn ($w) => $w->where('owner_unit_id', $unitId))
        ->when($condition !== '', fn ($w) => $w->whereHas('items', fn ($x) => $x->where('condition_status', $condition)))
        ->orderByDesc('id');

    return view('deposit.index-report', [
        'records' => $q->get(),
        'filterUnit' => $unitId > 0 ? \App\Models\Unit::find($unitId) : null,
        'filterStatus' => $status,
        'filterCondition' => $condition !== '' ? \App\Support\ConditionStatus::shortLabel($condition) : '',
        'generatedBy' => $u->display_name ?? $u->email,
    ]);
})->middleware(['auth', 'verified'])->name('deposit.report');

Route::get('deposit/{record}', App\Livewire\Deposit\Show::class)
    ->middleware(['auth', 'verified'])
    ->name('deposit.show');

// ── Disposal (ຈຳໜ່າຍ ເຄື່ອງ ຊຳລຸດ) ──
Route::get('disposal', App\Livewire\Disposal\Index::class)
    ->middleware(['auth', 'verified'])
    ->name('disposal');

Route::get('disposal/create', App\Livewire\Disposal\Create::class)
    ->middleware(['auth', 'verified'])
    ->name('disposal.create');

// ── ANSI · Application for New Stock Item ──
Route::get('ansi', App\Livewire\Ansi\Index::class)->middleware(['auth', 'verified'])->name('ansi');
Route::get('ansi/create', App\Livewire\Ansi\Create::class)->middleware(['auth', 'verified'])->name('ansi.create');
Route::get('ansi/{record}', App\Livewire\Ansi\Show::class)->middleware(['auth', 'verified'])->name('ansi.show');

Route::get('disposal/summary', Summary::class)
    ->middleware(['auth', 'verified'])
    ->name('disposal.summary');

Route::get('disposal/summary/pdf', function () {
    abort_unless(auth()->user()->can('disposal.view'), 403);
    $from = request('from') ?: null;
    $to = request('to') ?: null;
    $deptId = Summary::scopedDeptId(request('department_id')); // clamp dept-admin ໃສ່ ພະແນກ ຕົນ
    $status = request('status', 'disposed');
    $items = Summary::itemsQuery($from, $to, $deptId, $status)->get();

    return PdfExport::download('disposal.summary-pdf', [
        'items' => $items,
        'from' => $from, 'to' => $to, 'status' => $status,
        'deptName' => $deptId ? optional(Department::find($deptId))->name : null,
        'totalQty' => $items->sum('qty'),
        'totalValue' => $items->sum(fn ($it) => (float) $it->estimated_value),
    ], 'disposal-summary.pdf');
})->middleware(['auth', 'verified'])->name('disposal.summary.pdf');

Route::get('disposal/{record}/pdf', function (DisposalRecord $record) {
    $u = auth()->user();
    abort_unless(App\Livewire\Disposal\Show::canOpen($record), 403); // ownership scope + assigned endorser (IDOR guard)
    $record->load(['items', 'signoffs', 'department', 'preparedBy']);

    return PdfExport::download('disposal.pdf', ['record' => $record], "disposal-{$record->request_number}.pdf");
})->middleware(['auth', 'verified'])->name('disposal.pdf');

// Per-item disposal profile — shared view-data resolver (condition + photos + owner + endorsement)
$disposalProfileData = function (DisposalRecord $record, DisposalItem $item): array {
    abort_unless(App\Livewire\Disposal\Show::canOpen($record), 403);
    abort_unless($item->record_id === $record->id, 404);
    $record->load(['signoffs', 'department', 'preparedBy']);

    $source = match ($item->source_type) {
        'equipment' => Equipment::find($item->source_id),
        'inventory' => InventoryItem::find($item->source_id),
        'deposit' => DepositItem::find($item->source_id),
        default => null,
    };

    // owning Org Unit + Department (Unit derived from department)
    $ownerDept = $ownerUnit = null;
    if ($source) {
        if ($item->source_type === 'deposit') {
            $ownerDept = $source->record?->department?->name;
            $ownerUnit = $source->record?->unit?->name;
        } else {
            $ownerDept = $source->department?->name;
            $ownerUnit = $source->department?->unit?->name;
        }
    }

    return ['record' => $record, 'item' => $item, 'source' => $source, 'ownerDept' => $ownerDept, 'ownerUnit' => $ownerUnit];
};

// Preview the profile in-app (HTML) before exporting the file
Route::get('disposal/{record}/item/{item}/preview', function (DisposalRecord $record, DisposalItem $item) use ($disposalProfileData) {
    return view('disposal.item-profile-pdf', $disposalProfileData($record, $item) + ['preview' => true]);
})->middleware(['auth', 'verified'])->name('disposal.item.preview');

// Export the profile as a letterhead PDF
Route::get('disposal/{record}/item/{item}/pdf', function (DisposalRecord $record, DisposalItem $item) use ($disposalProfileData) {
    return PdfExport::download('disposal.item-profile-pdf', $disposalProfileData($record, $item),
        "disposal-item-{$record->request_number}-{$item->id}.pdf");
})->middleware(['auth', 'verified'])->name('disposal.item.pdf');

Route::get('disposal/{record}', App\Livewire\Disposal\Show::class)
    ->middleware(['auth', 'verified'])
    ->name('disposal.show');

// ── Area/Workplace Inspection (ກວດ ສະຖານທີ່) ──
Route::get('area-inspection', App\Livewire\AreaInspection\Index::class)
    ->middleware(['auth', 'verified'])
    ->name('area-inspection');

Route::get('area-inspection/{record}/pdf', function (AreaInspection $record) {
    abort_unless(auth()->user()->can('area_inspection.view'), 403);
    $record->load(['template', 'location', 'acknowledgedBy']);

    return PdfExport::download('area-inspection.pdf', ['record' => $record], "area-inspection-{$record->inspection_number}.pdf");
})->middleware(['auth', 'verified'])->name('area-inspection.pdf');

// ── Shops Material (catalog ສິນຄ້າ supplier) — Phase 6.7a ──
Route::get('catalog', App\Livewire\Catalog\Index::class)
    ->middleware(['auth', 'verified'])
    ->name('catalog');

// ── Request Material (ໃບເບີກວັດສະດຸ) — Phase 6.7b ──
Route::get('request', App\Livewire\Request\Index::class)
    ->middleware(['auth', 'verified'])
    ->name('request');

Route::get('request/create', App\Livewire\Request\Create::class)
    ->middleware(['auth', 'verified'])
    ->name('request.create');

Route::get('request/{record}/pdf', function (MaterialRequest $record) {
    $u = auth()->user();
    abort_unless($u->can('request.view'), 403);
    // ownership scope — mirrors Request\Show::canSee()
    abort_unless(
        $u->is_super_admin || $u->hasAnyRole(['admin', 'warehouse_staff'])
        || $record->requester_user_id === $u->id || $record->approver_user_id === $u->id
        || ($u->hasRole('supplier') && $u->supplier_id && $record->assigned_supplier_id === $u->supplier_id)
        || ($u->transactionScope() === 'department' && $u->department_id && $record->requester_dept_id === $u->department_id),
        403
    );
    $record->load(['items', 'supplier', 'unit', 'department', 'history']);

    return PdfExport::download('request.pdf', ['record' => $record], "request-{$record->request_number}.pdf");
})->middleware(['auth', 'verified'])->name('request.pdf');

Route::get('request/{record}', App\Livewire\Request\Show::class)
    ->middleware(['auth', 'verified'])
    ->name('request.show');

// ── DA Claims (Discrepancy Advice) — Phase 6.8b ──
Route::get('da', App\Livewire\Da\Index::class)
    ->middleware(['auth', 'verified'])
    ->name('da');

Route::get('da/create', App\Livewire\Da\Create::class)
    ->middleware(['auth', 'verified'])
    ->name('da.create');

Route::get('da/{record}/pdf', function (DiscrepancyAdvice $record) {
    $u = auth()->user();
    abort_unless($u->can('da.view'), 403);
    // ownership scope — privileged (warehouse/purchasing/leader) or the raiser only
    abort_unless(
        $u->is_super_admin || $u->can('da.edit') || $u->can('da.activate') || $record->raised_by === $u->id,
        403
    );
    $record->load(['items', 'photos', 'supplier', 'history']);

    return PdfExport::download('da.pdf', ['record' => $record], "da-{$record->da_number}.pdf");
})->middleware(['auth', 'verified'])->name('da.pdf');

Route::get('da/{record}', App\Livewire\Da\Show::class)
    ->middleware(['auth', 'verified'])
    ->name('da.show');

// ── OGA (Outwards Goods Advice) — Phase 6.8c ──
Route::get('oga', App\Livewire\Oga\Index::class)
    ->middleware(['auth', 'verified'])
    ->name('oga');

Route::get('oga/create', App\Livewire\Oga\Create::class)
    ->middleware(['auth', 'verified'])
    ->name('oga.create');

Route::get('oga/{record}/pdf', function (OutwardsGoodsAdvice $record) {
    $u = auth()->user();
    abort_unless($u->can('oga.view'), 403);
    // ownership scope — mirrors Oga\Show::mount() (suppliers limited to their own)
    if ($u->hasRole('supplier') && ! $u->is_super_admin && ! $u->hasAnyRole(['admin', 'warehouse_staff'])) {
        abort_unless($u->supplier_id && $record->supplier_id === $u->supplier_id, 403);
    }
    $record->load(['items', 'photos', 'supplier', 'history']);

    return PdfExport::download('oga.pdf', ['record' => $record], "oga-{$record->oga_number}.pdf");
})->middleware(['auth', 'verified'])->name('oga.pdf');

Route::get('oga/{record}', App\Livewire\Oga\Show::class)
    ->middleware(['auth', 'verified'])
    ->name('oga.show');

// ── Equipment & Tools (ທະບຽນ ເຄື່ອງ + ກວດກາ/ນຳໃຊ້/ບຳລຸງ) ──
Route::get('equipment', App\Livewire\Equipment\Index::class)
    ->middleware(['auth', 'verified'])
    ->name('equipment');

Route::get('equipment/inspection-templates', InspectionTemplates::class)
    ->middleware(['auth', 'verified'])
    ->name('equipment.templates');

Route::get('equipment/maintenance-templates', MaintenanceTemplates::class)
    ->middleware(['auth', 'verified'])
    ->name('equipment.maintenance-templates');

Route::get('equipment/categories', Categories::class)
    ->middleware(['auth', 'verified'])
    ->name('equipment.categories');

Route::get('equipment/maintenance/{record}/pdf', function (EquipmentMaintenance $record) {
    $u = auth()->user();
    abort_unless($u->can('equipment.view'), 403);
    // ພະແນກ scope — department_admin ດຶງ ໄດ້ ສະເພາະ ເຄື່ອງ ພະແນກ ຕົນ (ກັນ IDOR).
    $record->load('equipment');
    abort_if($u->equipmentDepartmentScoped() && $record->equipment?->department_id !== $u->department_id, 403);

    return PdfExport::download('equipment.maintenance-pdf', ['record' => $record], "maintenance-{$record->id}.pdf");
})->middleware(['auth', 'verified'])->name('equipment.maintenance.pdf');

Route::get('equipment/inspection/{record}/pdf', function (EquipmentInspection $record) {
    $u = auth()->user();
    abort_unless($u->can('equipment.view'), 403);
    $record->load(['equipment', 'template']);
    abort_if($u->equipmentDepartmentScoped() && $record->equipment?->department_id !== $u->department_id, 403);

    return PdfExport::download('equipment.inspection-pdf', ['record' => $record], "inspection-{$record->id}.pdf");
})->middleware(['auth', 'verified'])->name('equipment.inspection.pdf');

// ── Expo Info (mini-CRM) — Phase 6.9 ──
Route::get('expo', App\Livewire\Expo\Index::class)
    ->middleware(['auth', 'verified'])
    ->name('expo');

Route::get('expo/create', App\Livewire\Expo\Create::class)
    ->middleware(['auth', 'verified'])
    ->name('expo.create');

Route::get('expo/{record}/pdf', function (ExpoEvent $record) {
    abort_unless(auth()->user()->can('expo.view'), 403);
    $record->load(['attendees', 'companies.contacts', 'companies.files']);

    return PdfExport::download('expo.pdf', ['record' => $record], "expo-{$record->expo_number}.pdf");
})->middleware(['auth', 'verified'])->name('expo.pdf');

// Business-card image (third-party PII) — auth-gated, streamed from the private disk
// (falls back to the legacy public path until `expo:privatize-cards` has run).
Route::get('expo/contacts/{contact}/card', function (ExpoContact $contact) {
    abort_unless(auth()->user()->can('expo.view'), 403);
    abort_unless($contact->business_card_path, 404);
    foreach (['local', 'public'] as $disk) {
        if (Storage::disk($disk)->exists($contact->business_card_path)) {
            return Storage::disk($disk)->response($contact->business_card_path);
        }
    }
    abort(404);
})->middleware(['auth', 'verified'])->name('expo.card');

Route::get('expo/{record}', App\Livewire\Expo\Show::class)
    ->middleware(['auth', 'verified'])
    ->name('expo.show');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

// Settings hub — consolidates users/roles/org/uom/suppliers/audit/reports/notifications
Route::view('settings', 'settings.index')
    ->middleware(['auth', 'verified'])
    ->name('settings');

Route::get('settings/organization', Organization::class)
    ->middleware(['auth', 'verified'])
    ->name('settings.organization');

Route::get('settings/users', Users::class)
    ->middleware(['auth', 'verified'])
    ->name('settings.users');

Route::get('settings/roles', RolesPermissions::class)
    ->middleware(['auth', 'verified'])
    ->name('settings.roles');

Route::get('settings/facilities', Facilities::class)
    ->middleware(['auth', 'verified'])
    ->name('settings.facilities');

Route::get('settings/uom', Uom::class)
    ->middleware(['auth', 'verified'])
    ->name('settings.uom');

Route::get('settings/condition-statuses', ConditionStatuses::class)
    ->middleware(['auth', 'verified'])
    ->name('settings.condition-statuses');

Route::get('settings/request-types', \App\Livewire\Settings\RequestTypes::class)
    ->middleware(['auth', 'verified'])
    ->name('settings.request-types');

Route::get('settings/clear-test-data', ClearTestData::class)
    ->middleware(['auth', 'verified'])
    ->name('settings.clear-test-data');

Route::get('settings/suppliers', Suppliers::class)
    ->middleware(['auth', 'verified'])
    ->name('settings.suppliers');

Route::get('settings/suppliers/{supplier}', SupplierDetail::class)
    ->middleware(['auth', 'verified'])
    ->name('settings.suppliers.show');

Route::get('settings/system', System::class)
    ->middleware(['auth', 'verified'])
    ->name('settings.system');

Route::get('settings/notifications', Notifications::class)
    ->middleware(['auth', 'verified'])
    ->name('settings.notifications');

Route::get('settings/email', Email::class)
    ->middleware(['auth', 'verified'])
    ->name('settings.email');

Route::get('settings/ldap', Ldap::class)
    ->middleware(['auth', 'verified'])
    ->name('settings.ldap');

Route::get('settings/notification-log', NotificationLog::class)
    ->middleware(['auth', 'verified'])
    ->name('settings.notification-log');

Route::get('settings/audit', Audit::class)
    ->middleware(['auth', 'verified'])
    ->name('settings.audit');

Route::get('settings/reports', Reports::class)
    ->middleware(['auth', 'verified'])
    ->name('settings.reports');

Route::get('settings/translations', Translations::class)
    ->middleware(['auth', 'verified'])
    ->name('settings.translations');

Route::get('settings/backup', Backup::class)
    ->middleware(['auth', 'verified'])
    ->name('settings.backup');

// Customer Satisfaction Survey — public form (anonymous or logged-in) + admin results.
Route::get('survey', SurveyForm::class)->name('survey');
Route::get('survey/results', SurveyResults::class)
    ->middleware(['auth', 'verified'])
    ->name('survey.results');

require __DIR__.'/auth.php';
