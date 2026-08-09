<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PackageRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Route is already guarded by auth + policy; admins may write.
        return (bool) $this->user()?->is_admin;
    }

    protected function prepareForValidation(): void
    {
        if (blank($this->currency)) {
            $this->merge(['currency' => 'THB']);
        }

        if ($this->filled('code')) {
            $this->merge(['code' => mb_strtoupper(trim((string) $this->code))]);
        }

        if (is_string($this->attributes) && trim($this->attributes) !== '') {
            $decoded = json_decode($this->attributes, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $this->merge(['attributes' => $decoded]);
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'category_id' => ['nullable', 'exists:package_categories,id'],
            'item_type' => ['required', 'string', 'max:40', 'regex:/^[a-z0-9_-]+$/'],
            'code' => [
                'nullable',
                'string',
                'max:60',
                Rule::unique('packages', 'code')->ignore($this->route('package')),
            ],
            'name_th' => ['required', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'description_th' => ['nullable', 'string', 'max:5000'],
            'description_en' => ['nullable', 'string', 'max:5000'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'sale_price' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3'],
            'transaction_type' => ['nullable', Rule::in(['sale', 'rent', 'service'])],
            'availability' => ['required', Rule::in(['available', 'reserved', 'unavailable'])],
            'duration_minutes' => ['nullable', 'integer', 'min:0'],
            'terms' => ['nullable', 'string', 'max:5000'],
            'keywords' => ['nullable', 'string', 'max:1000'],
            'location_text' => ['nullable', 'string', 'max:255'],
            'province' => ['nullable', 'string', 'max:100'],
            'district' => ['nullable', 'string', 'max:100'],
            'subdistrict' => ['nullable', 'string', 'max:100'],
            'project_name' => ['nullable', 'string', 'max:255'],
            'bedrooms' => ['nullable', 'integer', 'min:0', 'max:99'],
            'bathrooms' => ['nullable', 'integer', 'min:0', 'max:99'],
            'usable_area_sqm' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
            'land_area_sqw' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
            'floor' => ['nullable', 'integer', 'min:0', 'max:999'],
            'attributes' => ['nullable', 'array', 'max:30'],
            'attributes.*' => ['nullable', 'string', 'max:500'],
            'is_active' => ['boolean'],
            'is_published' => ['boolean'],
            'effective_from' => ['nullable', 'date'],
            'effective_until' => ['nullable', 'date', 'after_or_equal:effective_from'],
        ];
    }
}
