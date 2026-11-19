export default function EmptyState({ message = 'No records available.' }) {
    return (
        <div className="border border-dashed rounded-lg p-6 text-center text-sm text-slate-500">
            {message}
        </div>
    );
}
