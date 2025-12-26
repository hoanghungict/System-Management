<?php

declare(strict_types=1);

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class StudentImportTemplateExport implements FromArray, WithHeadings
{
    protected array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function array(): array
    {
        // Skip first row (headings) and return data
        return array_slice($this->data, 1);
    }

    public function headings(): array
    {
        // Return first row as headings
        return $this->data[0] ?? [];
    }
}


