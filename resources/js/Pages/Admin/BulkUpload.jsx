import { useForm, Link, usePage } from '@inertiajs/react';
import Layout from '../../Components/Layout';

export default function BulkUpload({ roles, departments, templateHeaders }) {
    const { flash } = usePage().props;
    const { data, setData, post, processing, errors } = useForm({ file: null });

    const submit = e => {
        e.preventDefault();
        post('/admin/users/bulk', { forceFormData: true });
    };

    return (
        <Layout>
            <h1 className="text-2xl font-bold">Bulk Upload Users</h1>
            <p className="text-sm text-slate-600">SuperAdmin only — CSV with headers: <code>{templateHeaders.join(', ')}</code>. Generates import report + links to <Link href="/docs" className="underline">Documentation</Link>.</p>

            {flash?.success && <div className="bg-green-50 border border-green-200 text-green-800 p-3 rounded mt-4 text-sm">{flash.success}</div>}
            <div className="bg-white border rounded-xl p-4 mt-4 text-sm">
                <div className="font-semibold">Bulk format</div>
                <p className="text-xs text-slate-600">Accepts <b>CSV</b> and <b>Excel (.xlsx/.xls)</b>. Roles are dropdown in Excel; department names must match existing departments.</p>
                <div className="flex gap-2 mt-2">
                    <span className="text-xs bg-slate-100 px-2 py-1 rounded">Roles: {roles.join(', ')}</span>
                    <span className="text-xs bg-slate-100 px-2 py-1 rounded">Depts: {Object.values(departments).join(', ')}</span>
                </div>
            </div>
            {flash?.bulkResult && (
                <div className="bg-white border p-4 rounded mt-4 text-sm">
                    <div>Created: {flash.bulkResult.created} — Skipped: {flash.bulkResult.skipped}</div>
                    {flash.bulkResult.errors?.length > 0 && <ul className="list-disc ml-5 mt-2 text-red-600">{flash.bulkResult.errors.map((e,i)=><li key={i}>{e}</li>)}</ul>}
                </div>
            )}

            <div className="bg-white border rounded-xl p-6 mt-6 max-w-3xl">
                <form onSubmit={submit} className="space-y-4">
                    <div>
                        <label className="text-sm font-medium">CSV File</label>
                        <input type="file" accept=".csv" onChange={e=>setData('file', e.target.files[0])} className="w-full border rounded px-3 py-2 mt-1" />
                        {errors.file && <p className="text-red-600 text-xs">{errors.file}</p>}
                    </div>
                    <button disabled={processing} className="w-full bg-[#0b3d0b] text-white py-2 rounded font-semibold">Upload & Import</button>
                </form>
                <div className="mt-6 border-t pt-4 text-sm">
                    <div className="flex justify-between items-center">
                        <span>Need template?</span>
                        <div className="flex gap-2">
                            <a href="/admin/users/bulk/template" className="bg-slate-800 text-white px-4 py-1 rounded text-sm">CSV</a>
                            <a href="/admin/users/bulk/template?format=xlsx" className="bg-[#0b3d0b] text-white px-4 py-1 rounded text-sm">Excel (roles dropdown)</a>
                        </div>
                    </div>
                    <pre className="bg-slate-900 text-slate-100 p-3 rounded mt-3 text-xs overflow-auto">{`name,email,service_number,rank,unit,department,phone,role
Soldier Two,soldier2@army.mil.ng,NA/SL/004,Pte,Outpost B,Logistics,08012345678,Soldier
Support One,support@army.mil.ng,NA/SP/005,Sgt,HQ,Medical,08087654321,Support`}</pre>
                    <p className="text-xs text-slate-500 mt-2">Default password for imported users: <code>Password@123</code> — force change on first login. About this project: <Link href="/about" className="underline">About</Link> • Full spec: <Link href="/docs" className="underline">/docs</Link></p>
                </div>
            </div>
        </Layout>
    );
}
