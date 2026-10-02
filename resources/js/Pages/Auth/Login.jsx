import { useForm, usePage, router } from '@inertiajs/react';
import { useState } from 'react';
import Layout from '../../Components/Layout';
import FaceAuth from '../../Components/FaceAuth';

export default function Login() {
    const { systemSettings, branding } = usePage().props;
    const appName = branding?.app_name || 'NA E-Ticket';
    const appMotto = branding?.app_motto || 'Secure • Offline-Capable • Nigerian Army';
    const appLogo = branding?.logo_url || (branding?.app_logo ? `/storage/${branding.app_logo}` : null);
    const mode = systemSettings?.face_auth_mode || 'local';
    const faceEnabled = systemSettings?.face_auth_enabled ?? true;
    const [faceMessage, setFaceMessage] = useState('');
    const { data, setData, post, processing, errors } = useForm({ service_number: '', email: '', password: '', remember: false });

    const submit = e => { e.preventDefault(); post('/login'); };

    const handleFaceVerify = async (embedding) => {
        if (!data.service_number) { setFaceMessage('Enter Service Number first'); return; }
        setFaceMessage('Verifying face (' + mode + ' mode)...');
        try {
            const res = await fetch('/face/verify', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' },
                body: JSON.stringify({ service_number: data.service_number, embedding }),
            });
            const json = await res.json();
            setFaceMessage(json.message + ` (similarity: ${json.similarity?.toFixed(3) || 'n/a'}, mode:${json.mode})`);
            if (res.ok) window.location.href = json.redirect || '/dashboard';
        } catch (e) { setFaceMessage('Verification error: ' + e.message); }
    };

    return (
        <Layout>
            <div className="flex flex-col items-center mt-2 mb-2">
                {appLogo ? <img src={appLogo} alt={appName} className="w-20 h-20 object-contain bg-white rounded-xl p-2 border shadow-sm" onError={e=>e.target.style.display='none'} /> : <span className="bg-[#0b3d0b] text-white w-20 h-20 flex items-center justify-center rounded-xl text-2xl font-bold border shadow-sm">NA</span>}
                <h1 className="text-2xl font-extrabold text-slate-900 mt-3">{appName}</h1>
                <p className="text-xs text-slate-500 tracking-wide">{appMotto}</p>
            </div>
            <div className="max-w-5xl mx-auto grid md:grid-cols-2 gap-8 mt-6">
                <div className="bg-white p-8 rounded-xl border shadow">
                    <h2 className="text-2xl font-bold">Personnel Login</h2>
                    <p className="text-sm text-slate-500">Service Number + Password or Face ID ({mode})</p>
                    <form onSubmit={submit} className="mt-6 space-y-4">
                        <div>
                            <label className="text-sm font-medium">Service Number</label>
                            <input value={data.service_number} onChange={e=>setData('service_number', e.target.value)} placeholder="NA/12345" className="w-full border rounded px-3 py-2 mt-1" />
                            {errors.service_number && <p className="text-red-600 text-xs">{errors.service_number}</p>}
                        </div>
                        <div>
                            <label className="text-sm font-medium">Password</label>
                            <input type="password" value={data.password} onChange={e=>setData('password', e.target.value)} className="w-full border rounded px-3 py-2 mt-1" />
                            {errors.password && <p className="text-red-600 text-xs">{errors.password}</p>}
                        </div>
                        <button disabled={processing} className="w-full bg-[#0b3d0b] text-white py-2 rounded font-semibold">Login with Password</button>
                    </form>
                    {faceMessage && <p className="text-sm mt-3 p-2 bg-slate-50 border rounded">{faceMessage}</p>}
                    <p className="text-xs text-slate-400 mt-4">Forgot password? Contact Unit Admin. Accounts require is_active=true.</p>
                </div>
                {faceEnabled ? <FaceAuth mode={mode} onVerifyResult={handleFaceVerify} /> : <div className="border rounded-xl p-6 bg-slate-50 flex items-center justify-center text-slate-500">Face authentication disabled by SuperAdmin</div>}
            </div>
        </Layout>
    );
}
