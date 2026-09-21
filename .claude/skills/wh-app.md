---
name: wh-app
description: Use whenever working on the WH Warehouse Information System app. Loads on any task involving Laravel migrations, Eloquent models, Livewire components, Blade templates, controllers, services, tests, deployment, schema changes, RBAC, permissions, scope rules, notifications, or workflow phases (6.x). The WH project is a PHP 8.3 + Laravel 12 + MySQL + Livewire 3 rewrite of the Firebase WIS app. The Firebase reference at D:\MyApps\WIS-tooling\ is read-only blueprint — never modify it. Mobile + desktop are both first-class. Apply this skill when the user mentions any module: users, roles, units, departments, uoms, locations, buildings, rooms, suppliers, materials, inventory, borrow, deposit, request, da, oga, expo, notifications, dashboard, settings, reports, audit.
---

# WH — Warehouse Information System (Laravel rewrite)

## Project identity

- **Name:** WH (Warehouse Information System v2)
- **Working folder:** `D:\MyApps\WH\` (you are here)
- **Reference (READ-ONLY):** `D:\MyApps\WIS-tooling\` (Firebase blueprint — never modify)
- **Target host:** `wh.namtheun2.com` on cyberpersons.com
- **Database:** MySQL 8 / MariaDB 10.6+ (local: `wh_db` / `wh_admin`)
- **PHP:** 8.3.13 (via Laragon)

## Locked stack (do not propose alternatives)

| Layer | Choice |
|---|---|
| Backend | PHP 8.3 + Laravel 12 |
| Frontend | Blade + Livewire 3 + Alpine.js + Tailwind 3 |
| Auth | Laravel Breeze (Livewire stack) |
| RBAC | `spatie/laravel-permission` + `roles.scope_rules` JSON column |
| Database | MySQL via Eloquent ORM |
| File storage | Local filesystem (`storage/app/public`) |
| Real-time | Livewire `wire:poll` (3–5 s) — no WebSocket |
| Cache / Session / Queue | `file` / `database` / `database` (no Redis) |
| PDF | `barryvdh/laravel-dompdf` |
| Excel/CSV | `maatwebsite/excel` |
| Images | `intervention/image` |

## User profile (frozen)

- Not a professional developer; collaborates via AI
- Communicates in Lao primarily; English for code + technical terms
- Prefers atomic commits with clear messages
- Dislikes complex work with poor payoff (30 min effort for 30 sec saving → skip)
- Wants to review every step before commit
- Hates excessive context-switching, copy/paste loops

## Mobile + Desktop first-class

Both must work. See `D:\MyApps\WIS-tooling\docs\v2\STACK.md` §"Mobile + Desktop Support":

- Mobile-first design (Tailwind `sm:` `md:` `lg:` `xl:` breakpoints)
- Collapsible sidebar (hamburger toggle on `< md`)
- Touch targets ≥ 44×44 px (`min-h-[44px]`)
- Tables → card stack on mobile (`hidden md:block` / `md:hidden space-y-2`)
- Modals → full-screen on mobile (`inset-0 md:inset-auto`)
- Forms → single column on mobile (`grid-cols-1 md:grid-cols-2`)
- Camera capture: `<input type="file" accept="image/*" capture="environment">`
- PWA installable (Phase 6.10) via `vite-plugin-pwa`
- Real-device test required before any commit

## Authoritative documents (always check these first)

ALWAYS read these before answering schema/RBAC/workflow questions:

| Doc | What it answers |
|---|---|
| `D:\MyApps\WIS-tooling\docs\v2\WORKFLOW_SOP.md` | How to safely make changes (8-step procedure, 2 gates) |
| `D:\MyApps\WIS-tooling\docs\v2\PHASE_6.3_KICKOFF.md` | Locked answers to 11 architectural questions |
| `D:\MyApps\WIS-tooling\docs\v2\SCHEMA.md` | MySQL table structure (53 tables) |
| `D:\MyApps\WIS-tooling\docs\v2\RBAC_MATRIX.md` | Permission matrix + scope rules (21 menus × 7 roles) |
| `D:\MyApps\WIS-tooling\docs\v2\PHASE_PLAN.md` | 13 phase roadmap (6.0 → 6.12) |
| `D:\MyApps\WIS-tooling\docs\v2\STACK.md` | Stack decisions including mobile/PWA |
| `D:\MyApps\WIS-tooling\docs\v2\README.md` | Project overview |
| `D:\MyApps\WIS-tooling\docs\CLAUDE.md` | ABSOLUTE RULES inherited from v1 |
| `D:\MyApps\WIS-tooling\docs\EXPO_INFO.md` | Phase 5P Expo spec (current, accurate) |
| `D:\MyApps\WIS-tooling\docs\WORKFLOWS.md` | State machines for borrow/request/deposit |

## Code v1 is source of truth (NOT docs v1)

Docs `DATA_MODEL.md` and `ROLES_PERMISSIONS.md` are OLDER than code v1 — they predate the DA, OGA, and Expo modules. When docs and code disagree, **code wins**.

Authoritative code paths in `D:\MyApps\WIS-tooling\src\`:

| Domain | Source file |
|---|---|
| Identity / RBAC | `types/user.ts` + `scripts/seed/seed_roles.ts` |
| Borrow | `types/borrow.ts` + `services/borrowService.ts` |
| Deposit | `types/deposit.ts` + `services/depositService.ts` |
| Request | `types/request.ts` + `services/requestService.ts` |
| Discrepancy Advice | `types/discrepancyAdvice.ts` + `services/discrepancyAdviceService.ts` |
| Outwards Goods Advice | `types/outwardsGoodsAdvice.ts` + `services/outwardsGoodsAdviceService.ts` |
| Expo Info | `types/expo.ts` + `services/expoService.ts` + `docs/EXPO_INFO.md` |
| Inventory | `types/inventory.ts` |
| Suppliers / Materials | `types/supplier.ts` + `services/supplierService.ts` |
| Org dictionaries | `types/org.ts` |
| Notifications | `types/notification.ts` + `services/notificationDispatchService.ts` + `services/notificationTemplateService.ts` |
| Audit | `types/audit.ts` |

## Frozen architectural decisions (from PHASE_6.3_KICKOFF)

1. **21 menus** (dropped `translations` — Laravel uses lang files)
2. **7 roles** — super_admin, admin, warehouse_staff, approver, line_manager, requester, supplier
3. **MenuId list:** dashboard, inventory, borrow, deposit, request, da, oga, expo, catalog, supplier, units, departments, locations, buildings, rooms, users, roles, settings, reports, audit, notifications
4. **Spatie permission** + custom `roles.scope_rules` JSON column for transactionScope/inventoryScope/catalogScope
5. **Notifications hybrid:** Laravel native `notifications` table + custom `notification_broadcasts` + `notification_broadcast_reads` + `notification_templates`
6. **Soft delete** for users, master data, workflow records; hard delete only for pivots, old notifications, old audit log
7. **Counters** never deleted; sequence integrity is critical
8. **Settings keys:** `workflow`, `vat`, `letterhead`, `notifications` (4 keys, JSON payload)
9. **Borrow states (7):** draft → acknowledged → approved → active → overdue → returned → cancelled
10. **Deposit states (7):** draft → submitted → accepted → stored → needs_fix ⇄ stored → claimed → cancelled
11. **Request states (10):** draft → submitted → acknowledged → approved → validated → dispatched → received → completed (+ rejected, cancelled). NO `closed` state.
12. **Timezone:** UTC in DB, convert to Asia/Vientiane in display layer (`APP_TIMEZONE=UTC`)
13. **Email immutable** after user create (WIBT Fix #1 inherited)
14. **Photos** as filesystem paths (max 5MB), never base64
15. **dashboard + notifications** are universal — accessible by all active users via middleware, no permission matrix row needed

## Common pitfalls to avoid

- Treating `DATA_MODEL.md` as authoritative (it predates DA/OGA/Expo)
- Treating `ROLES_PERMISSIONS.md` as authoritative (missing DA/OGA/Expo)
- Cascading FK on inventory references in borrow (use RESTRICT)
- Storing photos as base64 (use Storage facade + path)
- Hardcoded credentials anywhere
- Adding features the user did not request
- "Improving" working code without permission
- Mass changes without proposing first
- Touching production without explicit consent

## When the user gives an ambiguous prompt

ASK. Do not assume. Do not infer. Examples:

❌ "Build users module" → Build with assumed fields → Wrong assumptions baked in
✅ "Build users module" → "Should I use the field list from `src/types/user.ts` lines 56-100, or are there changes to that schema?"

## Cross-reference to related skills

- `wh-safe-changes` — workflow gates for any code/schema/doc change
- `wh-source-of-truth` — disambiguating docs vs code

Load those skills automatically when their descriptions match.
