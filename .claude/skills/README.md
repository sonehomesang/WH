# WH — Claude Skills

ໂຟນເດີນີ້ມີ skill files ສຳລັບ project WH (Warehouse Information System Laravel rewrite).

## ມັນເຮັດວຽກແນວໃດ

Claude Code **ໂຫຼດ skills ອັດຕະໂນມັດ** ເມື່ອ description ຂອງ skill ກົງກັບສິ່ງທີ່ user ກຳລັງເຮັດ. ບໍ່ຕ້ອງ paste ດ້ວຍຕົນເອງ — Claude ກວດ description ແລ້ວດຶງ context ມາໃຫ້ເອງ.

ບ່ອນເກັບ: `D:\MyApps\WH\.claude\skills\` (project-level, ໃຊ້ສະເພາະ WH).

## Skills ປະຈຸບັນ (3 files)

| Skill | ໂຫຼດເມື່ອ | ໃຊ້ສຳລັບ |
|---|---|---|
| **wh-app** | ທຸກວຽກໃນ WH (migrations, models, Livewire, Blade, …) | Project context — locked stack, frozen decisions, doc references, code-as-truth |
| **wh-safe-changes** | ໃດໆທີ່ສ້າງ/ແກ້/ລົບ ໄຟລ໌, migrate, refactor | 8-step workflow + 2 mandatory gates + risk matrix + recovery |
| **wh-source-of-truth** | ຄຳຖາມກ່ຽວກັບ schema / RBAC / state machine / fields | Disambiguates v1 docs vs v1 code (code wins) |

## ການເພີ່ມ / ແກ້ skill

ສ້າງໄຟລ໌ `.md` ໃໝ່ດ້ວຍ frontmatter:

```markdown
---
name: skill-name (kebab-case, unique)
description: ບອກວ່າເມື່ອໃດທີ່ skill ນີ້ຄວນຖືກໂຫຼດ — ສະເພາະ + ກວ້າງພໍ
---

# Skill content
...
```

ກົດສຳຄັນ:
- **description** ສະເພາະ → Claude ໂຫຼດຖືກ
- ບໍ່ກວ້າງເກີນ (ໂຫຼດທຸກຄັ້ງ = ບໍ່ມີຄຸນຄ່າ)
- ບໍ່ແຄບເກີນ (ບໍ່ມີຫຍັງ trigger = ບໍ່ມີຄຸນຄ່າ)

## ການ override

Skills ໃນ folder ນີ້ **ບໍ່ override** SOP ໃນ `D:\MyApps\WIS-tooling\docs\v2\WORKFLOW_SOP.md`. ມັນເພີ່ມເຕີມ — ບໍ່ປ່ຽນແທນ.

ຖ້າ user ໃຫ້ຄຳສັ່ງ direct ທີ່ຂັດກັບ skill — **user wins**.

## ກ່ຽວຂ້ອງ

- Source SOP: `D:\MyApps\WIS-tooling\docs\v2\WORKFLOW_SOP.md`
- Locked decisions: `D:\MyApps\WIS-tooling\docs\v2\PHASE_6.3_KICKOFF.md`
- Project overview: `D:\MyApps\WIS-tooling\docs\v2\README.md`

## ການກວດສອບ skills

Claude ສະແດງ skills ທີ່ໂຫຼດໃນ session start ຫຼື ເມື່ອ user ຖາມໂດຍກົງ. ກວດດ້ວຍ:

> "Claude, ມີ skill ໃດໂຫຼດໄວ້ບໍ?"

ຫຼື ໃນ Claude Code: `/skills` (ຖ້າມີ slash command).
