<?php

namespace App\Imports;

use App\Models\ServicePackage;
use Illuminate\Database\Eloquent\Model;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class PackagesImport implements ToModel, WithHeadingRow, WithValidation, SkipsOnFailure
{
    use SkipsFailures;

    public int $imported = 0;
    public int $skipped = 0;

    /** @var array<string, true> */
    private array $seenCodes;

    public function __construct()
    {
        $this->seenCodes = ServicePackage::query()
            ->whereNotNull('code')
            ->pluck('code')
            ->mapWithKeys(fn (string $code) => [mb_strtoupper(trim($code)) => true])
            ->all();
    }

    /** @param array<string, mixed> $data */
    public function prepareForValidation(array $data, int $index): array
    {
        $data['code'] = mb_strtoupper(trim((string) ($data['code'] ?? '')));
        $data['effective_from'] = $this->date($data['effective_from'] ?? null);
        $data['effective_until'] = $this->date($data['effective_until'] ?? null);

        return $data;
    }

    /** @param array<string, mixed> $row */
    public function model(array $row): ?Model
    {
        $code = mb_strtoupper(trim((string) ($row['code'] ?? '')));

        if (isset($this->seenCodes[$code])) {
            $this->skipped++;
            return null;
        }

        $this->seenCodes[$code] = true;
        $this->imported++;

        return new ServicePackage([
            'code' => $code,
            'name_th' => $this->str($row['name_th'] ?? null),
            'description_th' => $this->str($row['description_th'] ?? null),
            'price' => $this->num($row['price'] ?? null),
            'sale_price' => $this->num($row['sale_price'] ?? null),
            'currency' => 'THB',
            'effective_from' => $this->date($row['effective_from'] ?? null),
            'effective_until' => $this->date($row['effective_until'] ?? null),
            'terms' => $this->str($row['terms'] ?? null),
            'keywords' => $this->str($row['keywords'] ?? null),
            'is_active' => true,
            'is_published' => false,
        ]);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:60'],
            'name_th' => ['required', 'string', 'max:255'],
            'description_th' => ['nullable', 'string', 'max:5000'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'sale_price' => ['nullable', 'numeric', 'min:0'],
            'effective_from' => ['nullable', 'date'],
            'effective_until' => ['nullable', 'date', 'after_or_equal:effective_from'],
            'terms' => ['nullable', 'string', 'max:5000'],
            'keywords' => ['nullable', 'string', 'max:1000'],
        ];
    }

    private function str(mixed $value): ?string
    {
        $value = $value === null ? null : trim((string) $value);
        return $value === '' ? null : $value;
    }

    private function num(mixed $value): ?float
    {
        $value = $this->str($value);
        return $value === null ? null : (float) $value;
    }

    private function date(mixed $value): ?string
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        return is_numeric($value)
            ? Date::excelToDateTimeObject((float) $value)->format('Y-m-d')
            : trim((string) $value);
    }
}
