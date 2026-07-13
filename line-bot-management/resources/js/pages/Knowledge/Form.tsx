import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, Save } from 'lucide-react';
import type { FormEvent } from 'react';
import AdminLayout from '@/components/AdminLayout';
import { Button, Card, Field, SelectInput, TextArea, TextInput, Toggle } from '@/components/ui';
import { routes } from '@/lib/routes';
import type { KnowledgeEntry } from '@/types';

interface Props {
    entry: KnowledgeEntry | null;
}

const typeOptions = [
    { value: 'general', label: 'ทั่วไป' },
    { value: 'policy', label: 'นโยบาย' },
    { value: 'promotion', label: 'โปรโมชัน' },
    { value: 'procedure', label: 'ขั้นตอน' },
];

export default function KnowledgeForm({ entry }: Props) {
    const editing = Boolean(entry);
    const knownTypes = typeOptions.map((option) => option.value);
    const { data, setData, post, put, processing, errors } = useForm({
        title: entry?.title ?? '',
        type: entry?.type ?? 'general',
        category: entry?.category ?? '',
        tags: entry?.tags ?? '',
        body: entry?.body ?? '',
        source_url: entry?.source_url ?? '',
        version: entry?.version ?? 1,
        reviewed_at: entry?.reviewed_at ? entry.reviewed_at.slice(0, 10) : '',
        is_active: entry?.is_active ?? true,
    });

    function submit(e: FormEvent) {
        e.preventDefault();
        if (editing && entry) {
            put(routes.knowledge.update(entry.id));
        } else {
            post(routes.knowledge.store);
        }
    }

    return (
        <AdminLayout title={editing ? 'แก้ไขความรู้' : 'เพิ่มความรู้'}>
            <Head title={editing ? 'แก้ไขความรู้' : 'เพิ่มความรู้'} />

            <Link href={routes.knowledge.index} className="mb-4 inline-flex items-center gap-1 text-sm text-slate-500 hover:text-slate-700">
                <ArrowLeft className="h-4 w-4" />
                กลับ
            </Link>

            <form onSubmit={submit}>
                <Card className="max-w-2xl space-y-4 p-6">
                    <Field label="หัวข้อ" error={errors.title} required>
                        <TextInput value={data.title} onChange={(e) => setData('title', e.target.value)} error={errors.title} />
                    </Field>
                    <div className="grid gap-4 sm:grid-cols-2">
                        <Field label="ประเภท" error={errors.type} required>
                            <SelectInput value={data.type} onChange={(e) => setData('type', e.target.value)} error={errors.type}>
                                {!knownTypes.includes(data.type) && <option value={data.type}>{data.type}</option>}
                                {typeOptions.map((option) => (
                                    <option key={option.value} value={option.value}>
                                        {option.label}
                                    </option>
                                ))}
                            </SelectInput>
                        </Field>
                        <Field label="หมวด" error={errors.category}>
                            <TextInput value={data.category} onChange={(e) => setData('category', e.target.value)} error={errors.category} />
                        </Field>
                    </div>
                    <Field label="แท็ก" error={errors.tags} hint="คั่นด้วยจุลภาค">
                        <TextInput value={data.tags} onChange={(e) => setData('tags', e.target.value)} error={errors.tags} />
                    </Field>
                    <Field label="เนื้อหา" error={errors.body} hint="รองรับ Markdown" required>
                        <TextArea rows={10} value={data.body} onChange={(e) => setData('body', e.target.value)} error={errors.body} />
                    </Field>
                    <Field label="แหล่งอ้างอิง" error={errors.source_url} hint="แหล่งอ้างอิง">
                        <TextInput value={data.source_url} onChange={(e) => setData('source_url', e.target.value)} error={errors.source_url} placeholder="https://" />
                    </Field>
                    <div className="grid gap-4 sm:grid-cols-2">
                        <Field label="เวอร์ชัน" error={errors.version}>
                            <TextInput
                                type="number"
                                min={1}
                                value={data.version}
                                onChange={(e) => setData('version', e.target.value ? Number(e.target.value) : 1)}
                                error={errors.version}
                            />
                        </Field>
                        <Field label="ตรวจล่าสุด" error={errors.reviewed_at}>
                            <TextInput
                                type="date"
                                value={data.reviewed_at}
                                onChange={(e) => setData('reviewed_at', e.target.value)}
                                error={errors.reviewed_at}
                            />
                        </Field>
                    </div>
                    <Field label="สถานะ" error={errors.is_active}>
                        <Toggle checked={data.is_active} onChange={(value) => setData('is_active', value)} label="ใช้งาน" />
                    </Field>

                    <div className="flex gap-2 pt-2">
                        <Button type="submit" disabled={processing}>
                            <Save className="h-4 w-4" />
                            {editing ? 'บันทึก' : 'เพิ่มความรู้'}
                        </Button>
                        <Link href={routes.knowledge.index}>
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
