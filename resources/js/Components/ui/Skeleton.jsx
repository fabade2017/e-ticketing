export default function Skeleton({ rows = 3 }) {
    return (
        <div className="animate-pulse space-y-2" aria-hidden="true">
            {Array.from({ length: rows }, (_, index) => (
                <div key={index} className="h-4 bg-slate-200 rounded w-full" />
            ))}
        </div>
    );
}
