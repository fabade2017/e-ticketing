import React, { useEffect, useState } from 'react';
import { View, Text, StyleSheet, TouchableOpacity, ScrollView, Alert, Linking } from 'react-native';
import { useNavigation } from '@react-navigation/native';
import client, { API_URL, getQueue } from '../api/client';
import { useAuth } from '../context/AuthContext';
import SyncButton from '../components/SyncButton';
import SetPinModal from '../components/SetPinModal';

const WEB_BASE_URL = API_URL.replace(/\/api\/?$/, '');

export default function DashboardScreen() {
  const navigation = useNavigation();
  const { user, logout, offline, pinSet, setPin, forgetOffline } = useAuth();
  const [stats, setStats] = useState(null);
  const [queueLen, setQueueLen] = useState(0);
  const [rooms, setRooms] = useState([]);
  const [openRoom, setOpenRoom] = useState(null);
  const [notifications, setNotifications] = useState([]);
  const [showSetPin, setShowSetPin] = useState(false);
  const [pinError, setPinError] = useState('');

  const load = async (silent = false) => {
    try { const { data } = await client.get('/tickets/stats', { __silent: silent }); setStats(data); } catch {}
    try { const { data } = await client.get('/suite/rooms', { __silent: silent }); setRooms(data?.rooms || []); } catch {}
    try { const { data } = await client.get('/suite/notifications', { __silent: silent }); setNotifications(data?.notifications || []); } catch {}
    setQueueLen((await getQueue()).length);
  };
  useEffect(() => { load(false); const id = setInterval(() => load(true), 10000); return () => clearInterval(id); }, []);

  const savePin = async (pin) => {
    try {
      await setPin(pin);
      setShowSetPin(false);
      alert(pinSet ? 'Offline PIN updated' : 'Offline PIN set — you can now log in with no network.');
    } catch (e) {
      setPinError(e.message || 'Could not set PIN.');
    }
  };

  const toggleRoom = (key) => {
    setOpenRoom((current) => current === key ? null : key);
  };

  const openMenu = async (menu) => {
    const route = menu.route || '/';
    if (route === '/tickets/create') {
      navigation.navigate('CreateTicket');
      return;
    }
    if (route.startsWith('/tickets')) {
      navigation.navigate('Tickets');
      return;
    }
    try {
      await Linking.openURL(`${WEB_BASE_URL}${route}`);
    } catch {
      Alert.alert('Unable to open', `${menu.label} could not be opened on this device.`);
    }
  };

  return (
    <ScrollView style={s.container} contentContainerStyle={{ padding:16, paddingTop:48 }}>
      <Text style={s.h1}>Welcome, {user?.rank} {user?.name}</Text>
      <Text style={s.sub}>{user?.service_number} • {user?.unit} • {user?.department?.name || '—'} • {user?.roles?.join(',')}</Text>

      {offline && (
        <View style={s.offlineBanner}>
          <Text style={s.offlineTxt}>OFFLINE SESSION — tickets are queued on this device and sync when you are back online.</Text>
        </View>
      )}

      <View style={s.grid}>
        <View style={[s.card, { backgroundColor: '#f1f5f9' }]}><Text style={s.num}>{stats?.total ?? '—'}</Text><Text style={s.lbl}>Total</Text></View>
        <View style={[s.card, { backgroundColor: '#fef3c7' }]}><Text style={s.num}>{stats?.open ?? '—'}</Text><Text style={s.lbl}>Open</Text></View>
        <View style={[s.card, { backgroundColor: '#dbeafe' }]}><Text style={s.num}>{stats?.in_progress ?? '—'}</Text><Text style={s.lbl}>In Progress</Text></View>
        <View style={[s.card, { backgroundColor: '#fee2e2' }]}><Text style={s.num}>{stats?.overdue ?? '—'}</Text><Text style={s.lbl}>Overdue SLA</Text></View>
      </View>

      <View style={s.info}>
        <Text style={s.infoTitle}>Suite Rooms</Text>
        <Text style={s.infoTxt}>Tap a room to view its menus. Other rooms collapse automatically.</Text>
        {rooms.length === 0 && <Text style={s.empty}>No suite rooms available for your access.</Text>}
        {rooms.map((room) => {
          const isOpen = openRoom === room.key;
          return (
            <View key={room.key} style={s.room}>
              <TouchableOpacity style={s.roomHead} onPress={() => toggleRoom(room.key)} aria-expanded={isOpen}>
                <Text style={s.roomIcon}>{room.icon}</Text>
                <View style={s.roomCopy}>
                  <Text style={s.roomName}>{room.label}</Text>
                  <Text style={s.roomDesc}>{room.description}</Text>
                </View>
                <Text style={s.roomToggle}>{isOpen ? '−' : '+'}</Text>
              </TouchableOpacity>
              {isOpen && (room.menus || []).map((menu) => (
                <TouchableOpacity key={menu.route + menu.label} style={s.menuItem} onPress={() => openMenu(menu)}>
                  <Text style={s.menuIcon}>{menu.icon}</Text>
                  <Text style={s.menuLabel}>{menu.label}</Text>
                </TouchableOpacity>
              ))}
            </View>
          );
        })}
      </View>

      {notifications.length > 0 && (
        <View style={s.info}>
          <Text style={s.infoTitle}>Notifications</Text>
          {notifications.map((notification) => (
            <View key={notification.id} style={s.notification}>
              <Text style={s.notificationModule}>{notification.module}</Text>
              <Text style={s.notificationTitle}>{notification.title}</Text>
            </View>
          ))}
        </View>
      )}

      <View style={s.info}>
        <Text style={s.infoTitle}>Field Ops Notice</Text>
        <Text style={s.infoTxt}>• Tickets offline-queue when no signal (border areas).{"\n"}• SLA: urgent 4h • high 12h • medium 24h • low 72h.{"\n"}• Offline queue: {queueLen} pending</Text>
        <SyncButton style={s.syncBtn} textStyle={s.syncTxt} onDone={() => load(false)} />
        <TouchableOpacity style={s.pinBtn} onPress={() => { setPinError(''); setShowSetPin(true); }}>
          <Text style={s.pinTxt}>{pinSet ? 'Change offline PIN' : 'Set offline PIN'}</Text>
        </TouchableOpacity>
        {pinSet && (
          <TouchableOpacity onPress={forgetOffline} style={s.forget}>
            <Text style={s.forgetTxt}>Forget offline login on this device</Text>
          </TouchableOpacity>
        )}
      </View>

      <TouchableOpacity onPress={logout} style={s.logout}><Text style={s.logoutTxt}>Logout</Text></TouchableOpacity>
      <Text style={s.foot}>NA E-Ticket • Secure • Offline-Capable • {new Date().getFullYear()}</Text>

      <SetPinModal
        visible={showSetPin}
        onSubmit={savePin}
        onCancel={() => setShowSetPin(false)}
        error={pinError}
        title={pinSet ? 'Change offline PIN' : 'Set offline PIN'}
      />
    </ScrollView>
  );
}
const s = StyleSheet.create({
  container: { flex:1, backgroundColor:'#fff' },
  h1: { fontSize:22, fontWeight:'800', color:'#0b3d0b' },
  sub: { fontSize:11, color:'#64748b', marginTop:4 },
  offlineBanner: { backgroundColor:'#fef3c7', borderWidth:1, borderColor:'#f59e0b', borderRadius:12, padding:10, marginTop:12 },
  offlineTxt: { fontSize:11, color:'#92400e', fontWeight:'700' },
  grid: { flexDirection:'row', flexWrap:'wrap', gap:12, marginTop:16 },
  card: { width:'47%', borderRadius:14, padding:16, alignItems:'center' },
  num: { fontSize:28, fontWeight:'800' },
  lbl: { fontSize:11, color:'#475569', marginTop:4, textAlign:'center' },
  info: { backgroundColor:'#f8fafc', borderRadius:14, padding:16, marginTop:16, borderWidth:1, borderColor:'#e2e8f0' },
  infoTitle: { fontWeight:'800', color:'#0b3d0b' },
  infoTxt: { fontSize:12, color:'#334155', marginTop:6, lineHeight:18 },
  empty: { fontSize:12, color:'#64748b', marginTop:12, fontStyle:'italic' },
  room: { borderWidth:1, borderColor:'#e2e8f0', borderRadius:12, backgroundColor:'#fff', marginTop:12, overflow:'hidden' },
  roomHead: { flexDirection:'row', alignItems:'center', gap:12, padding:12 },
  roomIcon: { fontSize:22 },
  roomCopy: { flex:1 },
  roomName: { fontSize:14, fontWeight:'800', color:'#0b3d0b' },
  roomDesc: { fontSize:11, color:'#64748b', marginTop:2 },
  roomToggle: { fontSize:18, color:'#0b3d0b', fontWeight:'800' },
  menuItem: { flexDirection:'row', alignItems:'center', gap:10, paddingVertical:10, paddingHorizontal:16, borderTopWidth:1, borderTopColor:'#e2e8f0' },
  menuIcon: { width:18, textAlign:'center', fontSize:13 },
  menuLabel: { fontSize:12, color:'#334155', fontWeight:'600' },
  notification: { borderBottomWidth:1, borderBottomColor:'#e2e8f0', paddingVertical:8 },
  notificationModule: { fontSize:10, fontWeight:'800', color:'#0b3d0b', textTransform:'uppercase' },
  notificationTitle: { fontSize:12, color:'#334155', marginTop:2 },
  syncBtn: { backgroundColor:'#0b3d0b', borderRadius:10, padding:12, alignItems:'center', marginTop:12 },
  syncTxt: { color:'#fff', fontWeight:'700' },
  pinBtn: { borderWidth:1, borderColor:'#0b3d0b', borderRadius:10, padding:12, alignItems:'center', marginTop:8 },
  pinTxt: { color:'#0b3d0b', fontWeight:'700' },
  forget: { marginTop:10, alignItems:'center' },
  forgetTxt: { color:'#dc2626', fontSize:11, fontWeight:'600' },
  logout: { marginTop:24, borderWidth:1, borderColor:'#fecaca', borderRadius:10, padding:14, alignItems:'center' },
  logoutTxt: { color:'#dc2626', fontWeight:'700' },
  foot: { textAlign:'center', fontSize:10, color:'#94a3b8', marginTop:16 },
});
