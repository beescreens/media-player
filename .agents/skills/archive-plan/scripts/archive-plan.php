#!/usr/bin/env php
<?php

declare(strict_types=1);

/*
 * Archives Claude Code plans from the session transcript to docs/plans.
 *
 * As a hook, the hook input is read as JSON from stdin.
 * Manually: php archive-plan.php --session <session-id>
 *
 * Hooks must never disturb the session: in hook mode, nothing is written to
 * stdout and the exit code is never 2 (which would block the tool call).
 */

const PLAN_MARKER = '<!-- final-plan -->';

/** Returns the text typed by the user, without the context added by the tools. */
function cleanText(string $text): string
{
    $tags = 'system-reminder|ide_selection|ide_opened_file|ide_diagnostics|local-command-caveat|local-command-stdout|local-command-stderr';
    $text = preg_replace("~<($tags)\b[^>]*>.*?</\\1>~s", '', $text) ?? $text;
    $text = trim($text);

    if (preg_match('~^<(task-notification|agent-message|bash-(input|stdout|stderr))\b~', $text) || str_starts_with($text, '[Request interrupted')) {
        return '';
    }

    if (preg_match('~<command-name>/?(.*?)</command-name>~s', $text, $name)) {
        preg_match('~<command-args>(.*?)</command-args>~s', $text, $args);

        return trim('/'.$name[1].' '.($args[1] ?? ''));
    }

    return $text;
}

/** Joins the text blocks of a message or tool result. */
function textOf(mixed $content): string
{
    if (is_string($content)) {
        return $content;
    }

    $texts = [];

    foreach (is_array($content) ? $content : [] as $block) {
        if (is_string($block)) {
            $texts[] = $block;
        } elseif (($block['type'] ?? '') === 'text') {
            $texts[] = $block['text'] ?? '';
        } elseif (($block['type'] ?? '') === 'image') {
            $texts[] = '[image]';
        }
    }

    return implode("\n\n", $texts);
}

/** Extracts the comment from a rejected tool call, if any. */
function rejectionComment(string $result): string
{
    if (preg_match('~(?:the user said|user provided the following reason[^:]*|feedback):\s*(.+)$~is', $result, $match)) {
        // Claude Code appends its own note after the comment.
        return cleanText(preg_split('~\n+Note: The user\'s next message~', $match[1])[0]);
    }

    return '';
}

/**
 * Splits the transcript in planning cycles: from the request that started plan
 * mode to the approved plan, or to the first message outside of plan mode.
 */
function parseTranscript(string $path): array
{
    $cycles = [];
    $cycle = null;
    $lastPrompt = null;
    $lastReply = '';
    $tools = [];
    $seen = [];

    $close = function () use (&$cycle, &$cycles): void {
        if ($cycle !== null) {
            $cycles[] = $cycle;
        }

        $cycle = null;
    };

    $open = function () use (&$cycle, &$lastPrompt): void {
        if ($cycle === null && $lastPrompt !== null) {
            $cycle = $lastPrompt + ['events' => [], 'status' => null, 'plan' => ''];
        }
    };

    foreach (new SplFileObject($path) as $line) {
        $record = json_decode((string) $line, true);

        if (! is_array($record) || ($record['isSidechain'] ?? false)) {
            continue;
        }

        if (isset($record['uuid'])) {
            if (isset($seen[$record['uuid']])) {
                continue;
            }

            $seen[$record['uuid']] = true;
        }

        $type = $record['type'] ?? '';
        $content = $record['message']['content'] ?? '';
        $blocks = is_array($content) ? $content : [];

        if ($type === 'assistant') {
            foreach ($blocks as $block) {
                if (($block['type'] ?? '') === 'text' && trim($block['text'] ?? '') !== '') {
                    $lastReply = trim($block['text']);
                }

                if (($block['type'] ?? '') !== 'tool_use') {
                    continue;
                }

                $tools[$block['id']] = $block;

                if ($block['name'] === 'ExitPlanMode') {
                    $open();

                    if ($cycle !== null) {
                        $cycle['status'] = 'draft';
                        $cycle['toolUseId'] = $block['id'];
                        $cycle['plan'] = $block['input']['plan'] ?? '';
                        $lastReply = '';
                    }
                }
            }

            continue;
        }

        $prompt = null;

        if ($type === 'attachment' && ($record['attachment']['type'] ?? '') === 'queued_command') {
            // A message sent while Claude was working.
            $origin = $record['attachment']['origin']['kind'] ?? null;
            $isPrompt = ($record['attachment']['commandMode'] ?? 'prompt') === 'prompt';
            $prompt = $isPrompt && in_array($origin, [null, 'human'], true)
                ? cleanText(textOf($record['attachment']['prompt'] ?? ''))
                : null;
        } elseif ($type === 'user') {
            foreach ($blocks as $block) {
                if (($block['type'] ?? '') !== 'tool_result') {
                    continue;
                }

                $tool = $tools[$block['tool_use_id'] ?? ''] ?? null;
                $failed = (bool) ($block['is_error'] ?? false);
                $result = textOf($block['content'] ?? '');
                $details = is_array($record['toolUseResult'] ?? null) ? $record['toolUseResult'] : [];

                if ($tool === null) {
                    continue;
                }

                if ($tool['name'] === 'EnterPlanMode' && ! $failed) {
                    $open();
                } elseif ($tool['name'] === 'AskUserQuestion' && $cycle !== null) {
                    $cycle['events'][] = [
                        'type' => 'questions',
                        'questions' => $details['questions'] ?? $tool['input']['questions'] ?? [],
                        'answers' => $details['answers'] ?? [],
                        'annotations' => $details['annotations'] ?? [],
                        'fallback' => $failed ? rejectionComment($result) : $result,
                    ];
                    $lastReply = '';
                } elseif ($tool['name'] === 'ExitPlanMode' && $cycle !== null) {
                    if ($failed) {
                        $cycle['status'] = 'rejected';
                        $comment = rejectionComment($result);

                        if ($comment !== '') {
                            $cycle['events'][] = ['type' => 'comment', 'text' => $comment, 'context' => '', 'onPlan' => true];
                        }
                    } else {
                        // The user can edit the plan before approving it.
                        if (preg_match('~\n## Approved Plan(?: \(edited by user\))?:\n(.*)$~s', $result, $match)) {
                            $cycle['plan'] = trim($match[1]);
                        } elseif (is_string($details['plan'] ?? null) && $details['plan'] !== '') {
                            $cycle['plan'] = $details['plan'];
                        }

                        $cycle['status'] = 'approved';
                        $close();
                    }
                }
            }

            $origin = $record['origin']['kind'] ?? null;

            if (! ($record['isMeta'] ?? false) && ! ($record['isCompactSummary'] ?? false) && in_array($origin, [null, 'human'], true)) {
                $prompt = cleanText(textOf($content));
            }
        }

        if ($prompt === null || $prompt === '') {
            continue;
        }

        $mode = $record['permissionMode'] ?? null;

        if ($cycle !== null && $mode !== null && $mode !== 'plan') {
            $close();
        }

        if ($cycle !== null) {
            $cycle['events'][] = ['type' => 'comment', 'text' => $prompt, 'context' => $lastReply, 'onPlan' => false];
        }

        $lastPrompt = [
            'id' => $record['uuid'] ?? sha1((string) $line),
            'request' => $prompt,
            'date' => substr($record['timestamp'] ?? date('c'), 0, 10),
        ];
        $lastReply = '';

        if ($mode === 'plan') {
            $open();
        }
    }

    $close();

    return $cycles;
}

function quote(string $text): string
{
    return implode("\n", array_map(fn (string $line): string => rtrim('> '.$line), explode("\n", $text)));
}

function renderEvent(array $event): string
{
    if ($event['type'] === 'comment') {
        $context = $event['context'] !== '' ? "**Claude:**\n\n".quote($event['context'])."\n\n" : '';

        return $context.($event['onPlan'] ? '**Comment on the plan:**' : '**Comment:**')."\n\n".quote($event['text']);
    }

    $parts = [];

    foreach ($event['questions'] as $question) {
        $text = $question['question'] ?? '';
        $answer = $event['answers'][$text] ?? '';
        $answer = is_array($answer) ? implode(', ', $answer) : (string) $answer;
        $notes = $event['annotations'][$text]['notes'] ?? '';
        $lines = ['**Question:** '.$text, ''];

        foreach ($question['options'] ?? [] as $option) {
            $lines[] = '- '.($option['label'] ?? '').(($option['description'] ?? '') !== '' ? ': '.$option['description'] : '');
        }

        $lines[] = '';
        $lines[] = '**Answer:**';
        $lines[] = '';
        $lines[] = quote($answer !== '' ? $answer : ($event['fallback'] !== '' ? $event['fallback'] : 'Not answered'));

        if ($notes !== '') {
            $lines[] = quote("\nNotes: ".$notes);
        }

        $parts[] = implode("\n", $lines);
    }

    return implode("\n\n", $parts);
}

function writeArchive(array $cycle, string $directory): string
{
    if (! is_dir($directory) && ! mkdir($directory, 0755, true) && ! is_dir($directory)) {
        throw new RuntimeException("Cannot create {$directory}");
    }

    $marker = "<!-- plan-id: {$cycle['id']} -->";
    $file = null;
    $plan = trim($cycle['plan']);

    foreach (glob($directory.'/*.md') ?: [] as $candidate) {
        $existing = (string) file_get_contents($candidate);

        if (str_contains(substr($existing, 0, 1000), $marker)) {
            $file = $candidate;

            // The transcript does not always contain the plan: keep the known one.
            if ($plan === '') {
                $plan = trim(explode(PLAN_MARKER, $existing, 2)[1] ?? '');
            }

            break;
        }
    }

    $title = preg_match('~^#\s+(.+)$~m', $plan, $match) ? $match[1] : strtok($cycle['request'], "\n");
    $title = trim(preg_replace('~^(?:Implementation )?Plan\s*[:—–-]\s*~i', '', (string) $title) ?? '');
    $title = mb_strlen($title) > 80 ? mb_substr($title, 0, 77).'...' : $title;

    if ($file === null) {
        $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $title) ?: $title;
        $slug = trim(substr(trim(preg_replace('~[^a-z0-9]+~', '-', strtolower($ascii)) ?? '', '-'), 0, 60), '-') ?: 'plan';
        $base = "{$directory}/{$cycle['date']}-{$slug}";
        $file = $base.'.md';

        for ($i = 2; file_exists($file); $i++) {
            $file = "{$base}-{$i}.md";
        }
    }

    $discussion = $cycle['events'] === [] ? 'None.' : implode("\n\n---\n\n", array_map(renderEvent(...), $cycle['events']));
    $plan = $plan !== '' ? $plan : 'Not available.';

    $markdown = <<<MD
    # {$title}

    {$marker}

    - Date: {$cycle['date']}
    - Agent: Claude Code
    - Status: {$cycle['status']}

    ## Initial request


    MD;

    $markdown .= quote($cycle['request'])."\n\n## Discussion\n\n{$discussion}\n\n## Final plan\n\n".PLAN_MARKER."\n\n{$plan}\n";
    $temporary = $file.'.'.getmypid().'.tmp';

    if (file_put_contents($temporary, $markdown) === false || ! rename($temporary, $file)) {
        throw new RuntimeException("Cannot write {$file}");
    }

    return $file;
}

$options = getopt('', ['session:']);
$manual = isset($options['session']);

try {
    $root = getenv('CLAUDE_PROJECT_DIR') ?: dirname(__DIR__, 4);
    $input = [];

    if ($manual) {
        if (! preg_match('~^[A-Za-z0-9-]+$~', $options['session'])) {
            throw new RuntimeException('Invalid session ID.');
        }

        $config = getenv('CLAUDE_CONFIG_DIR') ?: getenv('HOME').'/.claude';
        $transcript = glob("{$config}/projects/*/{$options['session']}.jsonl")[0] ?? '';
    } else {
        $input = json_decode((string) stream_get_contents(STDIN), true);
        $transcript = is_array($input) ? ($input['transcript_path'] ?? '') : '';
    }

    if (! is_string($transcript) || ! is_file($transcript)) {
        if ($manual) {
            throw new RuntimeException('Transcript not found.');
        }

        exit(0);
    }

    $cycles = parseTranscript($transcript);

    // The transcript can lag behind the hook, which holds the submitted plan.
    if (($input['tool_name'] ?? '') === 'ExitPlanMode') {
        $plan = $input['tool_input']['plan'] ?? '';
        $approved = ($input['hook_event_name'] ?? '') === 'PostToolUse';
        $toolUseId = $input['tool_use_id'] ?? null;
        $index = array_key_last($cycles);

        foreach ($cycles as $key => $candidate) {
            if ($toolUseId !== null && ($candidate['toolUseId'] ?? null) === $toolUseId) {
                $index = $key;
            }
        }

        if ($index !== null && ($cycles[$index]['status'] !== 'approved' || ($cycles[$index]['toolUseId'] ?? null) === $toolUseId)) {
            $response = $input['tool_response'] ?? [];
            $edited = is_array($response) ? ($response['plan'] ?? '') : '';
            $cycles[$index]['status'] = $approved ? 'approved' : 'draft';
            $cycles[$index]['plan'] = match (true) {
                is_string($edited) && $edited !== '' => $edited,
                is_string($plan) && $plan !== '' => $plan,
                default => $cycles[$index]['plan'],
            };
        }
    }

    // Only the latest plan can still change: older archives may have been edited.
    $cycles = array_filter($cycles, fn (array $cycle): bool => $cycle['status'] !== null);
    $cycle = end($cycles);

    if ($cycle !== false) {
        // Once approved, only the approval itself or a manual run rewrites the archive.
        $refresh = $manual || ($input['tool_name'] ?? '') === 'ExitPlanMode' || $cycle['status'] !== 'approved';
        $file = $refresh ? writeArchive($cycle, $root.'/docs/plans') : null;
    }

    if ($manual) {
        echo ($file ?? 'No plan found in this session.').PHP_EOL;
    }
} catch (Throwable $exception) {
    fwrite(STDERR, 'archive-plan: '.$exception->getMessage().PHP_EOL);

    exit(1);
}
