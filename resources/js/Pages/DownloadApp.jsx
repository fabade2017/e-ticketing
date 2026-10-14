import Layout from '../Components/Layout';
import { Link, usePage } from '@inertiajs/react';

export default function DownloadApp() {
  const { branding } = usePage().props;
  const apiUrl = 'https://na-e-ticketing.onrender.com/api';
  const apkUrl = branding?.app_download_url || '';
  const buildId = (apkUrl.split('/builds/')[1] || '').split(/[-?/#]/)[0];
  const qrApk = `https://api.qrserver.com/v1/create-qr-code/?size=240x240&data=${encodeURIComponent(apkUrl)}`;
  const qrPage = `https://api.qrserver.com/v1/create-qr-code/?size=240x240&data=${encodeURIComponent('https://na-e-ticketing.onrender.com/download')}`;
  return (
    <Layout>
      <h1 className="text-3xl font-bold text-[#0b3d0b]">Download Soldier App</h1>
      <p className="text-slate-600 mt-2">One APK file — tap to install, share via Bluetooth/WhatsApp/USB. No Expo Go.</p>

      <div className="mt-6 grid md:grid-cols-2 gap-6">
        <div className="bg-white border rounded-xl p-6 text-center">
          <h3 className="font-bold text-lg">1. Download APK</h3>
          <p className="text-sm text-slate-600 mt-2">Expo SDK 57 + Sanctum API at <code>{apiUrl}</code>. Works offline.</p>
          <img src={qrApk} alt="QR APK" className="mx-auto mt-4 border rounded-xl p-2 bg-white" width={240} height={240} />
          <p className="text-xs text-slate-500 mt-2">Scan QR with Camera → Download APK{buildId ? ` (Expo build ${buildId})` : ''}</p>
          <a href={apkUrl} target="_blank" rel="noopener" className="inline-block mt-4 bg-[#0b3d0b] text-white px-6 py-3 rounded-xl font-bold hover:bg-black">⬇ Download NA-Soldier.apk (~75MB)</a>
          <p className="text-xs text-slate-500 mt-2">Button opens the Expo build page → tap <b>Download</b> → 75MB. After download, share the file via Bluetooth.</p>
          <p className="text-xs text-slate-400 mt-2 break-all">Link: <code>{apkUrl}</code></p>
        </div>

        <div className="bg-white border rounded-xl p-6 text-center">
          <h3 className="font-bold">2. Share Download Page</h3>
          <p className="text-sm text-slate-600 mt-2">Let another soldier scan this QR to open this page and download.</p>
          <img src={qrPage} alt="QR Page" className="mx-auto mt-4 border rounded-xl p-2 bg-white" width={240} height={240} />
          <p className="text-xs text-slate-500 mt-2">QR → https://na-e-ticketing.onrender.com/download</p>
          <div className="mt-4 bg-yellow-50 border border-yellow-200 rounded-lg p-3 text-xs text-left">
            <b>Install:</b> Open file → Allow Unknown Sources → Install → Open → Login <code>NA/SL/003</code> / <code>Soldier@123</code>. No internet needed after install.
          </div>
        </div>
      </div>

      <div className="mt-6 bg-white border rounded-xl p-6">
        <h3 className="font-bold">For Commander / IT</h3>
        <p className="text-sm text-slate-600 mt-2">The APK link is managed by SuperAdmin under <b>Admin → Branding</b>; paste the latest Expo EAS build URL there and this page updates instantly (no code change or redeploy).</p>
        <div className="mt-3 flex gap-2">
          <Link href="/proposal" className="text-xs border px-3 py-1.5 rounded-full">Proposal Coverage →</Link>
          <Link href="/help" className="text-xs border px-3 py-1.5 rounded-full">Help →</Link>
        </div>
      </div>
    </Layout>
  );
}
