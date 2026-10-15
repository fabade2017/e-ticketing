import React, { useState, useEffect } from 'react';
import { Modal, View, Text, TextInput, TouchableOpacity, StyleSheet } from 'react-native';

export default function SetPinModal({ visible, onSubmit, onCancel, error, submitting, title = 'Set offline PIN', subtitle = 'Choose a 4-digit PIN. You will use it to log in with no network and to confirm syncing.' }) {
  const [pin, setPin] = useState('');
  const [confirm, setConfirm] = useState('');

  useEffect(() => { if (visible) { setPin(''); setConfirm(''); } }, [visible]);

  const valid = pin.length === 4 && pin === confirm;
  const mismatch = confirm.length === 4 && pin !== confirm;

  return (
    <Modal visible={visible} transparent animationType="fade" onRequestClose={onCancel}>
      <View style={s.overlay}>
        <View style={s.box}>
          <Text style={s.title}>{title}</Text>
          {!!subtitle && <Text style={s.subtitle}>{subtitle}</Text>}
          <TextInput
            style={s.input}
            value={pin}
            onChangeText={v => setPin(v.replace(/[^0-9]/g, '').slice(0, 4))}
            keyboardType="number-pad"
            secureTextEntry
            maxLength={4}
            placeholder="New PIN"
            placeholderTextColor="#94a3b8"
            autoFocus
            editable={!submitting}
          />
          <TextInput
            style={[s.input, { marginTop:10 }]}
            value={confirm}
            onChangeText={v => setConfirm(v.replace(/[^0-9]/g, '').slice(0, 4))}
            keyboardType="number-pad"
            secureTextEntry
            maxLength={4}
            placeholder="Confirm PIN"
            placeholderTextColor="#94a3b8"
            editable={!submitting}
          />
          {mismatch && <Text style={s.error}>PINs do not match.</Text>}
          {!!error && <Text style={s.error}>{error}</Text>}
          <View style={s.row}>
            <TouchableOpacity style={[s.btn, s.cancel]} onPress={onCancel} disabled={submitting}>
              <Text style={s.cancelTxt}>Cancel</Text>
            </TouchableOpacity>
            <TouchableOpacity
              style={[s.btn, s.ok, !valid && { opacity: 0.45 }]}
              onPress={() => onSubmit(pin)}
              disabled={!valid || submitting}
            >
              <Text style={s.okTxt}>{submitting ? 'Saving…' : 'Save PIN'}</Text>
            </TouchableOpacity>
          </View>
        </View>
      </View>
    </Modal>
  );
}

const s = StyleSheet.create({
  overlay: { flex:1, backgroundColor:'rgba(0,0,0,0.45)', justifyContent:'center', padding:24 },
  box: { backgroundColor:'#fff', borderRadius:16, padding:20 },
  title: { fontWeight:'800', fontSize:16, color:'#0b3d0b' },
  subtitle: { fontSize:12, color:'#64748b', marginTop:6 },
  input: { borderWidth:1, borderColor:'#cbd5e1', borderRadius:12, padding:14, marginTop:14, fontSize:20, letterSpacing:8, textAlign:'center' },
  error: { color:'#dc2626', fontSize:12, marginTop:8 },
  row: { flexDirection:'row', gap:10, marginTop:16 },
  btn: { flex:1, borderRadius:10, padding:14, alignItems:'center' },
  cancel: { borderWidth:1, borderColor:'#cbd5e1' },
  cancelTxt: { color:'#475569', fontWeight:'700' },
  ok: { backgroundColor:'#0b3d0b' },
  okTxt: { color:'#fff', fontWeight:'800' },
});
