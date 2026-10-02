import * as React from 'react';
import { Link, usePage } from '@inertiajs/react';
import { useState, useEffect } from 'react';

export default function SideMenu() {
    const { auth } = usePage().props;
    const user = auth?.user;
    const roles = user?.roles || [];
    const perms = user?.permissions || [];
    const hasPerm = (p) => perms.includes(p) || roles.includes('SuperAdmin');
    const isSuperAdmin = roles.includes('SuperAdmin');
    const isAdmin = roles.includes('Admin') || isSuperAdmin;
    const [collapsed, setCollapsed] = useState(false);

    const { branding } = usePage().props;
    const appLogo = branding?.app_logo ? `/storage/${branding.app_logo}` : null;
    const appName = branding?.app_name || 'NA E-Ticket';
    const isSoldier = roles.includes('Soldier');
    const isSupport = roles.includes('Support');
    const canViewOfficers = isSuperAdmin || isAdmin || isSupport;
    const canViewUsers = isAdmin;
    const isPrivileged = isSuperAdmin || isAdmin || isSupport;
    const [unread, setUnread] = useState(0);

    useEffect(()=>{
        if (!user) return;
        const fetchUnread = async ()=>{
            try { const r = await fetch('/chat/unread', { headers:{'Accept':'application/json'}}); if (r.ok) { const j = await r.json(); setUnread(j.total || 0); } } catch(e){}
        };
        fetchUnread();
        const id = setInterval(fetchUnread, 5000);
        return ()=> clearInterval(id);
    }, [user?.id]);

    const items = [
        { label: 'Dashboard', href: '/dashboard', icon: '▣', show: !!user },
        { label: 'My Tickets', href: '/tickets', icon: '✉', show: isSoldier },
        { label: 'Tickets', href: '/tickets', icon: '✉', show: isPrivileged },
        { label: 'New Ticket', href: '/tickets/create', icon: '+', show: !!user },
        { label: 'Chat', href: '/chat', icon: '💬', show: !!user },
        { label: 'Friends', href: '/friends', icon: '🤝', show: !!user },
        { label: 'Officers & Faces', href: '/officers', icon: '◉', show: canViewOfficers },
        { label: 'Users', href: '/admin/users', icon: '👥', show: canViewUsers, badge: isSuperAdmin ? 'RBAC' : null },
        { label: 'Users CRUD', href: '/admin/users/crud', icon: '👤', show: isSuperAdmin },
        { label: 'Bulk Upload', href: '/admin/users/bulk', icon: '⇧', show: isSuperAdmin },
        { label: 'Roles & Permissions', href: '/admin/roles', icon: '⚙', show: isSuperAdmin },
        { label: 'Face Settings', href: '/admin/settings', icon: '◉', show: isSuperAdmin },
        { label: 'Departments', href: '/admin/departments', icon: '🏢', show: isSuperAdmin },
        { label: 'Branding', href: '/admin/branding', icon: '🎨', show: isSuperAdmin },
        { label: 'Reports', href: '/reports', icon: '📊', show: isPrivileged },
        { label: 'Knowledge Base', href: '/kb', icon: '📚', show: hasPerm('view knowledge base') },
        { label: 'Download App', href: '/download', icon: '⬇', show: true },
        { label: 'Proposal Coverage', href: '/proposal', icon: '✓', show: hasPerm('view proposal coverage') },
        { label: 'Help', href: '/help', icon: '?', show: hasPerm('view help') },
        { label: 'About', href: '/about', icon: 'ℹ', show: hasPerm('view about') },
        { label: 'Documentation', href: '/docs', icon: '📄', show: hasPerm('view documentation') },
    ];

    if (!user) return null;

    return (
        <aside className={`${collapsed ? 'w-16' : 'w-64'} bg-[#0b3d0b] text-white flex flex-col shrink-0 transition-all duration-200 min-h-[calc(100vh-56px)]`}>
            <div className="p-3 flex flex-col gap-2 border-b border-white/10">
                <div className="flex justify-between items-center">
                    {!collapsed && <span className="text-xs tracking-widest opacity-70">MENU</span>}
                    <button onClick={() => setCollapsed(!collapsed)} className="bg-white/10 hover:bg-white/20 rounded px-2 py-1 text-xs">
                        {collapsed ? '→' : '←'}
                    </button>
                </div>
                {!collapsed && (
                    <div className="flex items-center gap-2 bg-white/5 rounded p-2">
                        {appLogo ? <img src={appLogo} alt="logo" className="w-8 h-8 bg-white rounded object-contain p-1" /> : <span className="bg-white text-[#0b3d0b] w-8 h-8 flex items-center justify-center rounded text-xs font-bold">NA</span>}
                        <div className="text-xs leading-tight">
                            <div className="font-bold">{appName}</div>
                            <div className="opacity-60 text-[11px] truncate w-32">{usePage().props.branding?.app_motto}</div>
                        </div>
                    </div>
                )}
            </div>
            <nav className="flex-1 py-2">
                {items.filter(i=>i.show).map(item => {
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
                )})}
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
