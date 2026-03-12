import { Link } from '@inertiajs/react';

export default function Pagination({ links = [] }) {
    if (!links.length) {
        return null;
    }

    return (
        <div className="flex flex-wrap gap-2">
            {links.map((link, index) => (
                <Link
                    key={`${link.label}-${index}`}
                    href={link.url || '#'}
                    preserveScroll
                    dangerouslySetInnerHTML={{ __html: link.label }}
                    className={`rounded border px-3 py-1 text-sm ${
                        link.active
                            ? 'border-slate-900 bg-slate-900 text-white'
                            : 'border-slate-300 bg-white text-slate-700'
                    } ${!link.url ? 'pointer-events-none opacity-60' : ''}`}
                />
            ))}
        </div>
    );
}
