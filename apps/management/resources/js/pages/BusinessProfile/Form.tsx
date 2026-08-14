import { Head, useForm } from '@inertiajs/react';
import { Save } from 'lucide-react';
import type { FormEvent } from 'react';
import AdminLayout from '@/components/AdminLayout';
import { Button, Card, Field, TextArea, TextInput } from '@/components/ui';
import { routes } from '@/lib/routes';
import type { BusinessProfile } from '@/types';

interface Props {
    profile: BusinessProfile;
}

export default function BusinessProfileForm({ profile }: Props) {
    const { data, setData, put, processing, errors } = useForm({
        business_name: profile.business_name ?? '',
        business_description: profile.business_description ?? '',
        services_offered: profile.services_offered ?? '',
        service_areas: profile.service_areas ?? '',
        business_hours: profile.business_hours ?? '',
        contact_channels: profile.contact_channels ?? '',
        conversation_tone: profile.conversation_tone ?? '',
        always_escalate_topics: profile.always_escalate_topics ?? '',
    });

    function submit(e: FormEvent) {
        e.preventDefault();
        put(routes.businessProfile.update);
    }

    return (
        <AdminLayout title="โปรไฟล์ธุรกิจ">
            <Head title="โปรไฟล์ธุรกิจ" />

            <form onSubmit={submit}>
                <Card className="mx-auto max-w-2xl space-y-4 p-6">
                    <p className="text-sm text-slate-500">
                        ข้อมูลนี้จะถูกนำไปใช้ประกอบคำตอบของ AI เป็นข้อมูลข้อเท็จจริงเท่านั้น ไม่ใช่คำสั่งควบคุม AI
                    </p>

                    <Field label="ชื่อธุรกิจ" error={errors.business_name} required>
                        <TextInput
                            value={data.business_name}
                            onChange={(e) => setData('business_name', e.target.value)}
                            error={errors.business_name}
                        />
                    </Field>

                    <Field label="รายละเอียดธุรกิจ" error={errors.business_description} hint="สรุปสั้น ๆ 1-3 ประโยคว่าธุรกิจทำอะไร" required>
                        <TextArea
                            rows={3}
                            value={data.business_description}
                            onChange={(e) => setData('business_description', e.target.value)}
                            error={errors.business_description}
                        />
                    </Field>

                    <Field label="บริการที่ให้" error={errors.services_offered} hint="เช่น ขาย/เช่า/ฝากขาย">
                        <TextArea
                            rows={3}
                            value={data.services_offered}
                            onChange={(e) => setData('services_offered', e.target.value)}
                            error={errors.services_offered}
                        />
                    </Field>

                    <Field label="ทำเลที่ให้บริการ" error={errors.service_areas}>
                        <TextArea
                            rows={3}
                            value={data.service_areas}
                            onChange={(e) => setData('service_areas', e.target.value)}
                            error={errors.service_areas}
                        />
                    </Field>

                    <Field label="เวลาทำการ" error={errors.business_hours}>
                        <TextInput
                            value={data.business_hours}
                            onChange={(e) => setData('business_hours', e.target.value)}
                            error={errors.business_hours}
                        />
                    </Field>

                    <Field label="ช่องทางติดต่อ" error={errors.contact_channels}>
                        <TextArea
                            rows={3}
                            value={data.contact_channels}
                            onChange={(e) => setData('contact_channels', e.target.value)}
                            error={errors.contact_channels}
                        />
                    </Field>

                    <Field label="โทนการสนทนา" error={errors.conversation_tone} hint="เช่น เป็นกันเอง, มืออาชีพ">
                        <TextInput
                            value={data.conversation_tone}
                            onChange={(e) => setData('conversation_tone', e.target.value)}
                            error={errors.conversation_tone}
                        />
                    </Field>

                    <Field
                        label="หัวข้อที่ต้องส่งต่อให้เจ้าหน้าที่เสมอ"
                        error={errors.always_escalate_topics}
                        hint="หัวข้อที่ AI ต้องส่งต่อให้มนุษย์เสมอ แม้จะตอบได้ก็ตาม"
                    >
                        <TextArea
                            rows={3}
                            value={data.always_escalate_topics}
                            onChange={(e) => setData('always_escalate_topics', e.target.value)}
                            error={errors.always_escalate_topics}
                        />
                    </Field>

                    <div className="flex gap-2 pt-2">
                        <Button type="submit" disabled={processing}>
                            <Save className="h-4 w-4" />
                            บันทึก
                        </Button>
                    </div>
                </Card>
            </form>
        </AdminLayout>
    );
}
