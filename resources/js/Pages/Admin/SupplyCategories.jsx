import { router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import Layout from '../../Components/Layout';

export default function SupplyCategories({ categories }) {
    const form = useForm({ name: '', code: '', description: '', is_active: true });
    const editForm = useForm({ name: '', code: '', description: '', is_active: true });
    const [editingId, setEditingId] = useState(null);

    const create = (event) => {
        event.preventDefault();
        form.post('/admin/supply-categories', { onSuccess: () => form.reset() });
    };

    const beginEdit = (category) => {
        setEditingId(category.id);
        editForm.setData({
            name: category.name,
            code: category.code || '',
            description: category.description || '',
            is_active: Boolean(category.is_active),
        });
    };

    const update = (event) => {
        event.preventDefault();
        editForm.put(`/admin/supply-categories/${editingId}`, {
            onSuccess: () => {
                setEditingId(null);
                editForm.reset();
            },
        });
    };

    const destroy = (id) => {
        if (confirm('Delete this category? Items using it will keep their existing label.')) {
            router.delete(`/admin/supply-categories/${id}`);
        }
    };

    return (
        <Layout>
            <h1 className="text-2xl font-bold">Supply Item Categories</h1>
            <p className="text-sm text-slate-600">SuperAdmin and Admin can manage the options shown on the New Supply Item form.</p>

            <div className="grid md:grid-cols-2 gap-6 mt-6">
                <div className="bg-white border rounded-xl p-6">
                    <h3 className="font-semibold">Create Category</h3>
                    <form onSubmit={create} className="space-y-3 mt-3">
                        <input value={form.data.name} onChange={(e) => form.setData('name', e.target.value)} placeholder="Name e.g. Medical" className="w-full border rounded px-3 py-2" />
                        <input value={form.data.code} onChange={(e) => form.setData('code', e.target.value)} placeholder="Code e.g. MED" className="w-full border rounded px-3 py-2" />
                        <input value={form.data.description} onChange={(e) => form.setData('description', e.target.value)} placeholder="Description" className="w-full border rounded px-3 py-2" />
                        <label className="flex items-center gap-2 text-sm">
                            <input type="checkbox" checked={form.data.is_active} onChange={(e) => form.setData('is_active', e.target.checked)} />
                            Active
                        </label>
                        {Object.values(form.errors).map((error, index) => <p key={index} className="text-red-600 text-xs">{error}</p>)}
                        <button disabled={form.processing} className="w-full bg-[#0b3d0b] text-white py-2 rounded">Create</button>
                    </form>
                </div>

                <div className="bg-white border rounded-xl p-6">
                    <h3 className="font-semibold">Existing Categories</h3>
                    <div className="mt-3 divide-y">
                        {categories.map((category) => (
                            <div key={category.id} className="py-3">
                                {editingId === category.id ? (
                                    <form onSubmit={update} className="space-y-2">
                                        <input value={editForm.data.name} onChange={(e) => editForm.setData('name', e.target.value)} className="w-full border rounded px-2 py-1" />
                                        <input value={editForm.data.code} onChange={(e) => editForm.setData('code', e.target.value)} className="w-full border rounded px-2 py-1" />
                                        <input value={editForm.data.description} onChange={(e) => editForm.setData('description', e.target.value)} className="w-full border rounded px-2 py-1" />
                                        <label className="flex items-center gap-2 text-sm">
                                            <input type="checkbox" checked={editForm.data.is_active} onChange={(e) => editForm.setData('is_active', e.target.checked)} />
                                            Active
                                        </label>
                                        {Object.values(editForm.errors).map((error, index) => <p key={index} className="text-red-600 text-xs">{error}</p>)}
                                        <div className="flex gap-2">
                                            <button disabled={editForm.processing} className="border rounded px-3 py-1 text-sm">Save</button>
                                            <button type="button" onClick={() => setEditingId(null)} className="text-sm underline">Cancel</button>
                                        </div>
                                    </form>
                                ) : (
                                    <div className="flex justify-between gap-3">
                                        <div>
                                            <div className="font-semibold text-sm">
                                                {category.name}
                                                {category.code && <span className="text-xs bg-slate-100 px-1.5 py-0.5 rounded ml-2">{category.code}</span>}
                                                {!category.is_active && <span className="text-xs bg-amber-100 px-1.5 py-0.5 rounded ml-2">Inactive</span>}
                                            </div>
                                            <div className="text-xs text-slate-500">{category.description || 'No description'} • {category.items_count} items</div>
                                        </div>
                                        <div className="flex gap-2 shrink-0">
                                            <button onClick={() => beginEdit(category)} className="text-xs underline">Edit</button>
                                            <button onClick={() => destroy(category.id)} className="text-red-600 text-xs">Delete</button>
                                        </div>
                                    </div>
                                )}
                            </div>
                        ))}
                        {categories.length === 0 && <p className="text-sm text-slate-500 py-3">No categories yet.</p>}
                    </div>
                </div>
            </div>
        </Layout>
    );
}
