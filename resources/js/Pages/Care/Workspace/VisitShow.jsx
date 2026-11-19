import { useForm } from '@inertiajs/react';
import Layout from '../../../Components/Layout';
import PageHeader from '../../../Components/ui/PageHeader';
import StatusBadge from '../../../Components/ui/StatusBadge';

export default function VisitShow({ visit, dutyStatus }) {
    const vitals = useForm({ height_cm: '', weight_kg: '', temperature_c: '', pulse: '', bp_systolic: '', bp_diastolic: '', respiratory_rate: '', oxygen_saturation: '' });
    const diagnosis = useForm({ code: '', description: '', type: 'provisional' });
    const lab = useForm({ test_code: '', test_name: '', priority: 'routine' });
    const summary = useForm({ published_summary: '' });
    const prescription = useForm({ medication: '', dosage: '', frequency: '', duration: '', notes: '' });

    return (
        <Layout>
            <PageHeader title={visit.visit_number} subtitle={`${visit.patient?.first_name || ''} ${visit.patient?.last_name || ''} · Duty: ${dutyStatus}`} actions={<StatusBadge value={visit.status} />} />
            <div className="grid grid-cols-1 lg:grid-cols-2 gap-5 mt-5">
                <div className="bg-white border rounded-xl p-4">
                    <h2 className="font-semibold mb-3">Vitals</h2>
                    <form onSubmit={(e) => { e.preventDefault(); vitals.post(`/care/visits/${visit.id}/vitals`); }} className="grid grid-cols-2 gap-2 text-sm">
                        {Object.keys(vitals.data).map((field) => (
                            <input key={field} className="border rounded px-2 py-1" placeholder={field.replaceAll('_', ' ')} value={vitals.data[field]} onChange={(e) => vitals.setData(field, e.target.value)} />
                        ))}
                        <button className="bg-[#0b3d0b] text-white rounded px-3 py-2 col-span-2">Record vitals</button>
                    </form>
                    <div className="mt-3 text-xs text-slate-500 space-y-1">
                        {visit.vitals?.map((item) => <div key={item.id}>{item.temperature_c}°C · {item.pulse} bpm · {item.bp_systolic}/{item.bp_diastolic}</div>)}
                    </div>
                </div>
                <div className="bg-white border rounded-xl p-4">
                    <h2 className="font-semibold mb-3">Diagnosis</h2>
                    <form onSubmit={(e) => { e.preventDefault(); diagnosis.post(`/care/visits/${visit.id}/diagnoses`); }} className="space-y-2 text-sm">
                        <input className="border rounded px-2 py-1 w-full" placeholder="Code" value={diagnosis.data.code} onChange={(e) => diagnosis.setData('code', e.target.value)} />
                        <input className="border rounded px-2 py-1 w-full" placeholder="Description" value={diagnosis.data.description} onChange={(e) => diagnosis.setData('description', e.target.value)} />
                        <button className="bg-[#0b3d0b] text-white rounded px-3 py-2">Add diagnosis</button>
                    </form>
                    <div className="mt-3 text-xs text-slate-500 space-y-1">
                        {visit.diagnoses?.map((item) => <div key={item.id}>{item.code} {item.description}</div>)}
                    </div>
                </div>
                <div className="bg-white border rounded-xl p-4">
                    <h2 className="font-semibold mb-3">Lab order</h2>
                    <form onSubmit={(e) => { e.preventDefault(); lab.post(`/care/visits/${visit.id}/lab-orders`); }} className="space-y-2 text-sm">
                        <input className="border rounded px-2 py-1 w-full" placeholder="Test code" value={lab.data.test_code} onChange={(e) => lab.setData('test_code', e.target.value)} />
                        <input className="border rounded px-2 py-1 w-full" placeholder="Test name" value={lab.data.test_name} onChange={(e) => lab.setData('test_name', e.target.value)} />
                        <button className="bg-[#0b3d0b] text-white rounded px-3 py-2">Order lab test</button>
                    </form>
                </div>
                <div className="bg-white border rounded-xl p-4">
                    <h2 className="font-semibold mb-3">Prescription</h2>
                    <form onSubmit={(e) => { e.preventDefault(); prescription.post(`/care/visits/${visit.id}/prescriptions`); }} className="space-y-2 text-sm">
                        <input className="border rounded px-2 py-1 w-full" placeholder="Medication" value={prescription.data.medication} onChange={(e) => prescription.setData('medication', e.target.value)} />
                        <input className="border rounded px-2 py-1 w-full" placeholder="Dosage" value={prescription.data.dosage} onChange={(e) => prescription.setData('dosage', e.target.value)} />
                        <button className="bg-[#0b3d0b] text-white rounded px-3 py-2">Add prescription</button>
                    </form>
                </div>
            </div>
            <div className="bg-white border rounded-xl p-4 mt-5">
                <h2 className="font-semibold mb-3">Published consultation summary</h2>
                <form onSubmit={(e) => { e.preventDefault(); summary.post(`/care/visits/${visit.id}/summary`); }} className="space-y-2 text-sm">
                    <textarea className="border rounded px-2 py-1 w-full" rows="4" value={summary.data.published_summary} onChange={(e) => summary.setData('published_summary', e.target.value)} />
                    <div className="flex gap-2">
                        <button className="bg-[#0b3d0b] text-white rounded px-3 py-2">Publish summary</button>
                        <button type="button" className="border rounded px-3 py-2" onClick={() => summary.post(`/care/visits/${visit.id}/complete`)}>Complete visit</button>
                    </div>
                </form>
            </div>
        </Layout>
    );
}
