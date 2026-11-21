<?php

namespace App\Console\Commands;

use App\Models\Student;
use Illuminate\Console\Command;
use PhpOffice\PhpWord\Element\Table;
use PhpOffice\PhpWord\Element\Text;
use PhpOffice\PhpWord\Element\TextBreak;
use PhpOffice\PhpWord\Element\TextRun;
use PhpOffice\PhpWord\IOFactory;

/**
 * Imports student names only from the tracking sheet Word file.
 * Does not assign teachers, academic year, or semester.
 */
class ImportTrackingStudentsCommand extends Command
{
    protected $signature = 'speech:import-tracking-students
        {--path= : Path to the tracking sheet .docx}
        {--dry-run : Parse and list names without writing}';

    protected $description = 'استيراد أسماء الطلاب فقط من كشف مراجعة الخطط (بدون معلمة/عام/فصل)';

    public function handle(): int
    {
        $path = $this->option('path')
            ?: base_path('project-inputs/كشف مراجعة خطط طلاب الصباحي والمسائي 1448هـ.docx');

        // Accept either filename spelling present on disk.
        if (! is_file($path)) {
            $matches = glob(base_path('project-inputs/كشف مراجعة خطط طلاب*.docx')) ?: [];
            $path = $matches[0] ?? $path;
        }

        if (! is_file($path)) {
            $this->error('ملف الكشف غير موجود: '.$path);

            return self::FAILURE;
        }

        $names = $this->extractUniqueNames($path);
        $this->info('أسماء فريدة مستخرجة: '.count($names));

        if ($this->option('dry-run')) {
            foreach ($names as $name) {
                $this->line(' - '.$name);
            }

            return self::SUCCESS;
        }

        $created = 0;
        $skipped = 0;

        foreach ($names as $name) {
            $exists = Student::withTrashed()->where('full_name', $name)->exists();
            if ($exists) {
                $skipped++;

                continue;
            }

            Student::create([
                'full_name' => $name,
                'teacher_id' => null,
                'period' => null,
                'academic_year' => null,
                'semester' => null,
            ]);
            $created++;
        }

        $this->table(
            ['النتيجة', 'العدد'],
            [
                ['تم إنشاؤهم', $created],
                ['موجودون مسبقاً (تم التخطي)', $skipped],
                ['الإجمالي في الملف', count($names)],
            ]
        );

        return self::SUCCESS;
    }

    /** @return list<string> */
    private function extractUniqueNames(string $path): array
    {
        $phpWord = IOFactory::load($path);
        $seen = [];
        $names = [];

        foreach ($phpWord->getSections() as $section) {
            foreach ($section->getElements() as $element) {
                if (! $element instanceof Table) {
                    continue;
                }

                foreach ($element->getRows() as $rowIndex => $row) {
                    if ($rowIndex === 0) {
                        continue; // header
                    }
                    $cells = $row->getCells();
                    if (count($cells) < 3) {
                        continue;
                    }
                    $name = $this->cellText($cells[2]);
                    $name = preg_replace('/\s+/u', ' ', trim($name)) ?? '';
                    if ($name === '' || $name === 'أسماء الطلاب') {
                        continue;
                    }
                    if (isset($seen[$name])) {
                        continue;
                    }
                    $seen[$name] = true;
                    $names[] = $name;
                }
            }
        }

        return $names;
    }

    private function cellText(mixed $cell): string
    {
        $parts = [];
        foreach ($cell->getElements() as $el) {
            $parts[] = $this->elementText($el);
        }

        return trim(implode('', $parts));
    }

    private function elementText(mixed $el): string
    {
        if ($el instanceof Text) {
            return (string) $el->getText();
        }
        if ($el instanceof TextBreak) {
            return ' ';
        }
        if ($el instanceof TextRun) {
            $out = '';
            foreach ($el->getElements() as $child) {
                $out .= $this->elementText($child);
            }

            return $out;
        }
        if (method_exists($el, 'getElements')) {
            $out = '';
            foreach ($el->getElements() as $child) {
                $out .= $this->elementText($child);
            }

            return $out;
        }
        if (method_exists($el, 'getText')) {
            return (string) $el->getText();
        }

        return '';
    }
}
