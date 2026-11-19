export default function PageHeader({ title, subtitle = null, actions = null }) {
    return (
        <div className="flex justify-between items-start gap-4">
            <div>
                <h1 className="text-2xl font-bold">{title}</h1>
                {subtitle && <p className="text-slate-500 text-sm mt-1">{subtitle}</p>}
            </div>
            {actions && <div className="flex gap-2 shrink-0">{actions}</div>}
        </div>
    );
}
