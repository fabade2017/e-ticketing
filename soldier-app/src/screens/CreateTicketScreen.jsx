import React, { useState } from 'react';
import { View, Text, TextInput, TouchableOpacity, ScrollView, Alert, StyleSheet } from 'react-native';
import client, { queueTicket, syncQueue } from '../api/client';
import NetInfo from '@react-native-async-storage/async-storage'; // fallback check via fetch

const CATEGORIES = ['welfare','logistics','medical','equipment','operations','other'];
const PRIORITIES = ['low','medium','high','urgent'];

export default function CreateTicketScreen({ navigation }) {
  const [form, setForm] = useState({ title:'', description:'', category:'other', priority:'medium', country:'Nigeria', state:'', lga:'', location:'' });
  const [saving, setSaving] = useState(false);

  const submit = async () => {
    if (!form.title || !form.description || !form.state || !form.lga) {
      return Alert.alert('Missing', 'Title, Description, State, LGA required');
    }
    setSaving(true);
    try {
      await client.post('/tickets', form);
      Alert.alert('Success', 'Ticket created');
      navigation.goBack();
    } catch (e) {
      const isNetwork = !e.response;
      if (isNetwork) {
        // Military offline: queue locally for border areas
        await queueTicket(form);
        Alert.alert('Offline queued', 'No signal — ticket saved locally. Will auto-sync when online. Check Tickets screen pull-to-refresh.');
        navigation.goBack();
      } else {
        const msg = e.response?.data?.message || JSON.stringify(e.response?.data?.errors) || 'Failed';
        Alert.alert('Failed', msg);
      }
    } finally { setSaving(false); }
  };

  const sync = async () => {
    const r = await syncQueue();
    Alert.alert('Sync', `Synced ${r.synced}, remaining ${r.remaining}`);
  };

  return (
    <ScrollView style={s.container} contentContainerStyle={{ padding:16 }}>
      <Text style={s.h1}>New Ticket</Text>
      <Text style={s.hint}>For remote/border outposts: auto-queues offline and syncs.</Text>

      <Text style={s.label}>Title</Text>
      <TextInput style={s.input} value={form.title} onChangeText={v=>setForm({...form, title:v})} placeholder="e.g. Water pump faulty at OP 1" />

      <Text style={s.label}>Category</Text>
      <View style={s.row}>{CATEGORIES.map(c=> <TouchableOpacity key={c} onPress={()=>setForm({...form, category:c})} style={[s.pill, form.category===c && s.pillOn]}><Text style={form.category===c ? {color:'#fff'} : {}}>{c}</Text></TouchableOpacity>)}</View>

      <Text style={s.label}>Priority (SLA: urgent 4h / high 12h / medium 24h / low 72h)</Text>
      <View style={s.row}>{PRIORITIES.map(p=> <TouchableOpacity key={p} onPress={()=>setForm({...form, priority:p})} style={[s.pill, form.priority===p && s.pillOn]}><Text style={form.priority===p ? {color:'#fff'} : {}}>{p}</Text></TouchableOpacity>)}</View>

      <Text style={s.label}>State</Text>
      <TextInput style={s.input} value={form.state} onChangeText={v=>setForm({...form, state:v})} placeholder="e.g. Borno" />
      <Text style={s.label}>LGA</Text>
      <TextInput style={s.input} value={form.lga} onChangeText={v=>setForm({...form, lga:v})} placeholder="e.g. Maiduguri" />
      <Text style={s.label}>Location / Outpost detail</Text>
      <TextInput style={s.input} value={form.location} onChangeText={v=>setForm({...form, location:v})} placeholder="Sector 2, precise OP" />
      <Text style={s.label}>Description</Text>
      <TextInput style={[s.input, { height:120, textAlignVertical:'top' }]} value={form.description} onChangeText={v=>setForm({...form, description:v})} multiline placeholder="Describe issue..." />

      <TouchableOpacity style={s.btn} onPress={submit} disabled={saving}><Text style={s.btnTxt}>{saving?'Submitting...':'Submit Ticket'}</Text></TouchableOpacity>
      <TouchableOpacity style={[s.btn, { backgroundColor:'#334155', marginTop:8 }]} onPress={sync}><Text style={s.btnTxt}>Sync Offline Queue</Text></TouchableOpacity>
    </ScrollView>
  );
}
const s = StyleSheet.create({
  container:{ flex:1, backgroundColor:'#f8fafc' },
  h1:{ fontSize:22, fontWeight:'800', color:'#0b3d0b' },
  hint:{ fontSize:11, color:'#64748b', marginBottom:12 },
  label:{ fontSize:12, fontWeight:'700', marginTop:12, marginBottom:6, color:'#334155' },
  input:{ backgroundColor:'#fff', borderWidth:1, borderColor:'#cbd5e1', borderRadius:10, padding:12 },
  row:{ flexDirection:'row', flexWrap:'wrap', gap:8 },
  pill:{ borderWidth:1, borderColor:'#cbd5e1', borderRadius:20, paddingHorizontal:12, paddingVertical:8, backgroundColor:'#fff' },
  pillOn:{ backgroundColor:'#0b3d0b', borderColor:'#0b3d0b' },
  btn:{ backgroundColor:'#0b3d0b', borderRadius:12, padding:14, alignItems:'center', marginTop:16 },
  btnTxt:{ color:'#fff', fontWeight:'800' }
});
