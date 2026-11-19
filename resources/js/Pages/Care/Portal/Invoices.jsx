import { useForm } from '@inertiajs/react';
import Layout from '../../../Components/Layout';
import PageHeader from '../../../Components/ui/PageHeader';
import StatusBadge from '../../../Components/ui/StatusBadge';
import EmptyState from '../../../Components/ui/EmptyState';

export default function Invoices({ invoices, paymentGateways = [], defaultGateway = 'manual_transfer' }) {
    return (
        <Layout>
            <PageHeader title="My Invoices" subtitle="Pay by an available gateway; manual transfers are verified by Accounts" />
            <div className="space-y-4 mt-5">
                {invoices.length ? invoices.map((invoice) => (
                    <div key={invoice.id} className="bg-white border rounded-xl p-4">
                        <div className="flex justify-between text-sm">
                            <div><span className="font-mono">{invoice.invoice_number}</span> · Total {invoice.total} · Paid {invoice.amount_paid}</div>
                            <StatusBadge value={invoice.status} />
                        </div>
                        <PaymentForm invoiceId={invoice.id} paymentGateways={paymentGateways} defaultGateway={defaultGateway} />
                        <div className="mt-3 text-xs text-slate-500">
                            {invoice.payments?.map((payment) => <div key={payment.id}>{payment.payment_reference} · {payment.method} · {payment.status}</div>)}
                        </div>
                    </div>
                )) : <EmptyState message="No invoices yet." />}
            </div>
        </Layout>
    );
}

function PaymentForm({ invoiceId, paymentGateways, defaultGateway }) {
    const form = useForm({ gateway: defaultGateway, amount: '', bank_reference: '', receipt: null });
    const selectedGateway = paymentGateways.find((gateway) => gateway.key === form.data.gateway);

    return (
        <form onSubmit={(e) => { e.preventDefault(); form.post(`/portal/invoices/${invoiceId}/payments`); }} className="mt-3 grid grid-cols-1 md:grid-cols-4 gap-2 text-sm">
            <select className="border rounded px-2 py-1" value={form.data.gateway} onChange={(e) => form.setData('gateway', e.target.value)}>
                {paymentGateways.map((gateway) => <option key={gateway.key} value={gateway.key}>{gateway.label}</option>)}
            </select>
            <input className="border rounded px-2 py-1" placeholder="Amount" value={form.data.amount} onChange={(e) => form.setData('amount', e.target.value)} />
            {selectedGateway?.requires_bank_reference && (
                <input className="border rounded px-2 py-1" placeholder="Bank reference" value={form.data.bank_reference} onChange={(e) => form.setData('bank_reference', e.target.value)} />
            )}
            {selectedGateway?.requires_receipt && (
                <input type="file" accept="image/jpeg,image/png,application/pdf" className="border rounded px-2 py-1" onChange={(e) => form.setData('receipt', e.target.files[0])} />
            )}
            <button className="border rounded px-3 py-1">Pay via {selectedGateway?.label || 'gateway'}</button>
        </form>
    );
}
