<?php

namespace App\Console\Commands;

use App\Services\GoalsBankImportService;
use Illuminate\Console\Command;
use InvalidArgumentException;

class ImportGoalsBankCommand extends Command
{
    protected $signature = 'speech:import-goals-bank {--path= : Path to the goals bank .docx (defaults to config speech.goals_bank_path)}';

    protected $description = 'Import (idempotently) the goals bank Word document into goal_domains / bank_goals';

    public function handle(GoalsBankImportService $service): int
    {
        $path = $this->option('path') ?: config('speech.goals_bank_path');
        if (! str_starts_with((string) $path, '/')) {
            $path = base_path((string) $path);
        }

        $this->info("Importing: {$path}");

        try {
            $s = $service->import($path);
        } catch (InvalidArgumentException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->newLine();
        $this->table(
            ['Domain', 'Goals'],
            collect($s['by_domain'])->map(fn ($n, $d) => [$d, $n])->values()->all(),
        );

        $this->table(['Metric', 'Count'], [
            ['Parsed goals', $s['parsed_total']],
            ['Domains created', $s['domains_created']],
            ['Domains total', $s['domains_total']],
            ['Goals created', $s['goals_created']],
            ['Goals updated', $s['goals_updated']],
            ['Goals unchanged', $s['goals_unchanged']],
            ['Goals skipped (no domain)', $s['goals_skipped']],
            ['Duplicates inside file', $s['duplicates_in_file']],
            ['Needs review', $s['needs_review_count']],
        ]);

        if ($s['needs_review'] !== []) {
            $this->warn('Rows flagged for review:');
            $this->table(
                ['Domain', 'Level', 'Code', 'Reason', 'Goal'],
                collect($s['needs_review'])->map(fn ($r) => [
                    $r['domain'] ?? '-',
                    $r['level'] ?? '-',
                    $r['printed_code'] ?? '-',
                    $r['review_reason'],
                    mb_strimwidth((string) $r['goal_text'], 0, 60, '…'),
                ])->all(),
            );
        }

        return self::SUCCESS;
    }
}
