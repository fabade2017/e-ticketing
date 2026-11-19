import { useEffect, useRef } from 'react';

/**
 * Debounced, live filter updates for Inertia grids.
 *
 * - Skips the initial render (the controller already returned the first page).
 * - Coalesces rapid keystrokes into one visit.
 * - Returns applyNow() for Enter / filter buttons so they do not double-fire
 *   with the pending debounced visit.
 */
export default function useLiveFilters(filters, apply, delay = 300) {
    const applyRef = useRef(apply);
    applyRef.current = apply;

    const key = JSON.stringify(filters);
    const isFirst = useRef(true);
    const lastKey = useRef(null);

    const applyNow = (override) => {
        const next = override || filters;
        lastKey.current = JSON.stringify(next);
        applyRef.current(next);
    };

    useEffect(() => {
        if (isFirst.current) {
            isFirst.current = false;
            lastKey.current = key;
            return;
        }

        if (key === lastKey.current) {
            return;
        }

        const timer = setTimeout(() => {
            if (key === lastKey.current) {
                return;
            }
            lastKey.current = key;
            applyRef.current(filters);
        }, delay);

        return () => clearTimeout(timer);
    }, [key, delay]);

    return applyNow;
}
