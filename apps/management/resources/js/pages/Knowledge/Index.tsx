import { Head, Link } from '@inertiajs/react';
import { Plus, Pencil } from 'lucide-react';
import AdminLayout from '@/components/AdminLayout';
import Pagination from '@/components/Pagination';
import SearchBar from '@/components/SearchBar';
import DeleteButton from '@/components/DeleteButton';
import { Badge, Button, SelectInput, Table, Th, Td, EmptyState } from '@/components/ui';
import { routes } from '@/lib/routes';
import { formatDate } from '@/lib/format';
import type { KnowledgeEntry, Paginated } from '@/types';

interface Props {
    entries: Paginated<KnowledgeEntry>;
    types: string[];
    filters: { search: string; type: string | null; is_active: string | null };
}

export default function KnowledgeIndex({ entries, types, filters }: Props) {
    return (
        <AdminLayout
            title="ความรู้"
            actions={
                <Link href={routes.knowledge.create}>
                    <Button>
                        <Plus className="h-4 w-4" />
                        เพิ่มความรู้
                    </Button>
                </Link>
            }
        >
            <Head title="ความรู้" />

            <div className="mb-4">
                <SearchBar action={routes.knowledge.index} initial={filters.search} placeholder="ค้นหาหัวข้อ / เนื้อหา">
                    <SelectInput
                        defaultValue={filters.type ?? ''}
                        name="type"
                        onChange={(e) => {
                            const type = e.target.value;
                            const url = new URL(window.location.href);
                            if (type) url.searchParams.set('type', type);
                            else url.searchParams.delete('type');
                            window.location.href = url.toString();
                        }}
                        className="sm:w-40"
                    >
                        <option value="">ทุกประเภท</option>
                        {types.map((type) => (
                            <option key={type} value={type}>
                                {type}
                            </option>
                        ))}
                    </SelectInput>
                </SearchBar>
            </div>

            {entries.data.length === 0 ? (
                <div className="rounded-xl border border-slate-200 bg-white">
                    <EmptyState message="ยังไม่มีข้อมูลความรู้" />
                </div>
            ) : (
                <Table>
                    <thead className="bg-slate-50">
                        <tr>
                            <Th>หัวข้อ</Th>
                            <Th>ประเภท</Th>
                            <Th>หมวด</Th>
                            <Th>เวอร์ชัน</Th>
                            <Th>ตรวจล่าสุด</Th>
                            <Th>สถานะ</Th>
                            <Th className="text-right">จัดการ</Th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-slate-100">
                        {entries.data.map((entry) => (
                            <tr key={entry.id} className="hover:bg-slate-50">
                                <Td>
                                    <div className="font-medium text-slate-800">{entry.title}</div>
                                </Td>
                                <Td>
                                    <span className="text-slate-600">{entry.type}</span>
                                </Td>
                                <Td>{entry.category ?? '—'}</Td>
                                <Td>{entry.version}</Td>
                                <Td>{formatDate(entry.reviewed_at)}</Td>
                                <Td>
                                    <Badge active={entry.is_active} />
                                </Td>
                                <Td className="text-right">
                                    <div className="flex items-center justify-end gap-1">
                                        <Link
                                            href={routes.knowledge.edit(entry.id)}
                                            className="inline-flex items-center gap-1 rounded-md px-2 py-1 text-sm text-slate-600 hover:bg-slate-100"
                                        >
                                            <Pencil className="h-4 w-4" />
                                        </Link>
                                        <DeleteButton url={routes.knowledge.destroy(entry.id)} />
                                    </div>
                                </Td>
                            </tr>
                        ))}
                    </tbody>
                </Table>
            )}

            <Pagination paginator={entries} />
        </AdminLayout>
    );
}
