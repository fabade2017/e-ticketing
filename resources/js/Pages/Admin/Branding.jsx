import { useForm, usePage } from '@inertiajs/react';
import { useState, useEffect } from 'react';
import Layout from '../../Components/Layout';

export default function Branding({ branding }) {
    const { flash } = usePage().props;
    const { data, setData, post, processing, errors } = useForm({
        app_name: branding.app_name || 'NA E-Ticket',
        app_motto: branding.app_motto || 'Secure • Offline-Capable • Nigerian Army',
        logo: null,
        remove_logo: false,
    });
    const [preview, setPreview] = useState(branding.logo_url || null);
    // keep preview in sync when server returns new logo_url after save
    useEffect(() => { if (!data.logo) setPreview(branding.logo_url || null); }, [branding.logo_url]);

    const onFile = e => {
        const f = e.target.files[0];
        setData('logo', f);
        if (f) setPreview(URL.createObjectURL(f));
        else setPreview(branding.logo_url || null);
    };

    const submit = e => {
        e.preventDefault();
        post('/admin/branding', {
            forceFormData: true,
            onSuccess: () => {
                // preview will sync via useEffect on next props
                setData('logo', null);
            },
        });
    };

    return (
        <Layout>
            <h1 className="text-2xl font-bold">Branding — Logo, Name & Motto</h1>
            <p className="text-sm text-slate-600">SuperAdmin only. Changes reflect instantly in header, side menu and login. Logo max 2MB (png/jpg/svg/webp).</p>
            {flash?.success && <div className="bg-green-50 border border-green-200 text-green-800 p-3 rounded mt-4 text-sm">{flash.success}</div>}
            {flash?.error && <div className="bg-red-50 border border-red-200 text-red-800 p-3 rounded mt-4 text-sm">{flash.error}</div>}

            <div className="grid md:grid-cols-2 gap-6 mt-6">
                <form onSubmit={submit} className="bg-white border rounded-xl p-6 space-y-4">
                    <div>
                        <label className="text-sm font-medium">Application Name</label>
                        <input value={data.app_name} onChange={e=>setData('app_name', e.target.value)} placeholder="NA E-Ticket" className="w-full border rounded px-3 py-2 mt-1" />
                        {errors.app_name && <p className="text-red-600 text-xs">{errors.app_name}</p>}
                    </div>
                    <div>
                        <label className="text-sm font-medium">Motto / Tagline</label>
                        <input value={data.app_motto} onChange={e=>setData('app_motto', e.target.value)} placeholder="Secure • Offline-Capable • Nigerian Army" className="w-full border rounded px-3 py-2 mt-1" />
                        {errors.app_motto && <p className="text-red-600 text-xs">{errors.app_motto}</p>}
                    </div>
                    <div>
                        <label className="text-sm font-medium">Logo</label>
                        <input type="file" accept="image/*" onChange={onFile} className="w-full border rounded px-3 py-2 mt-1" />
                        {errors.logo && <p className="text-red-600 text-xs">{errors.logo}</p>}
                        <label className="flex items-center gap-2 mt-2 text-sm">
                            <input type="checkbox" checked={data.remove_logo} onChange={e=>setData('remove_logo', e.target.checked)} /> Remove current logo
                        </label>
                    </div>
                    <button disabled={processing} className="w-full bg-[#0b3d0b] text-white py-2 rounded font-semibold">Save Branding</button>
                    <p className="text-xs text-slate-500">Footer & login hero use motto. Header shows logo + name. The logo is stored in the database so it persists across deploys.</p>
                </form>

                <div className="bg-white border rounded-xl p-6">
                    <h3 className="font-semibold">Live Preview — Header</h3>
                    <div className="bg-[#0b3d0b] text-white px-4 py-3 flex items-center gap-3 rounded mt-3">
                        {preview ? <img src={preview} alt="logo" className="w-10 h-10 rounded bg-white object-contain p-1" onError={e=>e.target.style.display='none'} /> : <span className="bg-white text-[#0b3d0b] px-2 py-1 rounded text-sm">NA</span>}
                        <div>
                            <div className="font-bold leading-none">{data.app_name || 'NA E-Ticket'}</div>
                            <div className="text-[11px] opacity-70">{data.app_motto}</div>
                        </div>
                    </div>
                    <h3 className="font-semibold mt-6">Current Stored</h3>
                    <div className="text-sm text-slate-600 mt-2 space-y-1">
                        <div><b>Name:</b> {branding.app_name}</div>
                        <div><b>Motto:</b> {branding.app_motto}</div>
                        <div><b>Logo:</b> {branding.app_logo || '— none (using NA badge)'} </div>
                        {branding.logo_url ? <img src={branding.logo_url + (branding.logo_url.includes('?')?'&':'?') + 'v=' + Date.now()} alt="current logo" className="w-24 h-24 border rounded object-contain mt-2 bg-white p-2" onError={e=>{e.target.style.display='none'; e.target.nextSibling && (e.target.nextSibling.style.display='block');}} /> : <span className="text-xs text-slate-400">No logo uploaded yet — upload above.</span>}
                        {branding.logo_url && <div style={{display:'none'}} className="text-xs text-amber-600 mt-1">Image failed to load — try re-uploading the logo.</div>}
                    </div>
                </div>
            </div>
        </Layout>
    );
}
