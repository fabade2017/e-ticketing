import React, { useState, useEffect } from 'react';
import { View, Text, TextInput, TouchableOpacity, ScrollView, Alert, Modal, FlatList, StyleSheet } from 'react-native';
import client, { queueTicket, getMeta, addAnonymousToken } from '../api/client';
import { useAuth } from '../context/AuthContext';
import SyncButton from '../components/SyncButton';
import { NIGERIA_STATES, STATES } from '../data/nigeria';
import { COUNTRIES, DEFAULT_COUNTRY } from '../data/countries';

const CATEGORIES = [
  { label: 'Welfare', value: 'welfare' },
  { label: 'Logistics', value: 'logistics' },
  { label: 'Medical', value: 'medical' },
  { label: 'Equipment', value: 'equipment' },
  { label: 'Operations', value: 'operations' },
  { label: 'Other', value: 'other' },
];
const PRIORITIES = [
  { label: 'Low', value: 'low' },
  { label: 'Medium', value: 'medium' },
  { label: 'High', value: 'high' },
  { label: 'Urgent', value: 'urgent' },
];

function Dropdown({ label, value, placeholder, options, onSelect, disabled }) {
  const [open, setOpen] = useState(false);
  const display = options.find(o => o.value === value)?.label || value || placeholder;
  return (
    <View style={{ flex: 1 }}>
      <Text style={s.label}>{label}</Text>
      <TouchableOpacity disabled={disabled} onPress={()=>setOpen(true)} style={[s.input, disabled && { backgroundColor:'#f1f5f9' }, { flexDirection:'row', justifyContent:'space-between', alignItems:'center' }]}>
        <Text style={{ color: value ? '#0f172a' : '#94a3b8' }}>{display}</Text><Text style={{ color:'#64748b' }}>▼</Text>
      </TouchableOpacity>
      <Modal visible={open} transparent animationType="fade">
        <TouchableOpacity style={s.modalOverlay} onPress={()=>setOpen(false)} activeOpacity={1}>
          <View style={s.modalBox}>
            <Text style={s.modalTitle}>{label}</Text>
            <FlatList data={options} keyExtractor={o=>o.value} style={{ maxHeight: 380 }}
              renderItem={({item})=>(
                <TouchableOpacity onPress={()=>{ onSelect(item.value); setOpen(false); }} style={[s.modalItem, value===item.value && { backgroundColor:'#0b3d0b' }]}>
                  <Text style={[s.modalItemText, value===item.value && { color:'#fff', fontWeight:'800' }]}>{item.label}</Text>
                </TouchableOpacity>
              )} />
            <TouchableOpacity onPress={()=>setOpen(false)} style={s.modalCancel}><Text style={s.modalCancelText}>Cancel</Text></TouchableOpacity>
          </View>
        </TouchableOpacity>
      </Modal>
    </View>
  );
}

export default function CreateTicketScreen({ navigation }) {
  const { user } = useAuth();
  const [departments, setDepartments] = useState([]);
  const [form, setForm] = useState({ title:'', description:'', category:'other', priority:'medium', department_id: user?.department_id || '', country: DEFAULT_COUNTRY, state:'', lga:'', location:'', is_anonymous: false });
  const [saving, setSaving] = useState(false);
  const isNigeria = form.country === 'Nigeria';
  const lgaOptions = (isNigeria && form.state) ? (NIGERIA_STATES[form.state] || []).map(l=>({ label:l, value:l })) : [];

  useEffect(() => {
    getMeta().then(m => setDepartments(m.departments || [])).catch(()=>{});
  }, []);

  useEffect(() => {
    if (!form.department_id && user?.department_id) setForm(f => ({ ...f, department_id: user.department_id }));
  }, [user]);

  const submit = async () => {
    if (!form.title || !form.description || !form.state || !form.lga) {
      return Alert.alert('Missing', 'Title, Description, State, LGA required');
    }
    setSaving(true);
    try {
      const { data } = await client.post('/tickets', form);
      if (data?.anonymous_token) await addAnonymousToken(data.anonymous_token);
      Alert.alert('Success', 'Ticket created');
      navigation.goBack();
    } catch (e) {
      const isNetwork = !e.response;
      if (isNetwork) {
        await queueTicket(form);
        Alert.alert('Offline queued', 'No signal — ticket saved locally. Will auto-sync when online.');
        navigation.goBack();
      } else {
        const msg = e.response?.data?.message || JSON.stringify(e.response?.data?.errors) || 'Failed';
        Alert.alert('Failed', msg);
      }
    } finally { setSaving(false); }
  };

  return (
    <ScrollView style={s.container} contentContainerStyle={{ padding:16, paddingBottom: 40 }}>
      <Text style={s.h1}>Create Ticket</Text>
      <Text style={s.hint}>For remote/border outposts: auto-queues offline and syncs. Fields match web app.</Text>

      <TouchableOpacity
        onPress={()=>setForm({...form, is_anonymous: !form.is_anonymous})}
        style={[s.anonBtn, form.is_anonymous && s.anonBtnOn]}
        accessibilityRole="button"
        accessibilityState={{ selected: form.is_anonymous }}
      >
        <Text style={[s.anonTxt, form.is_anonymous && { color:'#fff' }]}>
          {form.is_anonymous ? 'Anonymous: ON — tap to disable' : 'Anonymous — hide my identity'}
        </Text>
      </TouchableOpacity>
      {form.is_anonymous && (
        <Text style={s.anonNote}>Your name, service number, unit and IP will not be stored. It will still appear in your ticket list on this device.</Text>
      )}

      <Text style={s.label}>Title</Text>
      <TextInput style={s.input} value={form.title} onChangeText={v=>setForm({...form, title:v})} placeholder="e.g. Water pump faulty at OP 1" />

      <View style={{ flexDirection:'row', gap:10 }}>
        <Dropdown label="Category" value={form.category} placeholder="Select Category" options={CATEGORIES} onSelect={v=>setForm({...form, category:v})} />
        <Dropdown label="Priority" value={form.priority} placeholder="Select Priority" options={PRIORITIES} onSelect={v=>setForm({...form, priority:v})} />
      </View>

      <View style={{ flexDirection:'row', gap:10 }}>
        <Dropdown label="Department" value={form.department_id} placeholder="Select Department" options={departments.map(d=>({ label:d.name, value:d.id }))} onSelect={v=>setForm({...form, department_id:v})} />
      </View>

      <View style={{ flexDirection:'row', gap:10 }}>
        <Dropdown label="Country" value={form.country} placeholder="Select Country" options={COUNTRIES.map(c=>({ label:c, value:c }))} onSelect={v=>setForm({...form, country:v, state:'', lga:''})} />
        {isNigeria
          ? <Dropdown label="State" value={form.state} placeholder="Select State" options={STATES.map(s=>({ label:s, value:s }))} onSelect={v=>setForm({...form, state:v, lga:''})} />
          : <View style={{ flex:1 }}><Text style={s.label}>State</Text><TextInput style={s.input} value={form.state} onChangeText={v=>setForm({...form, state:v})} placeholder="State / Province / Region" /></View>}
      </View>
      {isNigeria
        ? <Dropdown label="LGA" value={form.lga} placeholder={form.state ? 'Select LGA' : 'Pick state first'} options={lgaOptions} onSelect={v=>setForm({...form, lga:v})} disabled={!form.state} />
        : <View><Text style={s.label}>LGA</Text><TextInput style={s.input} value={form.lga} onChangeText={v=>setForm({...form, lga:v})} placeholder="LGA / District / City" /></View>}

      <Text style={s.label}>Location / Outpost detail</Text>
      <TextInput style={s.input} value={form.location} onChangeText={v=>setForm({...form, location:v})} placeholder="e.g. Sector 2, Borno — detailed post" />

      <Text style={s.label}>Description</Text>
      <TextInput style={[s.input, { height:120, textAlignVertical:'top' }]} value={form.description} onChangeText={v=>setForm({...form, description:v})} multiline placeholder="Describe issue..." />

      <TouchableOpacity style={s.btn} onPress={submit} disabled={saving}><Text style={s.btnTxt}>{saving?'Submitting...':'Submit Ticket'}</Text></TouchableOpacity>
      <SyncButton style={[s.btn, { backgroundColor:'#334155', marginTop:8 }]} textStyle={s.btnTxt} />
      <Text style={s.note}>Country list shared with the web app • Nigerian State/LGA dropdowns appear when Country = Nigeria, otherwise State/LGA are free text.</Text>
    </ScrollView>
  );
}
const s = StyleSheet.create({
  container:{ flex:1, backgroundColor:'#f8fafc' },
  h1:{ fontSize:22, fontWeight:'800', color:'#0b3d0b' },
  hint:{ fontSize:11, color:'#64748b', marginBottom:4 },
  label:{ fontSize:11, fontWeight:'700', marginTop:12, marginBottom:6, color:'#334155' },
  input:{ backgroundColor:'#fff', borderWidth:1, borderColor:'#cbd5e1', borderRadius:10, padding:12 },
  btn:{ backgroundColor:'#0b3d0b', borderRadius:12, padding:14, alignItems:'center', marginTop:16 },
  btnTxt:{ color:'#fff', fontWeight:'800' },
  anonBtn:{ backgroundColor:'#fff', borderWidth:1, borderColor:'#cbd5e1', borderRadius:12, padding:12, alignItems:'center', marginTop:14 },
  anonBtnOn:{ backgroundColor:'#b45309', borderColor:'#b45309' },
  anonTxt:{ fontWeight:'800', color:'#334155', fontSize:12 },
  anonNote:{ fontSize:10, color:'#92400e', backgroundColor:'#fffbeb', borderWidth:1, borderColor:'#fcd34d', borderRadius:10, padding:10, marginTop:8, lineHeight:14 },
  note:{ fontSize:10, color:'#94a3b8', marginTop:8, textAlign:'center' },
  modalOverlay:{ flex:1, backgroundColor:'rgba(0,0,0,0.4)', justifyContent:'center', padding:20 },
  modalBox:{ backgroundColor:'#fff', borderRadius:16, padding:16, maxHeight: 520 },
  modalTitle:{ fontWeight:'800', fontSize:16, marginBottom:10, color:'#0b3d0b' },
  modalItem:{ padding:12, borderRadius:8, marginBottom:4, borderWidth:1, borderColor:'#f1f5f9' },
  modalItemText:{ fontSize:14 },
  modalCancel:{ marginTop:8, borderWidth:1, borderColor:'#cbd5e1', borderRadius:10, padding:12, alignItems:'center' },
  modalCancelText:{ fontWeight:'700', color:'#475569' }
});
