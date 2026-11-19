import { useForm } from '@inertiajs/react';
import Layout from '../../../Components/Layout';
import PageHeader from '../../../Components/ui/PageHeader';
import StatusBadge from '../../../Components/ui/StatusBadge';
import EmptyState from '../../../Components/ui/EmptyState';

export default function Invoices({ invoices }) {
    const invoice = useForm({ source_type: 'registration', source_id: '', total: '' });

    return (
        <Layout>
            <PageHeader title="Care Billing" subtitle="Manual transfer receipts are verified by Accounts before payment counts" />
            <form onSubmit={(e) => { e.preventDefault(); invoice.post('/care/invoices'); }} className="bg-white border rounded-xl p-4 mt-5 grid grid-cols-1 md:grid-cols-4 gap-3 text-sm">
                <select className="border rounded px-3 py-2" value={invoice.data.source_type} onChange={(e) => invoice.setData('source_type', e.target.value)}>
                    <option value="registration">Registration</option>
                    <option value="visit">Visit</option>
                </select>
                <input className="border rounded px-3 py-2" placeholder="Source ID" value={invoice.data.source_id} onChange={(e) => invoice.setData('source_id', e.target.value)} />
                <input className="border rounded px-3 py-2" placeholder="Total (optional)" value={invoice.data.total} onChange={(e) => invoice.setData('total', e.target.value)} />
                <button className="bg-[#0b3d0b] text-white rounded px-3 py-2">Create invoice</button>
            </form>
            <div className="space-y-4 mt-5">
                {invoices.data.length ? invoices.data.map((item) => (
                    <div key={item.id} className="bg-white border rounded-xl p-4">
                        <div className="flex justify-between text-sm">
                            <div><span className="font-mono">{item.invoice_number}</span> · {item.patient?.first_name} {item.patient?.last_name}</div>
                            <StatusBadge value={item.status} />
                        </div>
                        <div className="text-xs text-slate-500 mt-1">Total: {item.total} · Paid: {item.amount_paid}</div>
                        <PaymentForm invoiceId={item.id} />
                        <div className="mt-3 space-y-2">
                            {item.payments?.map((payment) => <VerifyForm key={payment.id} payment={payment} />)}
                        </div>
                    </div>
                )) : <EmptyState message="No invoices found." />}
            </div>
        </Layout>
    );
}

function PaymentForm({ invoiceId }) {
    const form = useForm({ amount: '', bank_reference: '', method: 'manual_transfer', receipt: null });

    return (
        <form onSubmit={(e) => { e.preventDefault(); form.post(`/care/invoices/${invoiceId}/payments`); }} className="mt-3 grid grid-cols-1 md:grid-cols-4 gap-2 text-sm">
            <input className="border rounded px-2 py-1" placeholder="Amount" value={form.data.amount} onChange={(e) => form.setData('amount', e.target.value)} />
            <input className="border rounded px-2 py-1" placeholder="Bank reference" value={form.data.bank_reference} onChange={(e) => form.setData('bank_reference', e.target.value)} />
            <input type="file" accept="image/jpeg,image/png,application/pdf" className="border rounded px-2 py-1" onChange={(e) => form.setData('receipt', e.target.files[0])} />
            <button className="border rounded px-3 py-1">Submit payment</button>
        </form>
    );
}

function VerifyForm({ payment }) {
    const form = useForm({ bank_reference: payment.bank_reference || '', credited: true });

    if (payment.status === 'verified') {
        return <div className="text-xs text-green-700">Payment {payment.payment_reference} verified.</div>;
    }

    return (
        <form onSubmit={(e) => { e.preventDefault(); form.post(`/care/payments/${payment.id}/verify`); }} className="flex gap-2 text-xs items-center">
            <span className="font-mono">{payment.payment_reference}</span>
            <input className="border rounded px-2 py-1" value={form.data.bank_reference} onChange={(e) => form.setData('bank_reference', e.target.value)} />
            <label className="flex items-center gap-1">
                <input type="checkbox" checked={form.data.credited} onChange={(e) => form.setData('credited', e.target.checked)} />
                Credited
            </label>
            <button className="bg-[#0b3d0b] text-white rounded px-2 py-1">Verify</button>
        </form>
    );
}
