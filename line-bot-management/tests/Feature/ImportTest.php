<?php

namespace Tests\Feature;

use App\Models\ServicePackage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImportTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    private function csvUpload(array $rows): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'packages').'.csv';
        file_put_contents($path, collect($rows)->map(fn (array $row) => implode(',', $row))->implode("\n"));

        return new UploadedFile($path, 'packages.csv', 'text/csv', null, true);
    }

    private function mockWorkbookUpload(): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'packages').'.xlsx';
        copy(public_path('mock-data/mock-packages.xlsx'), $path);

        return new UploadedFile($path, 'mock-packages.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
    }

    public function test_preview_does_not_write_to_database_and_classifies_rows(): void
    {
        Storage::fake('local');
        ServicePackage::factory()->create(['code' => 'EXISTING-001', 'price' => 999]);

        $rows = [
            ['code', 'name_th', 'description_th', 'price', 'sale_price', 'effective_from', 'effective_until', 'terms', 'keywords'],
            ['EXISTING-001', 'รายการเดิม', '', '1', '', '', '', '', ''],
            ['PROMO-NEW-001', 'โปรโมชันใหม่', 'รายละเอียด', '1500', '1290', '2026-07-01', '2026-12-31', 'เงื่อนไข', 'โปรโมชัน'],
            ['', 'ไม่มีรหัส', '', '100', '', '', '', '', ''],
        ];

        $response = $this->actingAs($this->admin())
            ->post('/admin/imports/packages/preview', ['file' => $this->csvUpload($rows)]);

        $response->assertRedirect('/admin/imports')
            ->assertSessionHas('package_import_preview', fn (array $preview) =>
                $preview['new_count'] === 1
                && $preview['duplicate_count'] === 1
                && $preview['invalid_count'] === 1
            );

        $this->assertDatabaseCount('packages', 1);
        $this->assertDatabaseCount('import_records', 0);
    }

    public function test_confirm_adds_only_new_rows_as_drafts_and_never_overwrites_duplicate_code(): void
    {
        Storage::fake('local');
        ServicePackage::factory()->create(['code' => 'EXISTING-001', 'name_th' => 'ข้อมูลเดิม', 'price' => 999]);

        $rows = [
            ['code', 'name_th', 'description_th', 'price', 'sale_price', 'effective_from', 'effective_until', 'terms', 'keywords'],
            ['EXISTING-001', 'ห้ามทับ', '', '1', '', '', '', '', ''],
            ['PROMO-NEW-001', 'โปรโมชันใหม่', 'รายละเอียด', '1500', '1290', '2026-07-01', '2026-12-31', 'เงื่อนไข', 'โปรโมชัน'],
        ];

        $this->actingAs($this->admin())
            ->post('/admin/imports/packages/preview', ['file' => $this->csvUpload($rows)])
            ->assertSessionHas('package_import_preview');

        $preview = session('package_import_preview');

        $this->post('/admin/imports/packages/confirm', ['token' => $preview['token']])
            ->assertRedirect('/admin/imports')
            ->assertSessionHas('success');

        $this->assertDatabaseHas('packages', [
            'code' => 'EXISTING-001',
            'name_th' => 'ข้อมูลเดิม',
            'price' => 999,
        ]);
        $this->assertDatabaseHas('packages', [
            'code' => 'PROMO-NEW-001',
            'is_active' => true,
            'is_published' => false,
        ]);
        $this->assertDatabaseCount('packages', 2);
        $this->assertDatabaseHas('import_records', [
            'resource' => 'packages',
            'rows_imported' => 1,
            'rows_skipped' => 1,
            'rows_failed' => 0,
        ]);
    }

    public function test_preview_requires_a_file(): void
    {
        $this->actingAs($this->admin())
            ->from('/admin/imports')
            ->post('/admin/imports/packages/preview')
            ->assertSessionHasErrors('file');
    }

    public function test_downloadable_mock_workbook_matches_the_safe_import_format(): void
    {
        Storage::fake('local');

        $this->actingAs($this->admin())
            ->post('/admin/imports/packages/preview', ['file' => $this->mockWorkbookUpload()])
            ->assertRedirect('/admin/imports')
            ->assertSessionHas('package_import_preview', fn (array $preview) =>
                $preview['new_count'] === 3
                && $preview['duplicate_count'] === 0
                && $preview['invalid_count'] === 0
            );

        $this->assertDatabaseCount('packages', 0);
    }

    public function test_faq_and_knowledge_import_routes_do_not_exist(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post('/admin/imports/faqs')->assertNotFound();
        $this->actingAs($admin)->post('/admin/imports/knowledge')->assertNotFound();
    }
}
