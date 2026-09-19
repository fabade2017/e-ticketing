import { useForm } from '@inertiajs/react';
import Layout from '../../Components/Layout';

export default function Create() {
    const { data, setData, post, processing, errors } = useForm({ title: '', description: '', category: 'other', priority: 'medium', location: '' });

    const submit = e => {
        e.preventDefault();
        // offline queue: if navigator.onLine === false, store in localStorage and alert
        if (!navigator.onLine) {
            const queue = JSON.parse(localStorage.getItem('offline_tickets') || '[]');
            queue.push({ ...data, offline_created: true, created_at: new Date().toISOString() });
            localStorage.setItem('offline_tickets', JSON.stringify(queue));
            alert('Offline: ticket queued locally. Will sync when online.');
            return;
        }
        post('/tickets');
    };

    return (
        <Layout>
            <h1 className="text-2xl font-bold">Create Ticket</h1>
            <p className="text-sm text-slate-500">For border/remote areas: form queues offline and syncs automatically.</p>
            <form onSubmit={submit} className="bg-white border rounded-xl p-6 mt-6 space-y-4 max-w-2xl">
                <div><label className="text-sm font-medium">Title</label><input value={data.title} onChange={e=>setData('title', e.target.value)} className="w-full border rounded px-3 py-2 mt-1" />{errors.title && <p className="text-red-600 text-xs">{errors.title}</p>}</div>
                <div><label className="text-sm font-medium">Category</label><select value={data.category} onChange={e=>setData('category', e.target.value)} className="w-full border rounded px-3 py-2 mt-1"><option value="welfare">Welfare</option><option value="logistics">Logistics</option><option value="medical">Medical</option><option value="equipment">Equipment</option><option value="operations">Operations</option><option value="other">Other</option></select></div>
                <div><label className="text-sm font-medium">Priority</label><select value={data.priority} onChange={e=>setData('priority', e.target.value)} className="w-full border rounded px-3 py-2 mt-1"><option value="low">Low</option><option value="medium">Medium</option><option value="high">High</option><option value="urgent">Urgent</option></select></div>
                <div><label className="text-sm font-medium">Location / Outpost</label><input value={data.location} onChange={e=>setData('location', e.target.value)} placeholder="e.g. Sector 2, Borno" className="w-full border rounded px-3 py-2 mt-1" /></div>
                <div><label className="text-sm font-medium">Description</label><textarea value={data.description} onChange={e=>setData('description', e.target.value)} rows={5} className="w-full border rounded px-3 py-2 mt-1" />{errors.description && <p className="text-red-600 text-xs">{errors.description}</p>}</div>
                <button disabled={processing} className="w-full bg-[#0b3d0b] text-white py-3 rounded font-semibold">{navigator.onLine ? 'Submit Ticket' : 'Queue Offline'}</button>
            </form>
        </Layout>
    );
}
