const styles = {
    open: 'bg-amber-50 text-amber-800',
    in_progress: 'bg-blue-50 text-blue-800',
    resolved: 'bg-green-50 text-green-800',
    closed: 'bg-slate-100 text-slate-700',
    overdue: 'bg-red-50 text-red-800',
};

export default function StatusBadge({ value }) {
    const key = String(value ?? '').toLowerCase().replace(/\s+/g, '_');
    return (
        <span className={`text-xs px-2 py-0.5 rounded ${styles[key] || 'bg-slate-100 text-slate-700'}`}>
            {value || '—'}
        </span>
    );
}
