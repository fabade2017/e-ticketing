import { useForm } from '@inertiajs/react';
import Layout from '../../../Components/Layout';
import PageHeader from '../../../Components/ui/PageHeader';
import StatusBadge from '../../../Components/ui/StatusBadge';
import EmptyState from '../../../Components/ui/EmptyState';

export default function Invoices({ invoices }) {
    return (
        <Layout>
            <PageHeader title="My Invoices" subtitle="Submit manual transfer receipts for Accounts verification" />
            <div className="space-y-4 mt-5">
                {invoices.length ? invoices.map((invoice) => (
                    <div key={invoice.id} className="bg-white border rounded-xl p-4">
                        <div className="flex justify-between text-sm">
                            <div><span className="font-mono">{invoice.invoice_number}</span> · Total {invoice.total} · Paid {invoice.amount_paid}</div>
                            <StatusBadge value={invoice.status} />
                        </div>
                        <PaymentForm invoiceId={invoice.id} />
                        <div className="mt-3 text-xs text-slate-500">
                            {invoice.payments?.map((payment) => <div key={payment.id}>{payment.payment_reference} · {payment.status}</div>)}
                        </div>
                    </div>
                )) : <EmptyState message="No invoices yet." />}
            </div>
        </Layout>
    );
}

function PaymentForm({ invoiceId }) {
    const form = useForm({ amount: '', bank_reference: '', method: 'manual_transfer', receipt: null });

    return (
        <form onSubmit={(e) => { e.preventDefault(); form.post(`/portal/invoices/${invoiceId}/payments`); }} className="mt-3 grid grid-cols-1 md:grid-cols-4 gap-2 text-sm">
            <input className="border rounded px-2 py-1" placeholder="Amount" value={form.data.amount} onChange={(e) => form.setData('amount', e.target.value)} />
            <input className="border rounded px-2 py-1" placeholder="Bank reference" value={form.data.bank_reference} onChange={(e) => form.setData('bank_reference', e.target.value)} />
            <input type="file" accept="image/jpeg,image/png,application/pdf" className="border rounded px-2 py-1" onChange={(e) => form.setData('receipt', e.target.files[0])} />
            <button className="border rounded px-3 py-1">Submit receipt</button>
        </form>
    );
}
