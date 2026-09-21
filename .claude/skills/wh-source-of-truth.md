---
name: wh-source-of-truth
description: Use whenever resolving questions about database schema, table structure, field types, ENUM values, foreign keys, indexes, RBAC, permissions, scope rules, state machines, workflow stages, notification structure, settings keys, or any architectural detail in the WH Laravel project. Disambiguates conflicts between v1 docs (older) and v1 code (newer). Loads whenever the user asks "what should X look like", "is this correct", "does v1 have Y", or proposes a schema/RBAC/workflow design.
---

# WH — Source of Truth Resolver

When two sources disagree, use this skill to pick the winner.

## Hierarchy (in order)

```
1. PHASE_6.3_KICKOFF.md   — locked decisions from previous session
2. Code v1 (src/types/*)  — TypeScript types, current
3. Code v1 (seed_roles.ts)— RBAC matrix, current
4. EXPO_INFO.md           — Phase 5P spec (current)
5. v2/SCHEMA.md           — planned MySQL mapping (may need patching)
6. v2/PHASE_PLAN.md       — phase roadmap (may need patching)
7. WORKFLOWS.md           — state machines (mostly current)
8. DATA_MODEL.md          — OLD — predates DA/OGA/Expo
9. ROLES_PERMISSIONS.md   — OLD — missing DA/OGA/Expo rows
```

## When v2 docs and v1 code disagree, code wins

Examples that have already been verified:
- v2 PHASE_PLAN said Request ends with `closed` — code v1 says `completed`. **Use `completed`.**
- v1 DATA_MODEL lists 19 menus — code v1 lists 22, v2 picks 21. **Use 21 (drop translations).**
- v1 ROLES_PERMISSIONS matrix missing DA/OGA/Expo — code v1 `seed_roles.ts` has them. **Use `seed_roles.ts`.**

## Specific source files by domain

### Identity / Users
- Schema: `D:\MyApps\WIS-tooling\src\types\user.ts`
- Default users seeded: `scripts\seed\seed_test_users.ts`
- Super admin claim: `scripts\seed\seed_super_admin.ts`

### RBAC / Roles / Permissions / Scope
- Full matrix: `D:\MyApps\WIS-tooling\scripts\seed\seed_roles.ts:135-317`
- Type definitions: `src/types/user.ts` (`MenuId`, `PermissionAction`, `PermissionSet`, `ScopeRules`)
- Spatie config: TBD in WH project
- Already mapped: `D:\MyApps\WIS-tooling\docs\v2\PHASE_6.3_KICKOFF.md` §7 (verbatim port)

### Organization dictionaries
- All: `D:\MyApps\WIS-tooling\src\types\org.ts`
- Hierarchy: units → departments (FK), locations → buildings → rooms

### Suppliers / Materials / Inventory
- `src/types/supplier.ts`
- `src/types/inventory.ts`
- Material schema with price history: search `WIS/src/services/materialService.ts` for `priceHistory` array handling

### Workflow domains

| Domain | Type file | Service file (workflow logic) |
|---|---|---|
| Borrow | `src/types/borrow.ts` | `src/services/borrowService.ts` |
| Deposit | `src/types/deposit.ts` | `src/services/depositService.ts` |
| Request | `src/types/request.ts` | `src/services/requestService.ts` |
| DA | `src/types/discrepancyAdvice.ts` | `src/services/discrepancyAdviceService.ts` |
| OGA | `src/types/outwardsGoodsAdvice.ts` | `src/services/outwardsGoodsAdviceService.ts` |
| Expo | `src/types/expo.ts` | `src/services/expoService.ts` |

### State machines (verified against code 2026-06-15)

**Borrow (7 states):**
```
draft → acknowledged → approved → active → overdue → returned
              ↘                          ↘
            cancelled                  cancelled
```

**Deposit (7 states):**
```
draft → submitted → accepted → stored → needs_fix → stored (loop)
                                              ↘
                                            claimed
(cancelled from any state until claimed)
```

**Request (10 states, NO 'closed'):**
```
draft → submitted → acknowledged → approved → validated
      → dispatched → received → completed
(rejected from submitted/acknowledged/approved/validated)
(cancelled from draft/submitted)
```

### Notifications

- Type: `src/types/notification.ts` (Firestore custom shape with `userId` broadcast target)
- Dispatch: `src/services/notificationDispatchService.ts`
- Templates (Phase 5O): `src/services/notificationTemplateService.ts`
- v2 hybrid plan: PHASE_6.3_KICKOFF §3 Q5

### Settings

- Settings page: `src/pages/Settings.tsx`
- Letterhead: `src/pages/LetterheadSettings.tsx`
- VAT: `src/hooks/useVatSetting.ts`
- Notifications: `src/pages/NotificationSettings.tsx`
- v2 keys: `workflow`, `vat`, `letterhead`, `notifications` (4 keys, JSON payload)

### Counters

- Pattern: `<collection>-<YYYY>` (e.g., `borrowRecords-2026`)
- Implementation v1: each service has `generateNextXxxNumber()` function
- v2 plan: `Counter` model with composite PK `(prefix, year)` + `lockForUpdate`

## Verification before locking a decision

Before promoting any v2 schema design to "decided":

1. Open the v1 type file (`src/types/*.ts`)
2. Read the interface definition completely
3. Compare against v2 SCHEMA.md
4. If they disagree: code v1 wins UNLESS PHASE_6.3_KICKOFF explicitly overrides
5. Document the decision in your response

## Quick lookups

```bash
# Find a Firestore field name → MySQL column candidate
grep -rn "fieldName" D:/MyApps/WIS-tooling/src/types/

# Find how v1 enforces a rule
grep -rn "FieldName" D:/MyApps/WIS-tooling/src/services/

# Find a Firestore rule
grep -rn "match /collection" D:/MyApps/WIS-tooling/firestore.rules
```

## When in doubt

If the user asks "should we do X?" and the v1 code is silent on it, that's a NEW decision — propose it explicitly, do not assume the v1 design covers it.
