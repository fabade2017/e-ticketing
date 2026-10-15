import React, { useState } from 'react';
import { View, Text, TextInput, TouchableOpacity, Alert, ActivityIndicator, StyleSheet } from 'react-native';
import { useAuth } from '../context/AuthContext';
import PinModal from '../components/PinModal';
import SetPinModal from '../components/SetPinModal';

export default function LoginScreen() {
  const { login, loginWithPin, setPin, refreshPinState } = useAuth();
  const [serviceNumber, setServiceNumber] = useState('NA/SL/003');
  const [password, setPassword] = useState('Soldier@123');
  const [loading, setLoading] = useState(false);

  const [showPinLogin, setShowPinLogin] = useState(false);
  const [pinError, setPinError] = useState('');
  const [pinBusy, setPinBusy] = useState(false);

  const [showSetPin, setShowSetPin] = useState(false);
  const [setPinModalError, setSetPinModalError] = useState('');

  const submit = async () => {
    if (!serviceNumber || !password) return Alert.alert('Error', 'Service Number + Password required');
    setLoading(true);
    try {
      await login(serviceNumber.trim(), password);
      const has = await refreshPinState();
      if (!has) { setSetPinModalError(''); setShowSetPin(true); }
    } catch (e) {
      if (!e.response) {
        Alert.alert(
          'No network',
          'Cannot reach the server. If you have an offline PIN on this device, log in with it.',
          [
            { text: 'Use PIN', onPress: () => { setPinError(''); setShowPinLogin(true); } },
            { text: 'Cancel', style: 'cancel' },
          ]
        );
      } else {
        Alert.alert('Login failed', e.response?.data?.message || e.message || 'Login failed');
      }
    } finally {
      setLoading(false);
    }
  };

  const submitPin = async (pin) => {
    setPinBusy(true);
    try {
      await loginWithPin(pin);
      setShowPinLogin(false);
    } catch (e) {
      const r = e.pinResult;
      if (r) {
        setPinError(r.locked
          ? `Too many wrong attempts. Try again in ${Math.ceil((r.retryInMs || 0) / 1000)}s.`
          : `Wrong PIN. ${r.attemptsLeft ?? 0} attempt(s) left.`);
      } else if (e.message === 'pin') {
        setPinError('No offline PIN is set on this device.');
      } else {
        setPinError(e.message || 'PIN login failed.');
      }
    } finally {
      setPinBusy(false);
    }
  };

  const submitSetPin = async (pin) => {
    try {
      await setPin(pin);
      setShowSetPin(false);
      Alert.alert('PIN set', 'Your 4-digit offline PIN is ready. Use it to log in with no network and to confirm syncing.');
    } catch (e) {
      setSetPinModalError(e.message || 'Could not set PIN.');
    }
  };

  return (
    <View style={s.container}>
      <View style={s.card}>
        <Text style={s.title}>NA E-Ticket</Text>
        <Text style={s.subtitle}>Soldier Login • Service Number + Password</Text>
        <TextInput style={s.input} placeholder="Service Number e.g. NA/SL/003" value={serviceNumber} onChangeText={setServiceNumber} autoCapitalize="characters" />
        <TextInput style={s.input} placeholder="Password" value={password} onChangeText={setPassword} secureTextEntry />
        <TouchableOpacity style={s.btn} onPress={submit} disabled={loading}>
          {loading ? <ActivityIndicator color="#fff" /> : <Text style={s.btnText}>Login</Text>}
        </TouchableOpacity>

        <TouchableOpacity style={s.btnAlt} onPress={() => { setPinError(''); setShowPinLogin(true); }} disabled={loading}>
          <Text style={s.btnAltText}>Login with PIN (offline)</Text>
        </TouchableOpacity>

        <Text style={s.hint}>No signal in the field? Use your 4-digit PIN to log in and create tickets offline.</Text>
        <Text style={s.hint}>Inactive accounts blocked (is_active). Contact Unit Admin.</Text>
      </View>

      <PinModal
        visible={showPinLogin}
        title="Offline login"
        subtitle="Enter the 4-digit PIN you set on this device."
        onSubmit={submitPin}
        onCancel={() => setShowPinLogin(false)}
        error={pinError}
        submitting={pinBusy}
      />

      <SetPinModal
        visible={showSetPin}
        onSubmit={submitSetPin}
        onCancel={() => setShowSetPin(false)}
        error={setPinModalError}
        title="Set your offline PIN"
      />
    </View>
  );
}

const s = StyleSheet.create({
  container: { flex:1, backgroundColor:'#0b3d0b', justifyContent:'center', padding:16 },
  card: { backgroundColor:'#fff', borderRadius:16, padding:24, elevation:4 },
  title: { fontSize:24, fontWeight:'800', color:'#0b3d0b' },
  subtitle: { fontSize:12, color:'#64748b', marginBottom:16 },
  input: { borderWidth:1, borderColor:'#cbd5e1', borderRadius:10, padding:12, marginBottom:12 },
  btn: { backgroundColor:'#0b3d0b', borderRadius:10, padding:14, alignItems:'center', marginTop:4 },
  btnText: { color:'#fff', fontWeight:'700' },
  btnAlt: { borderWidth:1, borderColor:'#0b3d0b', borderRadius:10, padding:14, alignItems:'center', marginTop:10 },
  btnAltText: { color:'#0b3d0b', fontWeight:'700' },
  hint: { fontSize:11, color:'#94a3b8', marginTop:10, textAlign:'center' },
});
