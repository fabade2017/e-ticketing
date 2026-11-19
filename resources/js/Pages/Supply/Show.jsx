import { Link } from '@inertiajs/react';
import Layout from '../../Components/Layout';
import PageHeader from '../../Components/ui/PageHeader';
import EmptyState from '../../Components/ui/EmptyState';

export default function Show({ item, balances, batches }) {
    return (
        <Layout>
            <PageHeader title={item.name} subtitle={`${item.sku} · ${item.unit}`} actions={<Link href="/supply" className="border rounded px-3 py-2 text-sm">Back to supply</Link>} />
            <div className="grid md:grid-cols-2 gap-4 mt-5">
                <div className="bg-white border rounded-xl p-4">
                    <h2 className="font-semibold">Stock by hospital</h2>
                    {balances.length ? (
                        <table className="w-full text-sm mt-3"><thead className="text-left text-slate-500"><tr><th className="py-2">Hospital</th><th className="py-2">Store</th><th className="py-2">On hand</th></tr></thead><tbody>
                            {balances.map((balance) => <tr key={balance.id} className="border-t"><td className="py-2">{balance.hospital?.name || balance.hospital_id}</td><td className="py-2">{balance.store}</td><td className="py-2 font-semibold">{balance.quantity_on_hand}</td></tr>)}
                        </tbody></table>
                    ) : <div className="mt-3"><EmptyState message="No stock balance recorded." /></div>}
                </div>
                <div className="bg-white border rounded-xl p-4">
                    <h2 className="font-semibold">Batches</h2>
                    {batches.length ? (
                        <table className="w-full text-sm mt-3"><thead className="text-left text-slate-500"><tr><th className="py-2">Batch</th><th className="py-2">Qty</th><th className="py-2">Expiry</th><th className="py-2">Damaged</th></tr></thead><tbody>
                            {batches.map((batch) => <tr key={batch.id} className="border-t"><td className="py-2">{batch.batch_code}</td><td className="py-2">{batch.quantity}</td><td className="py-2">{batch.expires_on || '—'}</td><td className="py-2">{batch.is_damaged ? 'Yes' : 'No'}</td></tr>)}
                        </tbody></table>
                    ) : <div className="mt-3"><EmptyState message="No batches recorded." /></div>}
                </div>
            </div>
        </Layout>
    );
}
