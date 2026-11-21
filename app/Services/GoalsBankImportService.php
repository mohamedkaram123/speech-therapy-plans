<?php

namespace App\Services;

use App\Models\BankGoal;
use App\Models\GoalDomain;
use Illuminate\Support\Facades\DB;

/**
 * Persists the output of GoalsBankImporter::parse() into goal_domains / bank_goals.
 *
 * Idempotent: domains are keyed by name and goals by fingerprint, so running the
 * import twice yields the same rows (second run reports everything "unchanged").
 */
class GoalsBankImportService
{
    /** Canonical domain order (matches the source document). */
    private const DOMAIN_ORDER = [
        GoalsBankImporter::DOMAIN_RECEPTIVE,
        GoalsBankImporter::DOMAIN_EXPRESSIVE,
        GoalsBankImporter::DOMAIN_PRAGMATICS_DIALOGUE,
        GoalsBankImporter::DOMAIN_PRELANGUAGE,
        GoalsBankImporter::DOMAIN_AUDITORY_MEMORY,
        GoalsBankImporter::DOMAIN_ARTICULATORS,
        GoalsBankImporter::DOMAIN_METALANGUAGE,
    ];

    public function __construct(private readonly GoalsBankImporter $importer) {}

    /**
     * @return array{
     *   path: string,
     *   parsed_total: int,
     *   domains_created: int,
     *   domains_total: int,
     *   goals_created: int,
     *   goals_updated: int,
     *   goals_unchanged: int,
     *   goals_skipped: int,
     *   duplicates_in_file: int,
     *   by_domain: array<string,int>,
     *   by_domain_level: array<string,array<string,int>>,
     *   needs_review: array<int,array<string,mixed>>,
     *   needs_review_count: int,
     * }
     */
    public function import(string $path): array
    {
        $parsed = $this->importer->parse($path);

        return DB::transaction(function () use ($parsed, $path) {
            $domainIds = [];
            $domainsCreated = 0;
            $created = $updated = $unchanged = $skipped = $dupes = 0;
            $seen = [];

            foreach ($parsed['goals'] as $row) {
                $domainName = $row['domain'] ?? null;

                // goal_domain_id is mandatory: rows without a recognised domain are reported, not invented.
                if ($domainName === null || $domainName === '') {
                    $skipped++;

                    continue;
                }

                if (isset($seen[$row['fingerprint']])) {
                    $dupes++; // same fingerprint twice in one file: first occurrence wins

                    continue;
                }
                $seen[$row['fingerprint']] = true;

                if (! isset($domainIds[$domainName])) {
                    $domain = GoalDomain::updateOrCreate(
                        ['name' => $domainName],
                        ['sort_order' => $this->sortOrder($domainName)],
                    );
                    $domainsCreated += $domain->wasRecentlyCreated ? 1 : 0;
                    $domainIds[$domainName] = $domain->id;
                }

                $goal = BankGoal::updateOrCreate(
                    ['fingerprint' => $row['fingerprint']],
                    [
                        'goal_domain_id' => $domainIds[$domainName],
                        'level' => $row['level'],
                        'subcategory' => $row['subcategory'],
                        'printed_code' => $row['printed_code'],
                        'goal_text' => $row['goal_text'],
                        'training_elements' => $row['training_elements'],
                        'generalization' => $row['generalization'],
                        'needs_review' => (bool) $row['needs_review'],
                        'review_reason' => $row['review_reason'],
                    ],
                );

                if ($goal->wasRecentlyCreated) {
                    $created++;
                } elseif ($goal->wasChanged()) {
                    $updated++;
                } else {
                    $unchanged++;
                }
            }

            return [
                'path' => $path,
                'parsed_total' => $parsed['summary']['total'],
                'domains_created' => $domainsCreated,
                'domains_total' => count($domainIds),
                'goals_created' => $created,
                'goals_updated' => $updated,
                'goals_unchanged' => $unchanged,
                'goals_skipped' => $skipped,
                'duplicates_in_file' => $dupes,
                'by_domain' => $parsed['summary']['by_domain'],
                'by_domain_level' => $parsed['summary']['by_domain_level'],
                'needs_review' => $parsed['summary']['needs_review'],
                'needs_review_count' => count($parsed['summary']['needs_review']),
            ];
        });
    }

    private function sortOrder(string $domain): int
    {
        $i = array_search($domain, self::DOMAIN_ORDER, true);

        return $i === false ? count(self::DOMAIN_ORDER) + 1 : $i + 1;
    }
}
