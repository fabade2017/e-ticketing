import { Head, Link, usePage } from '@inertiajs/react';
import SideMenu from './SideMenu';
import ErrorBoundary from './ErrorBoundary';
import GlobalLoader from './GlobalLoader';

export default function Layout({ children }) {
    const { auth, systemSettings, branding } = usePage().props;
    const user = auth?.user;
    const roles = user?.roles || [];
    const isSuperAdmin = roles.includes('SuperAdmin');
    const appName = branding?.app_name || 'NA E-Ticket';
    const appMotto = branding?.app_motto || 'Secure • Offline-Capable';
    // Prefer full logo_url from backend (handles APP_URL + exists check), fallback to /storage path
    const appLogo = branding?.logo_url || (branding?.app_logo ? `/storage/${branding.app_logo}` : null);

    return (
        <div className="min-h-screen flex flex-col">
            <Head title={appName} />
            <GlobalLoader />
            <nav className="bg-[#0b3d0b] text-white px-6 py-3 flex justify-between items-center shadow sticky top-0 z-50">
                <Link href="/" className="font-bold tracking-wide flex items-center gap-3">
                    {appLogo ? <img src={appLogo} alt="logo" className="w-9 h-9 rounded bg-white object-contain p-1" onError={e=>e.target.style.display='none'} /> : <span className="bg-white text-[#0b3d0b] px-2 py-1 rounded text-sm">NA</span>}
                    <div className="leading-tight">
                        <div className="font-bold">{appName}</div>
                        <div className="text-[11px] opacity-70 font-normal">{appMotto}</div>
                    </div>
                    <span className="text-xs bg-yellow-500 text-black px-2 py-0.5 rounded ml-2 hidden md:inline">{systemSettings?.face_auth_mode === 'cloud' ? 'FACE:CLOUD' : 'FACE:LOCAL'}</span>
                </Link>
                <div className="flex gap-4 items-center text-sm">
                    {user ? (
                        <>
                            <Link href="/help" className="hover:underline hidden md:inline">Help</Link>
                            <Link href="/about" className="hover:underline hidden md:inline">About</Link>
                            <span className="opacity-80 hidden md:inline">{user.rank} {user.name} ({roles.join(',')})</span>
                            <Link href="/logout" method="post" as="button" className="bg-white text-[#0b3d0b] px-3 py-1 rounded">Logout</Link>
                        </>
                    ) : (
                        <Link href="/login" className="bg-white text-[#0b3d0b] px-4 py-1 rounded font-semibold">Login</Link>
                    )}
                </div>
            </nav>
            <div className="flex flex-1">
                <SideMenu />
                <main className="flex-1 max-w-6xl w-full mx-auto p-6 bg-slate-50 min-h-[calc(100vh-56px)]"><ErrorBoundary>{children}</ErrorBoundary></main>
            </div>
            <footer className="text-center text-xs text-slate-500 py-4 border-t bg-white">{appName} • {appMotto} • {new Date().getFullYear()} • <Link href="/docs" className="underline">Docs</Link> • <Link href="/help" className="underline">Help</Link></footer>
        </div>
    );
}
