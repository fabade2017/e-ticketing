import { Link } from '@inertiajs/react';
import { useState } from 'react';

export default function SuiteRooms({ rooms = [], insights = [] }) {
    const [openRoom, setOpenRoom] = useState(null);

    if (rooms.length === 0) {
        return null;
    }

    return (
        <section className="mt-6">
            <div className="flex items-center justify-between">
                <div>
                    <h2 className="text-sm font-semibold uppercase tracking-wide text-slate-500">Suite rooms</h2>
                    <p className="text-xs text-slate-400 mt-1">Open a room to see the menus available to your access.</p>
                </div>
            </div>

            <div className="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 mt-3">
                {rooms.map((room) => {
                    const isOpen = openRoom === room.key;
                    const metrics = insights.find((group) => group.key === room.key)?.metrics || [];

                    return (
                        <article key={room.key} className="bg-white border rounded-xl shadow-sm overflow-hidden flex flex-col">
                            <button
                                type="button"
                                onClick={() => setOpenRoom(isOpen ? null : room.key)}
                                aria-expanded={isOpen}
                                className="w-full p-5 text-left hover:bg-slate-50 transition"
                            >
                                <div className="flex items-start gap-3">
                                    <span className="w-10 h-10 rounded-lg bg-[#0b3d0b] text-white grid place-items-center text-lg shrink-0">
                                        {room.icon}
                                    </span>
                                    <div className="min-w-0">
                                        <h3 className="font-semibold text-slate-900">{room.label}</h3>
                                        <p className="text-xs text-slate-500 mt-1">{room.description}</p>
                                    </div>
                                </div>

                                <div className="mt-4 flex items-center justify-between">
                                    <div className="flex flex-wrap gap-1.5">
                                        {metrics.slice(0, 2).map((metric) => (
                                            <span key={metric.label} className="text-[11px] bg-slate-100 text-slate-600 rounded px-2 py-1">
                                                {metric.label}: <b className="text-slate-800">{metric.value ?? 0}</b>
                                            </span>
                                        ))}
                                    </div>
                                    <span className="text-xs font-medium text-[#0b3d0b] shrink-0 ml-2">
                                        {isOpen ? 'Close' : 'Enter'} →
                                    </span>
                                </div>
                            </button>

                            {isOpen && (
                                <div className="border-t bg-slate-50 p-3 flex-1">
                                    <Link
                                        href={room.route}
                                        className="block mb-2 rounded bg-[#0b3d0b] text-white px-3 py-2 text-sm font-medium text-center hover:bg-emerald-900"
                                    >
                                        Open {room.label}
                                    </Link>
                                    <div className="space-y-1.5">
                                        {room.menus.length > 0 ? room.menus.map((menu) => (
                                            <Link
                                                key={menu.route}
                                                href={menu.route}
                                                className="flex items-center justify-between rounded border bg-white px-3 py-2 text-sm text-slate-700 hover:border-[#0b3d0b] hover:bg-green-50"
                                            >
                                                <span>{menu.label}</span>
                                                <span className="text-slate-400">→</span>
                                            </Link>
                                        )) : (
                                            <p className="text-xs text-slate-500 px-1 py-2">No additional menus for this room.</p>
                                        )}
                                    </div>
                                </div>
                            )}
                        </article>
                    );
                })}
            </div>
        </section>
    );
}
