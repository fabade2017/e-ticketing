import { Link, useForm } from '@inertiajs/react';
import Layout from '../../Components/Layout';
import PageHeader from '../../Components/ui/PageHeader';

export default function Create({ equipment, faults, tickets }) {
    const form = useForm({ equipment_id: '', fault_id: '', title: '', description: '', priority: 'medium', due_at: '' });
    const fromTicket = useForm({ ticket_id: '', equipment_id: '' });

    return (
        <Layout>
            <PageHeader title="New work order" subtitle="Open corrective maintenance for equipment or a support ticket" actions={<Link href="/maintenance" className="border rounded px-3 py-2 text-sm">Back</Link>} />
            <div className="grid md:grid-cols-2 gap-4 mt-5">
                <form className="bg-white border rounded-xl p-5" onSubmit={(e) => { e.preventDefault(); form.post('/maintenance'); }}>
                    <h2 className="font-semibold">Manual work order</h2>
                    <div className="mt-3 space-y-3">
                        <select className="w-full border rounded px-2 py-2 text-sm" value={form.data.equipment_id} onChange={(e) => form.setData('equipment_id', e.target.value)}><option value="">Select equipment</option>{equipment.map((item) => <option key={item.id} value={item.id}>{item.name} ({item.asset_id})</option>)}</select>
                        <select className="w-full border rounded px-2 py-2 text-sm" value={form.data.fault_id} onChange={(e) => form.setData('fault_id', e.target.value)}><option value="">No linked fault</option>{faults.map((fault) => <option key={fault.id} value={fault.id}>{fault.code} · {fault.title}</option>)}</select>
                        <input className="w-full border rounded px-2 py-2 text-sm" placeholder="Title" value={form.data.title} onChange={(e) => form.setData('title', e.target.value)} />
                        <textarea className="w-full border rounded px-2 py-2 text-sm" rows={4} placeholder="Description" value={form.data.description} onChange={(e) => form.setData('description', e.target.value)} />
                        <select className="w-full border rounded px-2 py-2 text-sm" value={form.data.priority} onChange={(e) => form.setData('priority', e.target.value)}><option value="low">Low</option><option value="medium">Medium</option><option value="high">High</option><option value="urgent">Urgent</option></select>
                        <input className="w-full border rounded px-2 py-2 text-sm" type="date" value={form.data.due_at} onChange={(e) => form.setData('due_at', e.target.value)} />
                        {Object.values(form.errors).map((error, index) => <p key={index} className="text-red-600 text-xs">{error}</p>)}
                        <button disabled={form.processing} className="w-full bg-[#0b3d0b] text-white rounded py-2 text-sm">Create work order</button>
                    </div>
                </form>
                <form className="bg-white border rounded-xl p-5 h-fit" onSubmit={(e) => { e.preventDefault(); fromTicket.post(`/maintenance/tickets/${fromTicket.data.ticket_id}/open`, { preserveScroll: true, onSuccess: () => fromTicket.reset() }); }}>
                    <h2 className="font-semibold">Open from ticket</h2>
                    <p className="text-xs text-slate-500 mt-1">Links the ticket to a work order for audit and reporting.</p>
                    <div className="mt-3 space-y-3">
                        <select className="w-full border rounded px-2 py-2 text-sm" value={fromTicket.data.ticket_id} onChange={(e) => fromTicket.setData('ticket_id', e.target.value)}><option value="">Select ticket</option>{tickets.map((ticket) => <option key={ticket.id} value={ticket.id}>{ticket.ticket_number} · {ticket.title}</option>)}</select>
                        <select className="w-full border rounded px-2 py-2 text-sm" value={fromTicket.data.equipment_id} onChange={(e) => fromTicket.setData('equipment_id', e.target.value)}><option value="">Select equipment</option>{equipment.map((item) => <option key={item.id} value={item.id}>{item.name} ({item.asset_id})</option>)}</select>
                        {Object.values(fromTicket.errors).map((error, index) => <p key={index} className="text-red-600 text-xs">{error}</p>)}
                        <button disabled={fromTicket.processing || !fromTicket.data.ticket_id || !fromTicket.data.equipment_id} className="w-full border rounded py-2 text-sm">Open from ticket</button>
                    </div>
                </form>
            </div>
        </Layout>
    );
}
