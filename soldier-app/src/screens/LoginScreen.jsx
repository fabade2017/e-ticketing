import React, { useState } from 'react';
import { View, Text, TextInput, TouchableOpacity, Alert, ActivityIndicator, StyleSheet } from 'react-native';
import { useAuth } from '../context/AuthContext';

export default function LoginScreen() {
  const { login } = useAuth();
  const [serviceNumber, setServiceNumber] = useState('NA/SL/003');
  const [password, setPassword] = useState('Soldier@123');
  const [loading, setLoading] = useState(false);

  const submit = async () => {
    if (!serviceNumber || !password) return Alert.alert('Error', 'Service Number + Password required');
    setLoading(true);
    try {
      await login(serviceNumber.trim(), password);
    } catch (e) {
      const msg = e.response?.data?.message || e.message || 'Login failed';
      Alert.alert('Login failed', msg);
    } finally { setLoading(false); }
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
        <Text style={s.hint}>Inactive accounts blocked (is_active). Contact Unit Admin.</Text>
        <Text style={s.hint}>Test: NA/SL/003 / Soldier@123 • Admin: NA/SA/001 / Admin@123</Text>
      </View>
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
  hint: { fontSize:11, color:'#94a3b8', marginTop:10, textAlign:'center' }
});
