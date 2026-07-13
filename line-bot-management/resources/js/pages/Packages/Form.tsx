import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, Save } from 'lucide-react';
import type { FormEvent } from 'react';
import AdminLayout from '@/components/AdminLayout';
import { Button, Card, Field, SelectInput, TextArea, TextInput, Toggle } from '@/components/ui';
import { routes } from '@/lib/routes';
import type { ServicePackage } from '@/types';

interface Props {
    pkg: ServicePackage | null;
    categories: { id: number; name_th: string }[];
}

export default function PackageForm({ pkg, categories }: Props) {
    const editing = Boolean(pkg);
    const { data, setData, post, put, processing, errors } = useForm({
        category_id: pkg?.category_id ?? null,
        code: pkg?.code ?? '',
        name_th: pkg?.name_th ?? '',
        name_en: pkg?.name_en ?? '',
        description_th: pkg?.description_th ?? '',
        description_en: pkg?.description_en ?? '',
        price: pkg?.price ?? '',
        sale_price: pkg?.sale_price ?? '',
        currency: pkg?.currency ?? 'THB',
        duration_minutes: pkg?.duration_minutes ?? '',
        terms: pkg?.terms ?? '',
        keywords: pkg?.keywords ?? '',
        effective_from: pkg?.effective_from ?? '',
        effective_until: pkg?.effective_until ?? '',
        is_active: pkg?.is_active ?? true,
        is_published: pkg?.is_published ?? false,
    });

    function submit(e: FormEvent) {
        e.preventDefault();
        if (editing && pkg) {
            put(routes.packages.update(pkg.id));
        } else {
            post(routes.packages.store);
        }
    }

    return (
        <AdminLayout title={editing ? 'แก้ไขแพ็กเกจ' : 'เพิ่มแพ็กเกจ'}>
            <Head title={editing ? 'แก้ไขแพ็กเกจ' : 'เพิ่มแพ็กเกจ'} />

            <Link href={routes.packages.index} className="mb-4 inline-flex items-center gap-1 text-sm text-slate-500 hover:text-slate-700">
                <ArrowLeft className="h-4 w-4" />
                กลับ
            </Link>

            <form onSubmit={submit}>
                <Card className="max-w-3xl space-y-4 p-6">
                    <div className="grid gap-4 sm:grid-cols-2">
                        <Field label="หมวดบริการ" error={errors.category_id}>
                            <SelectInput
                                value={data.category_id ?? ''}
                                onChange={(e) => setData('category_id', e.target.value ? Number(e.target.value) : null)}
                                error={errors.category_id}
                            >
                                <option value="">— ไม่ระบุ —</option>
                                {categories.map((category) => (
                                    <option key={category.id} value={category.id}>
                                        {category.name_th}
                                    </option>
                                ))}
                            </SelectInput>
                        </Field>
                    </div>

                    <Field label="รหัสแพ็กเกจ" error={errors.code}>
                        <TextInput value={data.code} onChange={(e) => setData('code', e.target.value)} error={errors.code} />
                    </Field>

                    <div className="grid gap-4 sm:grid-cols-2">
                        <Field label="ชื่อแพ็กเกจ (ไทย)" error={errors.name_th} required>
                            <TextInput value={data.name_th} onChange={(e) => setData('name_th', e.target.value)} error={errors.name_th} />
                        </Field>
                        <Field label="ชื่อแพ็กเกจ (อังกฤษ)" error={errors.name_en}>
                            <TextInput value={data.name_en} onChange={(e) => setData('name_en', e.target.value)} error={errors.name_en} />
                        </Field>
                    </div>

                    <Field label="รายละเอียด (ไทย)" error={errors.description_th}>
                        <TextArea rows={3} value={data.description_th} onChange={(e) => setData('description_th', e.target.value)} error={errors.description_th} />
                    </Field>
                    <Field label="รายละเอียด (อังกฤษ)" error={errors.description_en}>
                        <TextArea rows={3} value={data.description_en} onChange={(e) => setData('description_en', e.target.value)} error={errors.description_en} />
                    </Field>

                    <div className="grid gap-4 sm:grid-cols-3">
                        <Field label="ราคา" error={errors.price}>
                            <TextInput
                                type="number"
                                min={0}
                                step="0.01"
                                value={data.price}
                                onChange={(e) => setData('price', e.target.value)}
                                error={errors.price}
                            />
                        </Field>
                        <Field label="ราคาโปรโมชัน" error={errors.sale_price}>
                            <TextInput
                                type="number"
                                min={0}
                                step="0.01"
                                value={data.sale_price}
                                onChange={(e) => setData('sale_price', e.target.value)}
                                error={errors.sale_price}
                            />
                        </Field>
                        <Field label="สกุลเงิน" error={errors.currency}>
                            <TextInput value={data.currency} onChange={(e) => setData('currency', e.target.value)} error={errors.currency} />
                        </Field>
                    </div>

                    <Field label="ระยะเวลา (นาที)" error={errors.duration_minutes}>
                        <TextInput
                            type="number"
                            min={0}
                            value={data.duration_minutes}
                            onChange={(e) => setData('duration_minutes', e.target.value)}
                            error={errors.duration_minutes}
                        />
                    </Field>

                    <Field label="เงื่อนไข" error={errors.terms}>
                        <TextArea rows={3} value={data.terms} onChange={(e) => setData('terms', e.target.value)} error={errors.terms} />
                    </Field>
                    <Field label="คีย์เวิร์ด" error={errors.keywords} hint="คั่นด้วยจุลภาคเพื่อช่วยการค้นหา">
                        <TextInput value={data.keywords} onChange={(e) => setData('keywords', e.target.value)} error={errors.keywords} />
                    </Field>

                    <div className="grid gap-4 sm:grid-cols-2">
                        <Field label="เริ่มมีผล" error={errors.effective_from}>
                            <TextInput
                                type="date"
                                value={data.effective_from}
                                onChange={(e) => setData('effective_from', e.target.value)}
                                error={errors.effective_from}
                            />
                        </Field>
                        <Field label="สิ้นสุดผล" error={errors.effective_until}>
                            <TextInput
                                type="date"
                                value={data.effective_until}
                                onChange={(e) => setData('effective_until', e.target.value)}
                                error={errors.effective_until}
                            />
                        </Field>
                    </div>

                    <div className="flex flex-wrap gap-6">
                        <Field label="สถานะ" error={errors.is_active}>
                            <Toggle checked={data.is_active} onChange={(value) => setData('is_active', value)} label="ใช้งาน" />
                        </Field>
                        <Field label="การเผยแพร่" error={errors.is_published}>
                            <Toggle checked={data.is_published} onChange={(value) => setData('is_published', value)} label="เผยแพร่" />
                        </Field>
                    </div>

                    <div className="flex gap-2 pt-2">
                        <Button type="submit" disabled={processing}>
                            <Save className="h-4 w-4" />
                            {editing ? 'บันทึก' : 'เพิ่มแพ็กเกจ'}
                        </Button>
                        <Link href={routes.packages.index}>
                            <Button type="button" variant="secondary">
                                ยกเลิก
                            </Button>
                        </Link>
                    </div>
                </Card>
            </form>
        </AdminLayout>
    );
}
