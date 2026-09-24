import Layout from '../Components/Layout';
import { Link } from '@inertiajs/react';

export default function DownloadApp() {
  const apkUrl = '/soldier-app.apk'; // place built APK at public/soldier-app.apk
  const apiUrl = 'http://10.97.186.174:8004/api';
  return (
    <Layout>
      <h1 className="text-3xl font-bold text-[#0b3d0b]">Download Soldier App</h1>
      <p className="text-slate-600 mt-2">One APK file — tap to install, share via Bluetooth/WhatsApp/USB. No Expo Go, no QR, no Metro.</p>

      <div className="mt-6 grid md:grid-cols-2 gap-6">
        <div className="bg-white border rounded-xl p-6">
          <h3 className="font-bold text-lg">1. Download APK</h3>
          <p className="text-sm text-slate-600 mt-2">Built from <code>soldier-app/</code> (Expo SDK 57 + Sanctum API at <code>{apiUrl}</code>). Works offline, queues tickets when no signal.</p>
          <a href={apkUrl} download className="inline-block mt-4 bg-[#0b3d0b] text-white px-6 py-3 rounded-xl font-bold hover:bg-black">⬇ Download NA-Soldier.apk (~40MB)</a>
          <p className="text-xs text-slate-500 mt-2">If button 404: APK not yet built — run <code>cd soldier-app && eas build --platform android --profile preview</code> then copy APK to <code>public/soldier-app.apk</code>.</p>
          <div className="mt-4 bg-slate-50 border rounded-lg p-3 text-xs">
            <b>Share:</b> After download, share file via Bluetooth / WhatsApp / USB / ShareIt — recipient taps file → Install → Allow Unknown Sources.
          </div>
          <div className="mt-4 flex gap-2">
            <Link href="/proposal" className="text-xs border px-3 py-1.5 rounded-full">Proposal Coverage →</Link>
            <Link href="/help" className="text-xs border px-3 py-1.5 rounded-full">How It Works →</Link>
          </div>
        </div>

        <div className="bg-white border rounded-xl p-6">
          <h3 className="font-bold">2. Install on Android</h3>
          <ol className="list-decimal ml-5 text-sm text-slate-700 mt-3 space-y-2">
            <li>Tap <b>Download</b> above (or scan QR below on another phone).</li>
            <li>Open file → Android asks <b>Allow install from unknown sources</b> → Allow.</li>
            <li>App installs as <b>NA E-Ticket (Soldier)</b> — icon dark green #0b3d0b.</li>
            <li>Open → Login <code>NA/SL/003</code> / <code>Soldier@123</code>. Works offline; tickets queue when no signal.</li>
            <li>To pass to another soldier: File Manager → hold <code>NA-Soldier.apk</code> → Share → Bluetooth/WhatsApp.</li>
          </ol>
          <div className="mt-4 bg-yellow-50 border border-yellow-200 rounded-lg p-3 text-xs">
            <b>No internet needed after install.</b> App talks to <code>{apiUrl}</code> when online, queues offline when in border areas. Update via new APK (OTA later via <code>eas update</code>).
          </div>
        </div>
      </div>

      <div className="mt-6 bg-white border rounded-xl p-6">
        <h3 className="font-bold">For Commander / IT</h3>
        <div className="grid md:grid-cols-3 gap-4 mt-3 text-sm">
          <div className="bg-slate-50 p-4 rounded-lg">
            <b>Build APK (once):</b>
            <pre className="bg-slate-900 text-slate-100 p-3 rounded mt-2 text-xs whitespace-pre-wrap">cd soldier-app
npx expo login # create at expo.dev (free)
eas build --platform android --profile preview
# → link to APK → download → cp to ../public/soldier-app.apk</pre>
            <p className="text-xs text-slate-500 mt-2">Preview profile at <code>eas.json:5</code> builds APK (not AAB) for sideload.</p>
          </div>
          <div className="bg-slate-50 p-4 rounded-lg">
            <b>Local build (no cloud):</b>
            <pre className="bg-slate-900 text-slate-100 p-3 rounded mt-2 text-xs whitespace-pre-wrap">npx expo prebuild
cd android && ./gradlew assembleRelease
# → android/app/build/outputs/apk/release/app-release.apk</pre>
            <p className="text-xs text-slate-500 mt-2">Needs Android Studio. Good for classified networks.</p>
          </div>
          <div className="bg-slate-50 p-4 rounded-lg">
            <b>Update soldiers later:</b>
            <pre className="bg-slate-900 text-slate-100 p-3 rounded mt-2 text-xs whitespace-pre-wrap">eas update --branch preview
# or just rebuild APK and re-share file</pre>
            <p className="text-xs text-slate-500 mt-2">No Play Store needed. MDM can push APK.</p>
          </div>
        </div>
      </div>

      <div className="mt-6 text-center text-xs text-slate-500">
        Need help? <Link href="/help" className="underline">Help</Link> • <Link href="/proposal" className="underline">Proposal Coverage</Link> • Backend: <code>{apiUrl}</code>
      </div>
    </Layout>
  );
}
