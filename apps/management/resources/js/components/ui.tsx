import type { ReactNode, InputHTMLAttributes, TextareaHTMLAttributes, SelectHTMLAttributes } from 'react';

/** Compact, reusable form + table primitives shared by every admin CRUD screen. */

const labelBase = 'mb-1 block text-sm font-medium text-slate-700';
const controlBase =
    'block min-h-10 w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm placeholder:text-slate-400 focus:border-zinc-400 focus:ring-2 focus:ring-zinc-700 focus:outline-none disabled:bg-slate-100';

export function Field({
    label,
    error,
    required,
    hint,
    children,
}: {
    label: string;
    error?: string;
    required?: boolean;
    hint?: string;
    children: ReactNode;
}) {
    return (
        <div>
            <label className={labelBase}>
                {label}
                {required && <span className="ml-0.5 text-red-500">*</span>}
            </label>
            {children}
            {hint && !error && <p className="mt-1 text-xs text-slate-500">{hint}</p>}
            {error && <p className="mt-1 text-xs text-red-600">{error}</p>}
        </div>
    );
}

export function TextInput({ error, ...props }: InputHTMLAttributes<HTMLInputElement> & { error?: string }) {
    return <input {...props} className={`${controlBase} ${error ? 'border-red-400' : ''}`} />;
}

export function TextArea({ error, ...props }: TextareaHTMLAttributes<HTMLTextAreaElement> & { error?: string }) {
    return <textarea {...props} className={`${controlBase} ${error ? 'border-red-400' : ''}`} />;
}

export function SelectInput({
    error,
    children,
    ...props
}: SelectHTMLAttributes<HTMLSelectElement> & { error?: string }) {
    return (
        <select {...props} className={`${controlBase} ${error ? 'border-red-400' : ''}`}>
            {children}
        </select>
    );
}

export function Toggle({
    checked,
    onChange,
    label,
}: {
    checked: boolean;
    onChange: (value: boolean) => void;
    label: string;
}) {
    return (
        <label className="inline-flex cursor-pointer items-center gap-2">
            <input
                type="checkbox"
                checked={checked}
                onChange={(e) => onChange(e.target.checked)}
                className="h-4 w-4 rounded border-slate-300 accent-zinc-900 focus:ring-zinc-400"
            />
            <span className="text-sm text-slate-700">{label}</span>
        </label>
    );
}

type Variant = 'primary' | 'secondary' | 'danger' | 'ghost';

const variants: Record<Variant, string> = {
    primary: 'bg-zinc-900 text-zinc-50 hover:bg-zinc-700 focus:ring-zinc-400',
    secondary: 'border border-slate-300 bg-white text-slate-700 hover:bg-slate-50 focus:ring-slate-200',
    danger: 'bg-red-600 text-white hover:bg-red-700 focus:ring-red-300',
    ghost: 'bg-transparent text-slate-600 hover:bg-slate-100 focus:ring-slate-200',
};

export function Button({
    variant = 'primary',
    className = '',
    children,
    ...props
}: React.ButtonHTMLAttributes<HTMLButtonElement> & { variant?: Variant }) {
    return (
        <button
            {...props}
            className={`inline-flex min-h-10 items-center justify-center gap-1.5 rounded-md px-3.5 py-2 text-sm font-medium transition focus:ring-2 focus:outline-none disabled:cursor-not-allowed disabled:opacity-60 ${variants[variant]} ${className}`}
        >
            {children}
        </button>
    );
}

export function Badge({ active, labels }: { active: boolean; labels?: [string, string] }) {
    const [on, off] = labels ?? ['ใช้งาน', 'ปิด'];
    return (
        <span
            className={`inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium ${
                active ? 'bg-zinc-100 text-zinc-800' : 'bg-slate-100 text-slate-600'
            }`}
        >
            {active ? on : off}
        </span>
    );
}

export function Card({ children, className = '' }: { children: ReactNode; className?: string }) {
    return <div className={`rounded-lg border border-slate-200 bg-white ${className}`}>{children}</div>;
}

export function Table({ children }: { children: ReactNode }) {
    return (
        <div className="overflow-x-auto rounded-lg border border-slate-200 bg-white">
            <table className="min-w-full divide-y divide-slate-200 text-sm">{children}</table>
        </div>
    );
}

export function Th({ children, className = '' }: { children?: ReactNode; className?: string }) {
    return (
        <th className={`px-4 py-3 text-left text-xs font-semibold tracking-wide text-slate-500 uppercase ${className}`}>
            {children}
        </th>
    );
}

export function Td({ children, className = '' }: { children?: ReactNode; className?: string }) {
    return <td className={`px-4 py-3 align-middle text-slate-700 ${className}`}>{children}</td>;
}

export function EmptyState({ message }: { message: string }) {
    return <div className="px-4 py-12 text-center text-sm text-slate-500">{message}</div>;
}
