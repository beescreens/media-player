---
name: archive-plan
description: Archive a Claude Code plan in .agents/plans, with the initial request, the comments, the questions and answers, and the final plan. Use whenever the user asks to archive, save, store, export or refresh a plan made in plan mode, asks where past plans are, or says that a plan archive is missing or incomplete.
---

# Archive plan

Hooks in `.claude/settings.json` archive plans automatically when a plan is
submitted, approved or rejected. Use this skill to archive or refresh the latest
plan of the session by hand:

```bash
php .agents/skills/archive-plan/scripts/archive-plan.php --session ${CLAUDE_SESSION_ID}
```

The script builds the archive from the session transcript so that the user's
words stay verbatim. Do not rewrite or summarise its output: a paraphrase would
defeat the purpose of the archive. Running it again updates the same file, so
manual edits to that file are lost. It writes files, so run it once plan mode
has ended.

Archives are named `YYYY-MM-DD-HHMM-<title>.md`, after the date and time (UTC) of
the initial request, so they sort chronologically.

The `plan-id` comment at the top of an archive is the transcript ID of the
initial request. The script uses it to find the archive again, so keep it when
renaming a file. To find the session of a plan, search the transcripts:
`grep -l <plan-id> ~/.claude/projects/*/*.jsonl` (the file name is the session
ID, for `claude --resume <session-id>`).

Afterwards, give the user the path of the file. Remind them to check it for
secrets and personal data before committing, and only commit if they ask.

If no plan is found, say so rather than writing an archive from memory.
