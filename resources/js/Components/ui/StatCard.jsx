import { Link } from '@inertiajs/react';

export default function StatCard({ label, value, href = null, accent = 'border-slate-800' }) {
    const content = (
        <>
            <div className="text-xs text-slate-500 uppercase tracking-wide">{label}</div>
            <div className="text-2xl font-bold mt-1">{value ?? 0}</div>
        </>
    );

    const className = `bg-white border rounded-xl p-4 shadow-sm border-l-4 ${accent}`;

    return href ? (
        <Link href={href} className={`${className} hover:bg-slate-50`}>
            {content}
        </Link>
    ) : (
        <div className={className}>{content}</div>
    );
}
