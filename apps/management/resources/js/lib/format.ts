/** Thai-locale display helpers shared across admin tables and the public page. */

export function formatBaht(value: number | null | undefined): string {
    if (value === null || value === undefined) {
        return '—';
    }

    return new Intl.NumberFormat('th-TH', {
        style: 'currency',
        currency: 'THB',
        maximumFractionDigits: 0,
    }).format(value);
}

export function formatDate(value: string | null | undefined): string {
    if (!value) {
        return '—';
    }

    const date = new Date(value);
    if (Number.isNaN(date.getTime())) {
        return value;
    }

    return new Intl.DateTimeFormat('th-TH', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    }).format(date);
}

export function truncate(value: string | null | undefined, length = 80): string {
    if (!value) {
        return '';
    }
    return value.length > length ? `${value.slice(0, length)}…` : value;
}
