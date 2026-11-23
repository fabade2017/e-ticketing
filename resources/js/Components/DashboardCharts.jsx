import { BarChart, Bar, XAxis, YAxis, Tooltip, ResponsiveContainer, PieChart, Pie, Cell, LineChart, Line, CartesianGrid, Legend } from 'recharts';

const COLORS = ['#0b3d0b','#3b82f6','#f59e0b','#ef4444','#8b5cf6','#06b6d4'];

export default function DashboardCharts({ statusData, catData, priorityData, trendData }) {
    return (
        <div className="grid md:grid-cols-2 gap-6 mt-6">
            <div className="bg-white border rounded-xl p-5">
                <h3 className="font-semibold text-sm">Tickets by Status</h3>
                <ResponsiveContainer width="100%" height={220}>
                    <BarChart data={statusData}><CartesianGrid strokeDasharray="3 3" /><XAxis dataKey="name" fontSize={11} /><YAxis fontSize={11} /><Tooltip /><Bar dataKey="value" fill="#0b3d0b" radius={[6,6,0,0]} /></BarChart>
                </ResponsiveContainer>
            </div>
            <div className="bg-white border rounded-xl p-5">
                <h3 className="font-semibold text-sm">Tickets by Category</h3>
                {catData.length ? <ResponsiveContainer width="100%" height={220}><PieChart><Pie data={catData} dataKey="value" nameKey="name" cx="50%" cy="50%" outerRadius={80} label>{catData.map((e,i)=><Cell key={i} fill={COLORS[i%COLORS.length]} />)}</Pie><Tooltip /><Legend /></PieChart></ResponsiveContainer> : <p className="text-sm text-slate-500 mt-10">No category data yet</p>}
            </div>
            <div className="bg-white border rounded-xl p-5">
                <h3 className="font-semibold text-sm">Priority Breakdown</h3>
                <ResponsiveContainer width="100%" height={220}><BarChart data={priorityData} layout="vertical"><CartesianGrid strokeDasharray="3 3" /><XAxis type="number" fontSize={11} /><YAxis type="category" dataKey="name" fontSize={11} width={70} /><Tooltip /><Bar dataKey="value" fill="#f59e0b" radius={[0,6,6,0]} /></BarChart></ResponsiveContainer>
            </div>
            <div className="bg-white border rounded-xl p-5">
                <h3 className="font-semibold text-sm">14-Day Trend</h3>
                {trendData.length ? <ResponsiveContainer width="100%" height={220}><LineChart data={trendData}><CartesianGrid strokeDasharray="3 3" /><XAxis dataKey="date" fontSize={10} /><YAxis fontSize={11} /><Tooltip /><Line type="monotone" dataKey="count" stroke="#0b3d0b" strokeWidth={2} dot /></LineChart></ResponsiveContainer> : <p className="text-sm text-slate-500 mt-10">No trend yet</p>}
            </div>
        </div>
    );
}
