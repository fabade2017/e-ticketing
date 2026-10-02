import { useEffect, useState, useRef } from 'react';
import { router } from '@inertiajs/react';

export default function GlobalLoader() {
    const [visible, setVisible] = useState(false);
    const [message, setMessage] = useState('Processing, please wait...');
    const pendingFetches = useRef(0);
    const inertiaPending = useRef(false);
    const timerRef = useRef(null);

    const show = (msg) => {
        if (msg) setMessage(msg);
        setVisible(true);
    };
    const tryHide = () => {
        if (pendingFetches.current === 0 && !inertiaPending.current) {
            // small delay to avoid flicker
            clearTimeout(timerRef.current);
            timerRef.current = setTimeout(() => {
                if (pendingFetches.current === 0 && !inertiaPending.current) setVisible(false);
            }, 150);
        }
    };

    useEffect(() => {
        // Inertia router events – blocks navigation/form submits
        const onStart = (e) => {
            inertiaPending.current = true;
            const detail = e?.detail?.visit?.data || null;
            show('Processing, please wait…');
        };
        const onFinish = () => {
            inertiaPending.current = false;
            tryHide();
        };
        const onError = () => {
            inertiaPending.current = false;
            tryHide();
        };

        // router.on returns unsubscribe in newer versions; fallback to addEventListener
        let unsubs = [];
        try {
            if (router.on) {
                unsubs.push(router.on('start', onStart));
                unsubs.push(router.on('finish', onFinish));
                unsubs.push(router.on('error', onError));
                unsubs.push(router.on('invalid', onError));
            }
        } catch {}
        // Also listen to Inertia progress via document events (fallback)
        document.addEventListener('inertia:start', onStart);
        document.addEventListener('inertia:finish', onFinish);
        document.addEventListener('inertia:error', onError);

        // Fetch patch – catches direct fetch() like /ai/ask, /face/verify
        // Silent fetches (polling) are ignored via URL allowlist or X-Silent header
        const SILENT_URLS = ['/chat/unread', '/chat/groups', '/branding', '/admin/departments/users/search'];
        const isSilentFetch = (args) => {
            try {
                const url = typeof args[0] === 'string' ? args[0] : args[0]?.url || '';
                const opts = args[1] || {};
                const headers = opts.headers || {};
                const hasSilentHeader = headers['X-Silent'] === '1' || headers['x-silent'] === '1';
                if (hasSilentHeader) return true;
                return SILENT_URLS.some(u => url.includes(u));
            } catch { return false; }
        };
        const origFetch = window.fetch;
        window.fetch = async (...args) => {
            if (isSilentFetch(args)) return origFetch(...args);
            pendingFetches.current += 1;
            show('Processing, please wait…');
            try {
                const res = await origFetch(...args);
                return res;
            } finally {
                pendingFetches.current = Math.max(0, pendingFetches.current - 1);
                tryHide();
            }
        };

        // Axios-like: patch XMLHttpRequest for any raw XHR (optional)
        // Expose global helper for manual trigger
        window.__showLoader = (msg) => { pendingFetches.current += 1; show(msg || 'Processing, please wait…'); };
        window.__hideLoader = () => { pendingFetches.current = Math.max(0, pendingFetches.current - 1); tryHide(); };

        return () => {
            unsubs.forEach(fn => { try { typeof fn === 'function' ? fn() : router.off && router.off('start', onStart); } catch {} });
            document.removeEventListener('inertia:start', onStart);
            document.removeEventListener('inertia:finish', onFinish);
            document.removeEventListener('inertia:error', onError);
            window.fetch = origFetch;
            delete window.__showLoader;
            delete window.__hideLoader;
            clearTimeout(timerRef.current);
        };
    }, []);

    if (!visible) return null;

    return (
        <div className="fixed inset-0 z-[9999] flex items-center justify-center bg-black/40 backdrop-blur-[2px]" style={{ pointerEvents: 'auto' }} aria-busy="true" aria-live="polite">
            <div className="bg-white rounded-2xl shadow-2xl px-8 py-6 flex flex-col items-center gap-4 min-w-[260px] border border-slate-200">
                <div className="relative w-12 h-12">
                    <div className="absolute inset-0 rounded-full border-4 border-slate-200"></div>
                    <div className="absolute inset-0 rounded-full border-4 border-[#0b3d0b] border-t-transparent animate-spin"></div>
                    <div className="absolute inset-2 rounded-full bg-[#0b3d0b]/10"></div>
                </div>
                <div className="text-center">
                    <div className="font-bold text-slate-800 text-sm">{message}</div>
                    <div className="text-xs text-slate-500 mt-1">Please wait — do not close or refresh</div>
                </div>
                <div className="flex gap-1 mt-1">
                    <span className="w-2 h-2 bg-[#0b3d0b] rounded-full animate-bounce" style={{ animationDelay: '0ms' }}></span>
                    <span className="w-2 h-2 bg-[#0b3d0b] rounded-full animate-bounce" style={{ animationDelay: '150ms' }}></span>
                    <span className="w-2 h-2 bg-[#0b3d0b] rounded-full animate-bounce" style={{ animationDelay: '300ms' }}></span>
                </div>
            </div>
        </div>
    );
}
