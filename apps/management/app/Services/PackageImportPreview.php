<?php

namespace App\Services;

use App\Models\ServicePackage;
use Illuminate\Support\Facades\Validator;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use RuntimeException;

class PackageImportPreview
{
    public const COLUMNS = [
        'code',
        'name_th',
        'description_th',
        'price',
        'sale_price',
        'effective_from',
        'effective_until',
        'terms',
        'keywords',
    ];

    /**
     * @return array{new_count: int, duplicate_count: int, invalid_count: int, rows: array<int, array<string, mixed>>}
     */
    public function analyze(string $path): array
    {
        $sheet = IOFactory::load($path)->getActiveSheet();
        $rawRows = $sheet->toArray(null, true, true, false);
        $headings = array_map(fn (mixed $value) => $this->heading($value), array_shift($rawRows) ?? []);

        if ($headings !== self::COLUMNS) {
            throw new RuntimeException('หัวตารางไม่ถูกต้อง กรุณาดาวน์โหลดไฟล์ต้นแบบใหม่และห้ามเปลี่ยนชื่อหรือสลับคอลัมน์');
        }

        $existing = ServicePackage::query()
            ->whereNotNull('code')
            ->pluck('code')
            ->mapWithKeys(fn (string $code) => [mb_strtoupper(trim($code)) => true])
            ->all();

        $seen = [];
        $previewRows = [];
        $counts = ['new_count' => 0, 'duplicate_count' => 0, 'invalid_count' => 0];

        foreach ($rawRows as $offset => $values) {
            if (collect($values)->every(fn (mixed $value) => $value === null || trim((string) $value) === '')) {
                continue;
            }

            $rowNumber = $offset + 2;
            $row = array_combine(self::COLUMNS, array_pad(array_slice($values, 0, count(self::COLUMNS)), count(self::COLUMNS), null));
            $row['code'] = mb_strtoupper(trim((string) ($row['code'] ?? '')));
            $row['name_th'] = trim((string) ($row['name_th'] ?? ''));
            $row['effective_from'] = $this->date($row['effective_from'] ?? null);
            $row['effective_until'] = $this->date($row['effective_until'] ?? null);

            $validator = Validator::make($row, [
                'code' => ['required', 'string', 'max:60'],
                'name_th' => ['required', 'string', 'max:255'],
                'description_th' => ['nullable', 'string', 'max:5000'],
                'price' => ['nullable', 'numeric', 'min:0'],
                'sale_price' => ['nullable', 'numeric', 'min:0'],
                'effective_from' => ['nullable', 'date'],
                'effective_until' => ['nullable', 'date', 'after_or_equal:effective_from'],
                'terms' => ['nullable', 'string', 'max:5000'],
                'keywords' => ['nullable', 'string', 'max:1000'],
            ]);

            if ($validator->fails()) {
                $status = 'invalid';
                $reason = $validator->errors()->first();
                $counts['invalid_count']++;
            } elseif (isset($existing[$row['code']]) || isset($seen[$row['code']])) {
                $status = 'duplicate';
                $reason = 'รหัสนี้มีอยู่แล้ว ระบบจะข้ามและไม่เขียนทับ';
                $counts['duplicate_count']++;
            } else {
                $status = 'new';
                $reason = 'พร้อมเพิ่มเป็นฉบับร่าง';
                $counts['new_count']++;
                $seen[$row['code']] = true;
            }

            if (count($previewRows) < 50) {
                $previewRows[] = [
                    'row' => $rowNumber,
                    'code' => $row['code'],
                    'name_th' => $row['name_th'],
                    'status' => $status,
                    'reason' => $reason,
                ];
            }
        }

        return $counts + ['rows' => $previewRows];
    }

    private function heading(mixed $value): string
    {
        return strtolower(trim(str_replace("\xEF\xBB\xBF", '', (string) $value)));
    }

    private function date(mixed $value): ?string
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        if (is_numeric($value)) {
            return Date::excelToDateTimeObject((float) $value)->format('Y-m-d');
        }

        return trim((string) $value);
    }
}
