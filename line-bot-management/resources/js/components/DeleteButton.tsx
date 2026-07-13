import { router } from '@inertiajs/react';
import { Trash2 } from 'lucide-react';
import { useState } from 'react';

/** Inline delete control with a lightweight confirm prompt. */
export default function DeleteButton({ url, label = 'ยืนยันการลบรายการนี้?' }: { url: string; label?: string }) {
    const [busy, setBusy] = useState(false);

    function onClick() {
        if (!window.confirm(label)) {
            return;
        }
        setBusy(true);
        router.delete(url, {
            preserveScroll: true,
            onFinish: () => setBusy(false),
        });
    }

    return (
        <button
            type="button"
            onClick={onClick}
            disabled={busy}
            title="ลบ"
            className="inline-flex items-center gap-1 rounded-md px-2 py-1 text-sm text-red-600 hover:bg-red-50 disabled:opacity-50"
        >
            <Trash2 className="h-4 w-4" />
        </button>
    );
}
