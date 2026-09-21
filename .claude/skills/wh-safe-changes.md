---
name: wh-safe-changes
description: Use whenever proposing to create, modify, or delete files, run migrations, alter database schema, change RBAC, refactor code, install packages, or update planning docs in the WH Laravel project. Enforces a binding 8-step workflow with two mandatory user-approval gates (Gate 1 before code changes, Gate 2 before commits). Prevents destructive operations without explicit consent. Apply whenever the user asks Claude to build, create, add, write, edit, change, delete, refactor, migrate, install, deploy, or commit anything.
---

# WH — Safe Changes Workflow

The full SOP lives at `D:\MyApps\WIS-tooling\docs\v2\WORKFLOW_SOP.md`. This skill is the binding summary.

## The 8 steps (never skip, never reorder)

```
1. RECEIVE   — read the full prompt; classify the intent
2. EXPLORE   — read docs, code, state BEFORE thinking
3. PLAN      — propose 3-5 bullets + risk level + verify commands
   ⛔ GATE 1: wait for explicit "ໄປ" / "OK" / "ປະຕິບັດ" / "ດຳເນີນ"
4. SAFEGUARD — git status clean / branch / DB backup if destructive
5. EXECUTE   — one logical unit, Read-before-Write, idempotent
6. SELF-TEST — php artisan test / pint / migrate --pretend BEFORE showing user
7. REVIEW    — show files / lines / test output
   ⛔ GATE 2: wait for explicit "OK commit" / "Commit ໄດ້ເລີຍ"
8. COMMIT    — atomic message, TaskUpdate, move to next
```

## Gate 1 — counts as approval

- ✅ "ໄປ" / "go"
- ✅ "OK" / "ໂອເຄ" (in response to "Should I proceed?")
- ✅ "ປະຕິບັດ" / "ດຳເນີນ"
- ✅ "ສ້າງ" / "build it"

## Gate 1 — does NOT count as approval

- ❌ "ຄິດເບິ່ງ" / "think about"
- ❌ "ສະເໜີວິທີ" / "propose a way"
- ❌ "ຖ້າເຮັດ X ຈະເປັນແນວໃດ" / "what if"
- ❌ Silence (no response)
- ❌ A question back from the user
- ❌ "ດີ" alone (it's an opinion, not an instruction)

## Gate 2 — counts as approval

- ✅ "OK commit" / "Commit ໄດ້ເລີຍ"
- ✅ "ໄປ commit" / "ໂອເຄ commit"
- ✅ "ມາ commit ໄດ້"

## Gate 2 — does NOT count as approval

- ❌ "ດີ" alone
- ❌ "ເຫັນແລ້ວ" alone
- ❌ "OK" alone (without "commit")

## Risk matrix (consult before proposing)

| Action | Risk | Required gate |
|---|---|---|
| Create new file (test/dev) | 🟢 Low | Gate 1 + Gate 2 |
| Edit file (test/dev) | 🟢 Low | Gate 1 + Gate 2 |
| `php artisan migrate` (dev, new tables) | 🟡 Medium | Gate 1 + Gate 2 + state snapshot |
| `php artisan migrate` (dev, modifying existing) | 🟡 Medium | Gate 1 + 2 + DB backup |
| `php artisan migrate` (production) | 🔴 High | Gate 1 + 2 + DB backup + user present + recovery plan |
| Schema change on table with data | 🔴 High | + data migration plan + dry-run |
| Delete a file | 🔴 High | Gate 1 + 2 + explicit "ລົບ" instruction |
| Drop column / table | 🔴 High | + data backup + explicit "drop" instruction |
| Force push | 🔴 Critical | + explicit "force push" instruction + reason |
| Touch production | 🔴 Critical | + user present + recovery plan + rehearsal |
| Multi-file refactor | 🟡 Medium | + small atomic commits + verify between each |

## Mandatory practices

### Read before Edit
Always Read a file before editing it. No exceptions. Even for one-line changes.

### Atomic commits
One logical unit per commit. NEVER bundle multiple Phase steps in one commit.

### Test before commit
`php artisan test` must pass before showing user "ready for review".

### Idempotent commands when possible
Prefer `migrate:fresh --seed` over `migrate` in dev. Prefer `firstOrCreate` over `create` in seeders.

### Never delete without explicit "ລົບ" / "delete"
- `rm` / `Remove-Item` — ask first
- `php artisan migrate:rollback` — ask first
- `git reset --hard` — ask first
- `DROP TABLE` / `TRUNCATE` — ask first
- `Storage::delete()` — ask first

### Never amend or force-push pushed commits
Create a new commit instead. Force-push only with `--force-with-lease` AND explicit user instruction.

## Templates for communication

### Propose plan
```
## ແຜນ [name]

### ໄຟລ໌ທີ່ຈະແຕະ
- file1 (NEW)
- file2 (modify lines X-Y)

### ສິ່ງທີ່ຈະປ່ຽນ
[3-5 bullets]

### Risk: [Low / Medium / High]
[reason]

### Verify ດ້ວຍ
- command 1
- command 2

ບອກ "ໄປ" → ເລີ່ມ. ບອກ "ປ່ຽນ X" → ປັບແຜນ.
```

### Report success
```
## ✅ ສຳເລັດ — [task name]

### Files
- file1 (NEW, N lines)
- file2 (modified, +N -M lines)

### Verified
- ✅ php artisan migrate — sucessful
- ✅ php artisan test — N passed, 0 failed

ກວດ → ບອກ "OK commit" → commit.
```

### Report failure
```
## ⚠ ບໍ່ສຳເລັດ — [task name]

### What I did
[steps taken]

### What failed
[error + likely cause]

### Options
A. Rollback all changes (safe)
B. Debug ຕໍ່
C. Skip this part, continue with [next thing]

ບອກ A / B / C → ດຳເນີນ.
```

### Need clarification
```
## ❓ ຕ້ອງຄຳຊີ້ແຈງ — [task name]

[1-2 lines of context]

### ຄຳຖາມ
1. [Q1]
2. [Q2]

ບໍ່ສືບຕໍ່ຈົນກວ່າຈະຕອບ.
```

## Recovery procedures

### Code commit was wrong
```bash
git reset --soft HEAD~1   # before push, keep changes staged
git revert <sha>          # after push, safe (creates undo commit)
```

### Migration was wrong
```bash
php artisan migrate:rollback              # one step
php artisan migrate:rollback --step=3     # three steps
php artisan migrate:fresh --seed          # nuclear (dev only)
```

### Data corruption (dev)
```bash
# Restore from MySQL dump
mysql -u wh_admin -p wh_db < backups/wh_db_TIMESTAMP.sql
```

### Production crisis
DO NOT act without the user. Steps:
1. Tell the user immediately
2. State exactly what is broken
3. Propose recovery options
4. Wait for user's decision
5. Never improvise on production
