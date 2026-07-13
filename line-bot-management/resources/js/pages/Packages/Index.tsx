import { Head, Link } from '@inertiajs/react';
import { Plus, Pencil } from 'lucide-react';
import AdminLayout from '@/components/AdminLayout';
import Pagination from '@/components/Pagination';
import SearchBar from '@/components/SearchBar';
import DeleteButton from '@/components/DeleteButton';
import { Badge, Button, SelectInput, Table, Th, Td, EmptyState } from '@/components/ui';
import { routes } from '@/lib/routes';
import { formatBaht } from '@/lib/format';
import type { ServicePackage, Paginated } from '@/types';

interface Props {
    packages: Paginated<ServicePackage>;
    categories: { id: number; name_th: string }[];
    filters: { search: string; category_id: string | null; is_published: string | null; is_active: string | null };
}

function applyFilter(name: string, value: string) {
    const url = new URL(window.location.href);
    if (value) url.searchParams.set(name, value);
    else url.searchParams.delete(name);
    window.location.href = url.toString();
}

export default function PackagesIndex({ packages, categories, filters }: Props) {
    return (
        <AdminLayout
            title="แพ็กเกจ"
            actions={
                <Link href={routes.packages.create}>
                    <Button>
                        <Plus className="h-4 w-4" />
                        เพิ่มแพ็กเกจ
                    </Button>
                </Link>
            }
        >
            <Head title="แพ็กเกจ" />

            <div className="mb-4">
                <SearchBar action={routes.packages.index} initial={filters.search} placeholder="ค้นหาชื่อ / รหัส / คีย์เวิร์ด">
                    <SelectInput
                        defaultValue={filters.category_id ?? ''}
                        name="category_id"
                        onChange={(e) => applyFilter('category_id', e.target.value)}
                        className="sm:w-44"
                    >
                        <option value="">ทุกหมวด</option>
                        {categories.map((category) => (
                            <option key={category.id} value={category.id}>
                                {category.name_th}
                            </option>
                        ))}
                    </SelectInput>
                    <SelectInput
                        defaultValue={filters.is_published ?? ''}
                        name="is_published"
                        onChange={(e) => applyFilter('is_published', e.target.value)}
                        className="sm:w-40"
                    >
                        <option value="">ทั้งหมด</option>
                        <option value="1">เผยแพร่</option>
                        <option value="0">ฉบับร่าง</option>
                    </SelectInput>
                </SearchBar>
            </div>

            {packages.data.length === 0 ? (
                <div className="rounded-xl border border-slate-200 bg-white">
                    <EmptyState message="ยังไม่มีข้อมูลแพ็กเกจ" />
                </div>
            ) : (
                <Table>
                    <thead className="bg-slate-50">
                        <tr>
                            <Th>ชื่อแพ็กเกจ</Th>
                            <Th>หมวด</Th>
                            <Th>ราคา</Th>
                            <Th>เผยแพร่</Th>
                            <Th>สถานะ</Th>
                            <Th className="text-right">จัดการ</Th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-slate-100">
                        {packages.data.map((pkg) => (
                            <tr key={pkg.id} className="hover:bg-slate-50">
                                <Td>
                                    <div className="font-medium text-slate-800">{pkg.name_th}</div>
                                    {pkg.code && <div className="text-xs text-slate-500">{pkg.code}</div>}
                                </Td>
                                <Td>{pkg.category?.name_th ?? '—'}</Td>
                                <Td>
                                    {pkg.sale_price !== null ? (
                                        <div className="flex flex-col">
                                            <span className="font-medium text-slate-800">{formatBaht(pkg.sale_price)}</span>
                                            <span className="text-xs text-slate-400 line-through">{formatBaht(pkg.price)}</span>
                                        </div>
                                    ) : (
                                        <span>{formatBaht(pkg.price)}</span>
                                    )}
                                </Td>
                                <Td>
                                    <Badge active={pkg.is_published} labels={['เผยแพร่', 'ฉบับร่าง']} />
                                </Td>
                                <Td>
                                    <Badge active={pkg.is_active} />
                                </Td>
                                <Td className="text-right">
                                    <div className="flex items-center justify-end gap-1">
                                        <Link
                                            href={routes.packages.edit(pkg.id)}
                                            className="inline-flex items-center gap-1 rounded-md px-2 py-1 text-sm text-slate-600 hover:bg-slate-100"
                                        >
                                            <Pencil className="h-4 w-4" />
                                        </Link>
                                        <DeleteButton url={routes.packages.destroy(pkg.id)} />
                                    </div>
                                </Td>
                            </tr>
                        ))}
                    </tbody>
                </Table>
            )}

            <Pagination paginator={packages} />
        </AdminLayout>
    );
}
