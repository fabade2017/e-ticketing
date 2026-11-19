import { Link, router } from '@inertiajs/react';
import Layout from '../../../Components/Layout';
import PageHeader from '../../../Components/ui/PageHeader';
import StatCard from '../../../Components/ui/StatCard';
import EmptyState from '../../../Components/ui/EmptyState';

export default function Dashboard({ insights, registrations, visits, currentHospital, isAll, canSeeAll }) {
    return (
        <Layout>
            <PageHeader
                title="Care Network"
                subtitle={currentHospital ? `Workspace for ${currentHospital.name}` : 'Select a hospital to begin'}
                actions={
                    canSeeAll && (
                        <Link href={isAll ? '/care?scope=scoped' : '/care?scope=all'} className="border rounded px-3 py-2 text-sm">
                            {isAll ? 'My Scope' : 'All Hospitals'}
                        </Link>
                    )
                }
            />
            <div className="grid grid-cols-1 md:grid-cols-3 gap-3 mt-5">
                {(insights || []).map((metric) => (
                    <StatCard key={metric.label} label={metric.label} value={metric.value} href={metric.url} />
                ))}
            </div>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-5 mt-6">
                <div className="bg-white border rounded-xl overflow-hidden">
                    <div className="p-4 border-b font-semibold">Pending registrations</div>
                    {registrations.length ? (
                        registrations.map((item) => (
                            <div key={item.id} className="p-3 border-t text-sm">
                                {item.patient?.first_name} {item.patient?.last_name}
                                <span className="text-slate-400"> · {item.registration_number}</span>
                            </div>
                        ))
                    ) : <div className="p-4"><EmptyState message="No pending registrations." /></div>}
                </div>
                <div className="bg-white border rounded-xl overflow-hidden">
                    <div className="p-4 border-b font-semibold">Recent visits</div>
                    {visits.length ? (
                        visits.map((visit) => (
                            <div key={visit.id} className="p-3 border-t text-sm">
                                <Link href={`/care/visits/${visit.id}`} className="text-[#0b3d0b] underline">
                                    {visit.visit_number}
                                </Link>
                                <span className="text-slate-400"> · {visit.patient?.first_name} {visit.patient?.last_name}</span>
                            </div>
                        ))
                    ) : <div className="p-4"><EmptyState message="No visits yet." /></div>}
                </div>
            </div>
            <button
                onClick={() => router.post('/care/hospital/select', { hospital_id: currentHospital?.id || '' })}
                disabled={!currentHospital}
                className="mt-5 border rounded px-3 py-2 text-sm disabled:opacity-40"
            >
                Use current hospital
            </button>
        </Layout>
    );
}
