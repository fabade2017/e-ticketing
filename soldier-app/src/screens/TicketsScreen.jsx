import React, { useEffect, useState, useCallback } from 'react';
import { View, Text, FlatList, TouchableOpacity, RefreshControl, TextInput, StyleSheet } from 'react-native';
import client from '../api/client';
import { useAuth } from '../context/AuthContext';

function SLABadge({ ticket }) {
  if (!ticket.sla_due_at || ['resolved','closed'].includes(ticket.status)) return null;
  const due = new Date(ticket.sla_due_at);
  const overdue = due < new Date();
  const hours = Math.round((due - new Date())/3600000);
  const bg = overdue ? '#dc2626' : hours < 4 ? '#f59e0b' : '#dcfce7';
  const fg = overdue ? '#fff' : hours < 4 ? '#000' : '#166534';
  return <Text style={{ backgroundColor:bg, color:fg, fontSize:10, paddingHorizontal:6, paddingVertical:2, borderRadius:6, overflow:'hidden' }}>{overdue ? 'OVERDUE' : `${hours}h SLA`}</Text>;
}

export default function TicketsScreen({ navigation }) {
  const { user } = useAuth();
  const [tickets, setTickets] = useState([]);
  const [meta, setMeta] = useState(null);
  const [loading, setLoading] = useState(false);
  const [search, setSearch] = useState('');
  const [status, setStatus] = useState('');

  const load = useCallback(async () => {
    setLoading(true);
    try {
      const params = {};
      if (search) params.search = search;
      if (status) params.status = status;
      const { data } = await client.get('/tickets', { params });
      setTickets(data.data || []);
      setMeta(data);
    } catch (e) { console.log(e.response?.data); } finally { setLoading(false); }
  }, [search, status]);

  useEffect(() => { load(); }, [load]);

  return (
    <View style={s.container}>
      <View style={s.header}>
        <Text style={s.headerTitle}>My Tickets</Text>
        <Text style={s.headerSub}>{user?.rank} {user?.name} • {user?.unit || 'No unit'} • SLA: urgent 4h / high 12h</Text>
      </View>

      <View style={s.filters}>
        <TextInput placeholder="Search number/title" value={search} onChangeText={setSearch} style={[s.input, { flex:1 }]} onSubmitEditing={load} />
        <TouchableOpacity onPress={load} style={s.filterBtn}><Text style={s.filterTxt}>Filter</Text></TouchableOpacity>
      </View>
      <View style={s.chips}>
        {['','open','in_progress','resolved','closed'].map(v=> (
          <TouchableOpacity key={v} onPress={()=>setStatus(v)} style={[s.chip, status===v && s.chipActive]}><Text style={[s.chipTxt, status===v && {color:'#fff'}]}>{v||'All'}</Text></TouchableOpacity>
        ))}
      </View>

      <FlatList
        data={tickets}
        keyExtractor={t=>String(t.id)}
        refreshControl={<RefreshControl refreshing={loading} onRefresh={load} />}
        ListEmptyComponent={<Text style={{textAlign:'center', marginTop:40, color:'#64748b'}}>No tickets. Tap + to create.</Text>}
        renderItem={({item:t})=> (
          <TouchableOpacity onPress={()=>navigation.navigate('TicketDetail', { id: t.id })} style={s.card}>
            <View style={{flexDirection:'row', justifyContent:'space-between'}}>
              <Text style={s.mono}>{t.ticket_number} • {t.category}</Text>
              <SLABadge ticket={t} />
            </View>
            <Text style={s.title2}>{t.title}</Text>
            <View style={{flexDirection:'row', gap:6, marginTop:6}}>
              <Text style={s.badge}>{t.priority}</Text>
              <Text style={[s.badge, {backgroundColor: t.status==='open'?'#fef3c7':'#e2e8f0'}]}>{t.status}</Text>
              {t.escalated_at && <Text style={[s.badge,{backgroundColor:'#fde68a'}]}>escalated</Text>}
            </View>
            <Text style={s.meta}>{t.department?.name || '—'} • {new Date(t.created_at).toLocaleDateString()}</Text>
          </TouchableOpacity>
        )}
      />

      <TouchableOpacity onPress={()=>navigation.navigate('CreateTicket')} style={s.fab}><Text style={s.fabTxt}>+</Text></TouchableOpacity>
    </View>
  );
}
const s = StyleSheet.create({
  container:{ flex:1, backgroundColor:'#f1f5f9' },
  header:{ backgroundColor:'#0b3d0b', padding:16, paddingTop:48 },
  headerTitle:{ color:'#fff', fontSize:22, fontWeight:'800' },
  headerSub:{ color:'#cbd5e1', fontSize:11, marginTop:4 },
  filters:{ flexDirection:'row', padding:12, gap:8, backgroundColor:'#fff' },
  input:{ borderWidth:1, borderColor:'#cbd5e1', borderRadius:10, padding:10 },
  filterBtn:{ backgroundColor:'#0f172a', borderRadius:10, paddingHorizontal:16, justifyContent:'center' },
  filterTxt:{ color:'#fff', fontWeight:'700' },
  chips:{ flexDirection:'row', gap:6, paddingHorizontal:12, paddingBottom:8, backgroundColor:'#fff' },
  chip:{ borderWidth:1, borderColor:'#cbd5e1', borderRadius:20, paddingHorizontal:10, paddingVertical:6 },
  chipActive:{ backgroundColor:'#0b3d0b', borderColor:'#0b3d0b' },
  chipTxt:{ fontSize:11 },
  card:{ backgroundColor:'#fff', margin:12, marginBottom:0, borderRadius:14, padding:14, elevation:1 },
  mono:{ fontFamily:'monospace', fontSize:10, color:'#0b3d0b', fontWeight:'700' },
  title2:{ fontWeight:'700', marginTop:4 },
  badge:{ fontSize:10, backgroundColor:'#f1f5f9', paddingHorizontal:8, paddingVertical:3, borderRadius:20, overflow:'hidden' },
  meta:{ fontSize:11, color:'#64748b', marginTop:6 },
  fab:{ position:'absolute', right:20, bottom:20, width:56, height:56, borderRadius:28, backgroundColor:'#0b3d0b', justifyContent:'center', alignItems:'center', elevation:4 },
  fabTxt:{ color:'#fff', fontSize:28, lineHeight:28 }
});
