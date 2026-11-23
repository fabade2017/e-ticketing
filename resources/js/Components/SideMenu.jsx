import * as React from 'react';
import { Link, usePage } from '@inertiajs/react';
import { useState, useEffect } from 'react';

export default function SideMenu() {
    const { auth, branding, suite } = usePage().props;
    const user = auth?.user;
    const roles = user?.roles || [];
    const perms = user?.permissions || [];
    const hasPerm = (p) => perms.includes(p) || roles.includes('SuperAdmin');
    const isSuperAdmin = roles.includes('SuperAdmin');
    const isAdmin = roles.includes('Admin') || isSuperAdmin;
    const [collapsed, setCollapsed] = useState(false);

    const appLogo = branding?.logo_url || (branding?.app_logo ? `/storage/${branding.app_logo}` : null);
    const appName = branding?.app_name || 'NA E-Ticket';
    const isSoldier = roles.includes('Soldier');
    const isSupport = roles.includes('Support');
    const canViewOfficers = isSuperAdmin || isAdmin || isSupport;
    const canViewUsers = isAdmin;
    const isPrivileged = isSuperAdmin || isAdmin || isSupport;
    const [unread, setUnread] = useState(0);
    const currentHospital = suite?.current_hospital;
    const suiteUnread = suite?.unread_notifications || 0;

    useEffect(()=>{
        if (!user || !hasPerm('use chat')) return;
        let timer = null;
        const fetchUnread = async ()=>{
            if (document.hidden) return;
            try { const r = await fetch('/chat/unread', { headers:{'Accept':'application/json'}}); if (r.ok) { const j = await r.json(); setUnread(j.total || 0); } } catch(e){}
        };
        const start = ()=>{
            if (timer) return;
            fetchUnread();
            timer = setInterval(fetchUnread, 15000);
        };
        const stop = ()=>{
            if (!timer) return;
            clearInterval(timer);
            timer = null;
        };
        const onVisibility = ()=> document.hidden ? stop() : start();
        start();
        document.addEventListener('visibilitychange', onVisibility);
        return ()=> { stop(); document.removeEventListener('visibilitychange', onVisibility); };
    }, [user?.id, perms.join(',')]);

    const items = [
        { label: 'Dashboard', href: '/dashboard', icon: '▣', show: !!user, module: 'system' },
        { label: 'My Tickets', href: '/tickets', icon: '✉', show: isSoldier, module: 'ticketing' },
        { label: 'Tickets', href: '/tickets', icon: '✉', show: isPrivileged, module: 'ticketing' },
        { label: 'New Ticket', href: '/tickets/create', icon: '+', show: !!user, module: 'ticketing' },
        { label: 'Chat', href: '/chat', icon: '💬', show: hasPerm('use chat'), module: 'ticketing' },
        { label: 'Friends', href: '/friends', icon: '🤝', show: hasPerm('use friends'), module: 'ticketing' },
        { label: 'Officers & Faces', href: '/officers', icon: '◉', show: canViewOfficers, module: 'ticketing' },
        { label: 'Work Orders', href: '/maintenance', icon: '⚙', show: hasPerm('use maintenance'), module: 'maintenance' },
        { label: 'Stock Levels', href: '/supply', icon: '▤', show: hasPerm('use supply'), module: 'supply' },
        { label: 'Stock Approvals', href: '/supply/approvals', icon: '✓', show: hasPerm('approve stock adjustments'), module: 'supply' },
        { label: 'Reports', href: '/reports', icon: '📊', show: isPrivileged, module: 'ticketing' },
        { label: 'Knowledge Base', href: '/kb', icon: '📚', show: hasPerm('view knowledge base'), module: 'ticketing' },
        { label: 'Users', href: '/admin/users', icon: '👥', show: canViewUsers, badge: isSuperAdmin ? 'RBAC' : null, module: 'system' },
        { label: 'Users CRUD', href: '/admin/users/crud', icon: '👤', show: isSuperAdmin, module: 'system' },
        { label: 'Bulk Upload', href: '/admin/users/bulk', icon: '⇧', show: isSuperAdmin, module: 'system' },
        { label: 'Roles & Permissions', href: '/admin/roles', icon: '⚙', show: isSuperAdmin, module: 'system' },
        { label: 'Face Settings', href: '/admin/settings', icon: '◉', show: isSuperAdmin, module: 'system' },
        { label: 'Departments', href: '/admin/departments', icon: '🏢', show: isSuperAdmin, module: 'system' },
        { label: 'Supply Categories', href: '/admin/supply-categories', icon: '▤', show: isAdmin, module: 'system' },
        { label: 'Branding', href: '/admin/branding', icon: '🎨', show: isSuperAdmin, module: 'system' },
        { label: 'Download App', href: '/download', icon: '⬇', show: true, module: 'system' },
        { label: 'Proposal Coverage', href: '/proposal', icon: '✓', show: hasPerm('view proposal coverage'), module: 'system' },
        { label: 'Help', href: '/help', icon: '?', show: hasPerm('view help'), module: 'system' },
        { label: 'About', href: '/about', icon: 'ℹ', show: hasPerm('view about'), module: 'system' },
        { label: 'Documentation', href: '/docs', icon: '📄', show: hasPerm('view documentation'), module: 'system' },
    ];

    if (!user) return null;

    const groups = [
        ...(suite?.modules || []).map((module) => ({
            key: module.key,
            label: module.label,
            items: items.filter((item) => item.module === module.key && item.show),
        })),
        {
            key: 'system',
            label: 'Suite Administration',
            items: items.filter((item) => item.module === 'system' && item.show),
        },
    ].filter((group) => group.items.length > 0);

    return (
        <aside className={`${collapsed ? 'w-16' : 'w-64'} bg-[#0b3d0b] text-white flex flex-col shrink-0 transition-all duration-200 min-h-[calc(100vh-56px)]`}>
            <div className="p-3 flex flex-col gap-2 border-b border-white/10">
                <div className="flex justify-between items-center">
                    {!collapsed && <span className="text-xs tracking-widest opacity-70">MENU</span>}
                    {!collapsed && suiteUnread > 0 && (
                        <span className="text-[10px] bg-yellow-500 text-black px-1.5 py-0.5 rounded" title="Unread suite notifications">
                            {suiteUnread} new
                        </span>
                    )}
                    <button onClick={() => setCollapsed(!collapsed)} className="bg-white/10 hover:bg-white/20 rounded px-2 py-1 text-xs">
                        {collapsed ? '→' : '←'}
                    </button>
                </div>
                {!collapsed && (
                    <div className="flex items-center gap-2 bg-white/5 rounded p-2">
                        {appLogo ? <img src={appLogo} alt="logo" className="w-8 h-8 bg-white rounded object-contain p-1" onError={e=>e.target.style.display='none'} /> : <span className="bg-white text-[#0b3d0b] w-8 h-8 flex items-center justify-center rounded text-xs font-bold">NA</span>}
                        <div className="text-xs leading-tight">
                            <div className="font-bold">{appName}</div>
                            <div className="opacity-60 text-[11px] truncate w-32">{branding?.app_motto}</div>
                            {currentHospital && <div className="opacity-75 text-[11px] truncate w-32" title={currentHospital.name}>{currentHospital.code} · {currentHospital.name}</div>}
                        </div>
                    </div>
                )}
            </div>
            <nav className="flex-1 py-2 overflow-y-auto">
                {groups.map((group) => (
                    <div key={group.key} className="mb-2">
                        {!collapsed && <div className="px-4 pt-2 pb-1 text-[10px] uppercase tracking-widest opacity-50">{group.label}</div>}
                        {group.items.map((item) => {
                            const isChat = item.href === '/chat';
                            return (
                            <Link key={item.href+item.label} href={item.href} className={`flex items-center gap-3 px-4 py-2.5 hover:bg-white/10 text-sm ${collapsed ? 'justify-center' : ''}`}>
                                <span className="w-6 text-center relative">
                                    {item.icon}
                                    {isChat && unread > 0 && <span className="absolute -top-1 -right-1 bg-red-600 text-white text-[10px] w-4 h-4 flex items-center justify-center rounded-full">{unread>9?'9+':unread}</span>}
                                </span>
                                {!collapsed && <span className="flex-1">{item.label}</span>}
                                {!collapsed && isChat && unread > 0 && <span className="bg-red-600 text-white text-xs px-1.5 py-0.5 rounded-full">{unread}</span>}
                                {!collapsed && item.badge && <span className="text-[10px] bg-yellow-500 text-black px-1.5 py-0.5 rounded">{item.badge}</span>}
                            </Link>
                        );})}
                    </div>
                ))}
            </nav>
            {!collapsed && (
                <div className="p-4 border-t border-white/10 text-xs opacity-60">
                    <div className="font-semibold text-white">{user.rank} {user.name}</div>
                    <div>{roles.join(', ')}</div>
                    <div className="mt-1 text-[11px]">{user.service_number}</div>
                </div>
            )}
        </aside>
    );
}
