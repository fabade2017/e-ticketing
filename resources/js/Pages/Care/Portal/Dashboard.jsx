import { Link } from '@inertiajs/react';
import Layout from '../../../Components/Layout';
import PageHeader from '../../../Components/ui/PageHeader';
import StatCard from '../../../Components/ui/StatCard';

export default function Dashboard({ patient, registrations, appointments, invoices, visits, dutyStatus, canViewClinical }) {
    return (
        <Layout>
            <PageHeader title="Patient Portal" subtitle={`${patient.first_name} ${patient.last_name} · ${patient.network_number}`} />
            <div className="grid grid-cols-2 md:grid-cols-4 gap-3 mt-5">
                <StatCard label="Duty status" value={dutyStatus} />
                <StatCard label="Registrations" value={registrations.length} href="/portal/registrations" />
                <StatCard label="Appointments" value={appointments.length} href="/portal/appointments" />
                <StatCard label="Invoices" value={invoices.length} href="/portal/invoices" />
            </div>
            <div className="grid grid-cols-1 md:grid-cols-3 gap-5 mt-6">
                <div className="bg-white border rounded-xl p-4">
                    <h2 className="font-semibold mb-2">Registrations</h2>
                    {registrations.map((item) => <div key={item.id} className="text-sm border-t py-2">{item.registration_number} · {item.status}</div>)}
                </div>
                <div className="bg-white border rounded-xl p-4">
                    <h2 className="font-semibold mb-2">Appointments</h2>
                    {appointments.map((item) => <div key={item.id} className="text-sm border-t py-2">{item.appointment_number} · {item.status}</div>)}
                </div>
                <div className="bg-white border rounded-xl p-4">
                    <h2 className="font-semibold mb-2">Clinical results</h2>
                    {canViewClinical ? (
                        <Link href="/portal/results" className="text-[#0b3d0b] underline text-sm">View published results</Link>
                    ) : (
                        <p className="text-sm text-slate-500">Sponsors cannot view clinical results.</p>
                    )}
                    <div className="text-sm text-slate-500 mt-2">Published visits: {visits.length}</div>
                </div>
            </div>
        </Layout>
    );
}
