import { router } from '@inertiajs/react';
import { Search } from 'lucide-react';
import { useState, type FormEvent, type ReactNode } from 'react';

/**
 * Debounce-free search + filter bar. Submits a GET to the current index route,
 * preserving other query params passed via `extra`.
 */
export default function SearchBar({
    action,
    initial = '',
    placeholder = 'ค้นหา…',
    children,
}: {
    action: string;
    initial?: string;
    placeholder?: string;
    children?: ReactNode;
}) {
    const [term, setTerm] = useState(initial);

    function submit(e: FormEvent) {
        e.preventDefault();
        router.get(action, term ? { search: term } : {}, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    }

    return (
        <form onSubmit={submit} className="flex flex-wrap items-center gap-2">
            <div className="relative flex-1 sm:max-w-xs">
                <Search className="pointer-events-none absolute top-2.5 left-3 h-4 w-4 text-slate-400" />
                <input
                    type="search"
                    value={term}
                    onChange={(e) => setTerm(e.target.value)}
                    placeholder={placeholder}
                    className="w-full rounded-lg border border-slate-300 bg-white py-2 pr-3 pl-9 text-sm shadow-sm focus:border-[#2773E4] focus:ring-2 focus:ring-blue-200 focus:outline-none"
                />
            </div>
            {children}
            <button
                type="submit"
                className="rounded-lg bg-slate-800 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700"
            >
                ค้นหา
            </button>
        </form>
    );
}
