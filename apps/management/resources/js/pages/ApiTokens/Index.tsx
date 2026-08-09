import { Head, router, useForm } from '@inertiajs/react';
import { KeyRound, Copy, Plus, Check, LockKeyhole, ShieldCheck } from 'lucide-react';
import { useState, type FormEvent } from 'react';
import AdminLayout from '@/components/AdminLayout';
import { Badge, Button, Card, Field, TextInput, Table, Th, Td, EmptyState } from '@/components/ui';
import { routes } from '@/lib/routes';

interface TokenRow {
    id: number;
    name: string;
    prefix: string;
    abilities: string[];
    is_protected: boolean;
    last_used_at: string | null;
    expires_at: string | null;
    revoked_at: string | null;
}

interface Props {
    tokens: TokenRow[];
    newToken: string | null;
}

function formatDateTime(value: string | null): string {
    if (!value) {
        return '—';
    }
    return new Date(value).toLocaleString('th-TH');
}

function tokenStatus(token: TokenRow): { label: string; active: boolean } {
    if (token.revoked_at) {
        return { label: 'เพิกถอนแล้ว', active: false };
    }
    if (token.expires_at && new Date(token.expires_at).getTime() < Date.now()) {
        return { label: 'หมดอายุ', active: false };
    }
    return { label: 'ใช้งาน', active: true };
}

export default function ApiTokensIndex({ tokens, newToken }: Props) {
    const [copied, setCopied] = useState(false);

    const { data, setData, post, processing, errors, reset } = useForm({
        name: '',
        expires_days: '',
    });

    function submit(e: FormEvent) {
        e.preventDefault();
        post(routes.apiTokens.store, {
            preserveScroll: true,
            onSuccess: () => reset(),
        });
    }

    function copyToken() {
        if (!newToken) {
            return;
        }
        navigator.clipboard.writeText(newToken).then(() => {
            setCopied(true);
            setTimeout(() => setCopied(false), 2000);
        });
    }

    function revoke(id: number) {
        if (!window.confirm('ยืนยันการเพิกถอนโทเคนนี้? การเชื่อมต่อที่ใช้โทเคนนี้จะหยุดทำงานทันที')) {
            return;
        }
        router.delete(routes.apiTokens.destroy(id), { preserveScroll: true });
    }

    return (
        <AdminLayout title="โทเคน API">
            <Head title="โทเคน API" />

            {newToken && (
                <Card className="mb-6 border-zinc-300 bg-zinc-50 p-4">
                    <div className="flex items-start gap-3">
                        <KeyRound className="mt-0.5 h-5 w-5 shrink-0 text-zinc-700" />
                        <div className="min-w-0 flex-1">
                            <p className="text-sm font-semibold text-zinc-800">โทเคนใหม่ถูกสร้างแล้ว</p>
                            <p className="mb-2 text-xs text-zinc-700">
                                คัดลอกและเก็บโทเคนนี้ไว้ทันที เพราะจะแสดงเพียงครั้งเดียวและไม่สามารถดูได้อีก
                            </p>
                            <div className="flex flex-col gap-2 sm:flex-row sm:items-center">
                                <code className="min-w-0 flex-1 overflow-x-auto rounded-md border border-zinc-200 bg-white px-3 py-2 font-mono text-sm text-slate-800">
                                    {newToken}
                                </code>
                                <Button type="button" variant="secondary" onClick={copyToken}>
                                    {copied ? <Check className="h-4 w-4" /> : <Copy className="h-4 w-4" />}
                                    {copied ? 'คัดลอกแล้ว' : 'คัดลอก'}
                                </Button>
                            </div>
                        </div>
                    </div>
                </Card>
            )}

            <Card className="mb-6 p-5 lg:p-6">
                <div className="mb-5">
                    <h2 className="text-base font-semibold text-slate-900">สร้างโทเคนใหม่</h2>
                    <p className="mt-1 text-sm text-slate-500">ใช้สำหรับเชื่อมระบบภายนอกเพื่ออ่านข้อมูลจาก Aion3 Knowledge Management</p>
                </div>
                <form onSubmit={submit} className="grid gap-4 md:grid-cols-2">
                    <Field label="ชื่อโทเคน" required error={errors.name}>
                        <TextInput
                            value={data.name}
                            onChange={(e) => setData('name', e.target.value)}
                            placeholder="เช่น chatwoot-ai-service"
                            error={errors.name}
                        />
                    </Field>
                    <Field label="อายุการใช้งาน (วัน)" hint="เว้นว่างหากไม่มีวันหมดอายุ" error={errors.expires_days}>
                        <TextInput
                            type="number"
                            min={1}
                            value={data.expires_days}
                            onChange={(e) => setData('expires_days', e.target.value)}
                            placeholder="ไม่มีกำหนด"
                            error={errors.expires_days}
                        />
                    </Field>
                    <div className="flex justify-end border-t border-slate-100 pt-4 md:col-span-2">
                        <Button type="submit" disabled={processing} className="w-full sm:w-auto">
                            <Plus className="h-4 w-4" />
                            {processing ? 'กำลังสร้าง...' : 'สร้างโทเคน'}
                        </Button>
                    </div>
                </form>
            </Card>

            <div className="mb-4 flex items-start gap-3 rounded-xl border border-zinc-200 bg-zinc-50 p-4 text-sm text-zinc-900">
                <ShieldCheck className="mt-0.5 h-5 w-5 shrink-0 text-zinc-700" />
                <p>
                    รายการที่มีป้าย <strong>โทเคนระบบ</strong> ถูกใช้งานโดย LINE Bot/agent และไม่สามารถเพิกถอนจากหน้าเว็บได้
                    เพื่อป้องกัน Bot หยุดทำงานโดยไม่ตั้งใจ
                </p>
            </div>

            {tokens.length === 0 ? (
                <div className="rounded-xl border border-slate-200 bg-white">
                    <EmptyState message="ยังไม่มีโทเคน API" />
                </div>
            ) : (
                <Table>
                    <thead className="bg-slate-50">
                        <tr>
                            <Th>ชื่อ</Th>
                            <Th>Prefix</Th>
                            <Th>สิทธิ์</Th>
                            <Th>ใช้งานล่าสุด</Th>
                            <Th>วันหมดอายุ</Th>
                            <Th>สถานะ</Th>
                            <Th className="text-right">จัดการ</Th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-slate-100">
                        {tokens.map((token) => {
                            const status = tokenStatus(token);
                            return (
                                <tr key={token.id} className="hover:bg-slate-50">
                                    <Td>
                                        <div className="font-medium text-slate-800">{token.name}</div>
                                        {token.is_protected && (
                                            <span className="mt-1 inline-flex items-center gap-1 rounded-full bg-zinc-100 px-2 py-0.5 text-xs font-medium text-zinc-800">
                                                <LockKeyhole className="h-3 w-3" />
                                                โทเคนระบบ
                                            </span>
                                        )}
                                    </Td>
                                    <Td>
                                        <code className="rounded bg-slate-100 px-1.5 py-0.5 font-mono text-xs text-slate-600">
                                            {token.prefix}
                                        </code>
                                    </Td>
                                    <Td>
                                        <span className="text-xs text-slate-600">
                                            {token.abilities.length > 0 ? token.abilities.join(', ') : '—'}
                                        </span>
                                    </Td>
                                    <Td>{formatDateTime(token.last_used_at)}</Td>
                                    <Td>{token.expires_at ? formatDateTime(token.expires_at) : 'ไม่มีกำหนด'}</Td>
                                    <Td>
                                        <Badge active={status.active} labels={[status.label, status.label]} />
                                    </Td>
                                    <Td className="text-right">
                                        {token.revoked_at ? (
                                            <span className="text-xs text-slate-400">—</span>
                                        ) : token.is_protected ? (
                                            <span className="inline-flex items-center gap-1 text-xs font-medium text-zinc-700" title="LINE Bot/agent กำลังใช้งาน">
                                                <LockKeyhole className="h-3.5 w-3.5" />
                                                ล็อกโดยระบบ
                                            </span>
                                        ) : (
                                            <button
                                                type="button"
                                                onClick={() => revoke(token.id)}
                                                title="เพิกถอน"
                                                className="inline-flex items-center gap-1 rounded-md px-2 py-1 text-sm text-red-600 hover:bg-red-50"
                                            >
                                                เพิกถอน
                                            </button>
                                        )}
                                    </Td>
                                </tr>
                            );
                        })}
                    </tbody>
                </Table>
            )}
        </AdminLayout>
    );
}
