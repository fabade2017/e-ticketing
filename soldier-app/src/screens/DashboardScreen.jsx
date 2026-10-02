import React, { useEffect, useState } from 'react';
import { View, Text, StyleSheet, TouchableOpacity, ScrollView } from 'react-native';
import client from '../api/client';
import { useAuth } from '../context/AuthContext';
import { syncQueue, getQueue } from '../api/client';

export default function DashboardScreen() {
  const { user, logout } = useAuth();
  const [stats, setStats] = useState(null);
  const [queueLen, setQueueLen] = useState(0);

  const load = async (silent=false) => {
    try { const {data}= await client.get('/tickets/stats', { __silent: silent }); setStats(data); } catch {}
    setQueueLen((await getQueue()).length);
  };
  useEffect(()=>{ load(false); const id=setInterval(()=>load(true),10000); return()=>clearInterval(id); },[]);

  return (
    <ScrollView style={s.container} contentContainerStyle={{ padding:16, paddingTop:48 }}>
      <Text style={s.h1}>Welcome, {user?.rank} {user?.name}</Text>
      <Text style={s.sub}>{user?.service_number} • {user?.unit} • {user?.department?.name || '—'} • {user?.roles?.join(',')}</Text>

      <View style={s.grid}>
        <View style={[s.card, {backgroundColor:'#f1f5f9'}]}><Text style={s.num}>{stats?.total ?? '—'}</Text><Text style={s.lbl}>Total</Text></View>
        <View style={[s.card, {backgroundColor:'#fef3c7'}]}><Text style={s.num}>{stats?.open ?? '—'}</Text><Text style={s.lbl}>Open</Text></View>
        <View style={[s.card, {backgroundColor:'#dbeafe'}]}><Text style={s.num}>{stats?.in_progress ?? '—'}</Text><Text style={s.lbl}>In Progress</Text></View>
        <View style={[s.card, {backgroundColor:'#fee2e2'}]}><Text style={s.num}>{stats?.overdue ?? '—'}</Text><Text style={s.lbl}>Overdue SLA</Text></View>
      </View>

      <View style={s.info}>
        <Text style={s.infoTitle}>Field Ops Notice</Text>
        <Text style={s.infoTxt}>• Tickets offline-queue when no signal (border areas). Pull to sync.{"\n"}• SLA: urgent 4h • high 12h • medium 24h • low 72h.{"\n"}• Offline queue: {queueLen} pending • Tap Sync to retry</Text>
        <TouchableOpacity onPress={async()=>{ const r=await syncQueue(); alert(`Synced ${r.synced}, remaining ${r.remaining}`); load(false); }} style={s.syncBtn}><Text style={s.syncTxt}>Sync Offline Queue ({queueLen})</Text></TouchableOpacity>
      </View>

      <TouchableOpacity onPress={logout} style={s.logout}><Text style={s.logoutTxt}>Logout</Text></TouchableOpacity>
      <Text style={s.foot}>NA E-Ticket • Secure • Offline-Capable • {new Date().getFullYear()}</Text>
    </ScrollView>
  );
}
const s = StyleSheet.create({
  container:{ flex:1, backgroundColor:'#fff' },
  h1:{ fontSize:22, fontWeight:'800', color:'#0b3d0b' },
  sub:{ fontSize:11, color:'#64748b', marginTop:4 },
  grid:{ flexDirection:'row', flexWrap:'wrap', gap:12, marginTop:16 },
  card:{ width:'47%', borderRadius:14, padding:16, alignItems:'center' },
  num:{ fontSize:28, fontWeight:'800' },
  lbl:{ fontSize:11, color:'#475569', marginTop:4 },
  info:{ backgroundColor:'#f8fafc', borderRadius:14, padding:16, marginTop:16, borderWidth:1, borderColor:'#e2e8f0' },
  infoTitle:{ fontWeight:'800', color:'#0b3d0b' },
  infoTxt:{ fontSize:12, color:'#334155', marginTop:6, lineHeight:18 },
  syncBtn:{ backgroundColor:'#0b3d0b', borderRadius:10, padding:12, alignItems:'center', marginTop:12 },
  syncTxt:{ color:'#fff', fontWeight:'700' },
  logout:{ marginTop:24, borderWidth:1, borderColor:'#fecaca', borderRadius:10, padding:14, alignItems:'center' },
  logoutTxt:{ color:'#dc2626', fontWeight:'700' },
  foot:{ textAlign:'center', fontSize:10, color:'#94a3b8', marginTop:16 }
});
