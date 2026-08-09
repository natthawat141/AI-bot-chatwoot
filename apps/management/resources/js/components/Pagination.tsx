import { Link } from '@inertiajs/react';
import type { Paginated } from '@/types';

/** Renders API-resource paginator links, preserving the current query string. */
export default function Pagination<T>({ paginator }: { paginator: Paginated<T> }) {
    const meta = paginator.meta;
    if (!meta || meta.last_page <= 1) {
        return null;
    }

    return (
        <nav className="mt-4 flex flex-wrap items-center justify-between gap-3 text-sm">
            <p className="text-slate-500">
                แสดง {meta.from ?? 0}–{meta.to ?? 0} จาก {meta.total} รายการ
            </p>
            <div className="flex flex-wrap gap-1">
                {meta.links.map((link, i) => {
                    const label = link.label
                        .replace('&laquo;', '‹')
                        .replace('&raquo;', '›')
                        .replace('Previous', 'ก่อนหน้า')
                        .replace('Next', 'ถัดไป');

                    if (!link.url) {
                        return (
                            <span
                                key={i}
                                className="cursor-not-allowed rounded-md px-3 py-1.5 text-slate-300"
                                dangerouslySetInnerHTML={{ __html: label }}
                            />
                        );
                    }

                    return (
                        <Link
                            key={i}
                            href={link.url}
                            preserveScroll
                            preserveState
                            className={`rounded-md px-3 py-1.5 ${
                                link.active ? 'bg-[#2773E4] text-white' : 'bg-white text-slate-600 hover:bg-slate-100'
                            }`}
                            dangerouslySetInnerHTML={{ __html: label }}
                        />
                    );
                })}
            </div>
        </nav>
    );
}
