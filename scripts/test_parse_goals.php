<?php

/**
 * Usage: php scripts/test_parse_goals.php [path/to/goals.docx] [--full]
 * Prints summary JSON (domain counts, review rows) to stdout.
 * With --full, prints the full parse result including all goals.
 */

require __DIR__.'/../vendor/autoload.php';

use App\Services\GoalsBankImporter;

$args = array_values(array_filter(array_slice($argv, 1), fn ($a) => ! str_starts_with($a, '--')));
$full = in_array('--full', $argv, true);

$path = $args[0] ?? __DIR__.'/../project-inputs/برنامج تنمية المهارات اللغوية.docx';

$result = (new GoalsBankImporter())->parse($path);

echo json_encode(
    $full ? $result : $result['summary'],
    JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
), PHP_EOL;
