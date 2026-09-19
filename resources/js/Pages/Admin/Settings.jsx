import { useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';
import Layout from '../../Components/Layout';
import FaceAuth from '../../Components/FaceAuth';

export default function Settings({ settings }) {
    const { data, setData, post, processing } = useForm({
        face_auth_mode: settings.face_auth_mode || 'local',
        face_auth_enabled: settings.face_auth_enabled,
        cloud_provider: settings.face_cloud_provider || 'aws',
    });
    const [enrollUserId, setEnrollUserId] = useState('');
    const submit = e => { e.preventDefault(); post('/admin/settings/face'); };

    const handleEnroll = async (embedding) => {
        if (!enrollUserId) { alert('Enter User ID to enroll'); return; }
        const res = await fetch('/face/enroll', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' },
            body: JSON.stringify({ user_id: parseInt(enrollUserId), embedding }),
        });
        alert(res.ok ? 'Enrolled' : await res.text());
    };

    return (
        <Layout>
            <h1 className="text-2xl font-bold">SuperAdmin Settings</h1>
            <p className="text-sm text-slate-500">Toggle Face Auth mode globally. RBAC enforced: only SuperAdmin can change this.</p>
            <form onSubmit={submit} className="bg-white border rounded-xl p-6 mt-6 max-w-2xl space-y-4">
                <label className="flex items-center gap-2"><input type="checkbox" checked={data.face_auth_enabled} onChange={e=>setData('face_auth_enabled', e.target.checked)} /> Enable Face Authentication</label>
                <div>
                    <label className="text-sm font-medium">Face Auth Mode</label>
                    <div className="flex gap-4 mt-2">
                        <label className={`flex-1 border rounded-xl p-4 cursor-pointer ${data.face_auth_mode==='local'?'border-green-600 bg-green-50':'bg-slate-50'}`}>
                            <input type="radio" checked={data.face_auth_mode==='local'} onChange={()=>setData('face_auth_mode','local')} /> <span className="font-semibold ml-2">LOCAL</span>
                            <p className="text-xs text-slate-600 mt-1">On-Premise face-api.js • Offline-capable • Data stays in Army intranet • Recommended for border ops</p>
                        </label>
                        <label className={`flex-1 border rounded-xl p-4 cursor-pointer ${data.face_auth_mode==='cloud'?'border-blue-600 bg-blue-50':'bg-slate-50'}`}>
                            <input type="radio" checked={data.face_auth_mode==='cloud'} onChange={()=>setData('face_auth_mode','cloud')} /> <span className="font-semibold ml-2">CLOUD</span>
                            <p className="text-xs text-slate-600 mt-1">AWS Rekognition / Azure Face • Higher accuracy • Requires internet + cloud credentials</p>
                        </label>
                    </div>
                </div>
                {data.face_auth_mode==='cloud' && (
                    <div><label className="text-sm font-medium">Cloud Provider</label><select value={data.cloud_provider} onChange={e=>setData('cloud_provider', e.target.value)} className="w-full border rounded px-3 py-2 mt-1"><option value="aws">AWS Rekognition</option><option value="azure">Azure Face API</option></select></div>
                )}
                <div className="text-xs text-slate-500">Threshold: {settings.threshold} • Liveness required • Embeddings stored encrypted</div>
                <button disabled={processing} className="w-full bg-[#0b3d0b] text-white py-3 rounded font-semibold">Save Settings (SuperAdmin)</button>
            </form>

            <div className="mt-8 max-w-2xl">
                <h3 className="font-semibold">Enroll Face (Admin)</h3>
                <input value={enrollUserId} onChange={e=>setEnrollUserId(e.target.value)} placeholder="User ID (e.g. 2)" className="w-full border rounded px-3 py-2 mt-2" />
                <div className="mt-4"><FaceAuth mode={data.face_auth_mode} onEmbedding={handleEnroll} /></div>
            </div>
        </Layout>
    );
}
