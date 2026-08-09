<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

/**
 * Generic empty-sheet export: emits only the heading row for a given resource so
 * users can download a blank template that matches the importer's expected columns.
 */
class TemplateExport implements FromArray, WithHeadings
{
    /**
     * @param  array<int, string>  $headings
     */
    public function __construct(private array $headings)
    {
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return $this->headings;
    }

    /**
     * No data rows — the template is intentionally blank below the heading.
     *
     * @return array<int, mixed>
     */
    public function array(): array
    {
        return [];
    }
}
