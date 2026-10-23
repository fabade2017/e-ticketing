import React, { useEffect, useState } from 'react';
import { View, Text, ScrollView, StyleSheet } from 'react-native';
import client from '../api/client';

export default function TicketDetailScreen({ route }) {
  const { id } = route.params;
  const [ticket, setTicket] = useState(null);
  const [histories, setHistories] = useState([]);

  useEffect(() => {
    client.get(`/tickets/${id}`).then(({data})=> { setTicket(data.ticket); setHistories(data.histories); }).catch(console.log);
  }, [id]);

  if (!ticket) return <View style={s.center}><Text>Loading...</Text></View>;

  return (
    <ScrollView style={s.container} contentContainerStyle={{ padding:16 }}>
      <Text style={s.mono}>{ticket.ticket_number} • SLA {ticket.sla_due_at ? new Date(ticket.sla_due_at).toLocaleString() : '—'}</Text>
      <Text style={s.title}>{ticket.title}</Text>
      <View style={s.row}><Text style={s.badge}>{ticket.status}</Text><Text style={s.badge}>{ticket.priority} • {ticket.category}</Text></View>
      <Text style={s.meta}>{ticket.state} / {ticket.lga} • {ticket.location || '—'} • {ticket.department?.name || 'No dept'}</Text>
      {ticket.is_anonymous && <Text style={s.meta}>Raised by: Anonymous — no identity attached</Text>}
      <Text style={s.desc}>{ticket.description}</Text>

      {ticket.escalated_at && <Text style={s.warn}>⚠ Escalated: {new Date(ticket.escalated_at).toLocaleString()} (overdue SLA)</Text>}
      {ticket.isOverdue && <Text style={s.warn}>OVERDUE</Text>}

      <Text style={s.h2}>History</Text>
      {histories.map(h=> (
        <View key={h.id} style={s.hist}>
          <Text style={s.histAction}>{h.action} • {h.user ? `${h.user.rank || ''} ${h.user.name || ''}`.trim() : (h.action === 'created' && ticket.is_anonymous ? 'Anonymous' : 'System')}</Text>
          <Text style={s.histMeta}>{new Date(h.created_at).toLocaleString()}{h.ip ? ` • ${h.ip}` : ''}</Text>
        </View>
      ))}
      {histories.length===0 && <Text style={s.meta}>No history yet</Text>}
    </ScrollView>
  );
}
const s = StyleSheet.create({
  container:{ flex:1, backgroundColor:'#fff' },
  center:{ flex:1, justifyContent:'center', alignItems:'center' },
  mono:{ fontFamily:'monospace', fontSize:11, color:'#0b3d0b', fontWeight:'700' },
  title:{ fontSize:20, fontWeight:'800', marginTop:6 },
  row:{ flexDirection:'row', gap:8, marginTop:8 },
  badge:{ backgroundColor:'#f1f5f9', paddingHorizontal:10, paddingVertical:6, borderRadius:20, fontSize:11, overflow:'hidden' },
  meta:{ color:'#64748b', fontSize:12, marginTop:8 },
  desc:{ marginTop:16, lineHeight:20, color:'#1e293b' },
  warn:{ marginTop:12, backgroundColor:'#fef2f2', color:'#dc2626', padding:10, borderRadius:10, overflow:'hidden' },
  h2:{ fontWeight:'800', marginTop:24, marginBottom:8 },
  hist:{ backgroundColor:'#f8fafc', borderRadius:10, padding:10, marginBottom:8 },
  histAction:{ fontWeight:'700', fontSize:12 },
  histMeta:{ fontSize:11, color:'#64748b' }
});
