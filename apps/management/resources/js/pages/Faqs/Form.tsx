import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, Save } from 'lucide-react';
import type { FormEvent } from 'react';
import AdminLayout from '@/components/AdminLayout';
import { Button, Card, Field, TextArea, TextInput, Toggle } from '@/components/ui';
import { routes } from '@/lib/routes';
import type { Faq } from '@/types';

interface Props {
    faq: Faq | null;
}

export default function FaqForm({ faq }: Props) {
    const editing = Boolean(faq);
    const { data, setData, post, put, processing, errors } = useForm({
        question_th: faq?.question_th ?? '',
        answer_th: faq?.answer_th ?? '',
        question_en: faq?.question_en ?? '',
        answer_en: faq?.answer_en ?? '',
        category: faq?.category ?? '',
        tags: faq?.tags ?? '',
        is_active: faq?.is_active ?? true,
    });

    function submit(e: FormEvent) {
        e.preventDefault();
        if (editing && faq) {
            put(routes.faqs.update(faq.id));
        } else {
            post(routes.faqs.store);
        }
    }

    return (
        <AdminLayout title={editing ? 'แก้ไขคำถาม' : 'เพิ่มคำถาม'}>
            <Head title={editing ? 'แก้ไขคำถาม' : 'เพิ่มคำถาม'} />

            <div className="mx-auto w-full max-w-5xl">
                <Link
                    href={routes.faqs.index}
                    className="mb-4 inline-flex min-h-11 items-center gap-1.5 rounded-lg px-2 text-sm font-medium text-slate-600 transition hover:bg-slate-100 hover:text-slate-900 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-zinc-600"
                >
                    <ArrowLeft className="h-4 w-4" />
                    กลับไปหน้าคำถามพบบ่อย
                </Link>

                <form onSubmit={submit}>
                    <Card className="p-6 lg:p-8">
                        <div className="grid gap-8 lg:grid-cols-2 lg:gap-10">
                            <section className="space-y-4" aria-labelledby="thai-fields-heading">
                                <div>
                                    <h2 id="thai-fields-heading" className="text-base font-semibold text-slate-900">
                                        ภาษาไทย
                                    </h2>
                                    <p className="mt-1 text-sm text-slate-600">ข้อมูลหลักที่ AI ใช้ตอบผู้ใช้งานภาษาไทย</p>
                                </div>
                                <Field label="คำถาม" error={errors.question_th} required>
                                    <TextArea
                                        rows={3}
                                        value={data.question_th}
                                        onChange={(e) => setData('question_th', e.target.value)}
                                        error={errors.question_th}
                                    />
                                </Field>
                                <Field label="คำตอบ" error={errors.answer_th} required>
                                    <TextArea
                                        rows={6}
                                        value={data.answer_th}
                                        onChange={(e) => setData('answer_th', e.target.value)}
                                        error={errors.answer_th}
                                    />
                                </Field>
                            </section>

                            <section className="space-y-4" aria-labelledby="english-fields-heading">
                                <div>
                                    <h2 id="english-fields-heading" className="text-base font-semibold text-slate-900">
                                        English <span className="font-normal text-slate-500">(ไม่บังคับ)</span>
                                    </h2>
                                    <p className="mt-1 text-sm text-slate-600">ใช้เมื่อต้องการให้ AI ตอบคำถามภาษาอังกฤษ</p>
                                </div>
                                <Field label="Question" error={errors.question_en}>
                                    <TextArea
                                        rows={3}
                                        value={data.question_en}
                                        onChange={(e) => setData('question_en', e.target.value)}
                                        error={errors.question_en}
                                    />
                                </Field>
                                <Field label="Answer" error={errors.answer_en}>
                                    <TextArea
                                        rows={6}
                                        value={data.answer_en}
                                        onChange={(e) => setData('answer_en', e.target.value)}
                                        error={errors.answer_en}
                                    />
                                </Field>
                            </section>
                        </div>

                        <section className="mt-8 border-t border-slate-200 pt-6" aria-labelledby="faq-settings-heading">
                            <div>
                                <h2 id="faq-settings-heading" className="text-base font-semibold text-slate-900">
                                    การจัดหมวดและสถานะ
                                </h2>
                                <p className="mt-1 text-sm text-slate-600">ช่วยให้ค้นหาและควบคุมว่ารายการนี้พร้อมให้ AI ใช้หรือไม่</p>
                            </div>
                            <div className="mt-4 grid gap-4 md:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_auto] md:items-start">
                                <Field label="หมวด" error={errors.category}>
                                    <TextInput
                                        value={data.category}
                                        onChange={(e) => setData('category', e.target.value)}
                                        error={errors.category}
                                    />
                                </Field>
                                <Field label="แท็ก" error={errors.tags} hint="คั่นหลายแท็กด้วยจุลภาค">
                                    <TextInput value={data.tags} onChange={(e) => setData('tags', e.target.value)} error={errors.tags} />
                                </Field>
                                <div className="md:min-w-32">
                                    <Field label="สถานะ" error={errors.is_active}>
                                        <div className="flex min-h-10 items-center">
                                            <Toggle
                                                checked={data.is_active}
                                                onChange={(value) => setData('is_active', value)}
                                                label="ใช้งาน"
                                            />
                                        </div>
                                    </Field>
                                </div>
                            </div>
                        </section>

                        <div className="mt-8 flex flex-wrap items-center gap-2 border-t border-slate-200 pt-6">
                            <Button type="submit" disabled={processing}>
                                <Save className="h-4 w-4" />
                                {editing ? 'บันทึกการแก้ไข' : 'เพิ่มคำถาม'}
                            </Button>
                            <Link
                                href={routes.faqs.index}
                                className="inline-flex min-h-10 items-center justify-center rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-zinc-600"
                            >
                                ยกเลิก
                            </Link>
                        </div>
                    </Card>
                </form>
            </div>
        </AdminLayout>
    );
}
