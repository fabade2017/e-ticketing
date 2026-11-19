import { Link, router } from '@inertiajs/react';
import Layout from '../../../Components/Layout';
import PageHeader from '../../../Components/ui/PageHeader';
import StatusBadge from '../../../Components/ui/StatusBadge';
import EmptyState from '../../../Components/ui/EmptyState';

export default function Appointments({ appointments, filters }) {
    return (
        <Layout>
            <PageHeader title="Care Appointments" subtitle="Requests become bookings only after Reception confirms" />
            <div className="bg-white border rounded-xl mt-5 overflow-hidden">
                <div className="p-4 border-b flex gap-2 text-sm">
                    {['', 'requested', 'confirmed', 'completed'].map((status) => (
                        <Link key={status || 'all'} href={status ? `/care/appointments?status=${status}` : '/care/appointments'} className={`px-2 py-1 rounded border ${filters.status === status ? 'bg-[#0b3d0b] text-white' : ''}`}>
                            {status || 'All'}
                        </Link>
                    ))}
                </div>
                {appointments.data.length ? (
                    <table className="w-full text-sm">
                        <thead className="bg-slate-50 text-left"><tr><th className="p-3">Number</th><th className="p-3">Patient</th><th className="p-3">Requested for</th><th className="p-3">Status</th><th className="p-3">Action</th></tr></thead>
                        <tbody>
                            {appointments.data.map((item) => (
                                <tr key={item.id} className="border-t">
                                    <td className="p-3 font-mono text-xs">{item.appointment_number}</td>
                                    <td className="p-3">{item.patient?.first_name} {item.patient?.last_name}</td>
                                    <td className="p-3">{item.requested_for}</td>
                                    <td className="p-3"><StatusBadge value={item.status} /></td>
                                    <td className="p-3">
                                        {item.status === 'requested' && (
                                            <button onClick={() => router.post(`/care/appointments/${item.id}/confirm`)} className="bg-[#0b3d0b] text-white px-2 py-1 rounded text-xs">Confirm</button>
                                        )}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                ) : <div className="p-4"><EmptyState message="No appointment requests found." /></div>}
            </div>
        </Layout>
    );
}
