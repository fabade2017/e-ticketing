import React, { useState, useEffect } from 'react';
import { Modal, View, Text, TextInput, TouchableOpacity, StyleSheet } from 'react-native';

export default function PinModal({ visible, title = 'Enter PIN', subtitle, onSubmit, onCancel, error, submitting }) {
  const [pin, setPin] = useState('');

  useEffect(() => { if (visible) setPin(''); }, [visible]);

  const done = pin.length === 4;

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
            placeholder="••••"
            placeholderTextColor="#94a3b8"
            autoFocus
            editable={!submitting}
          />
          {!!error && <Text style={s.error}>{error}</Text>}
          <View style={s.row}>
            <TouchableOpacity style={[s.btn, s.cancel]} onPress={onCancel} disabled={submitting}>
              <Text style={s.cancelTxt}>Cancel</Text>
            </TouchableOpacity>
            <TouchableOpacity
              style={[s.btn, s.ok, !done && { opacity: 0.45 }]}
              onPress={() => onSubmit(pin)}
              disabled={!done || submitting}
            >
              <Text style={s.okTxt}>{submitting ? 'Please wait…' : 'Confirm'}</Text>
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
  input: { borderWidth:1, borderColor:'#cbd5e1', borderRadius:12, padding:14, marginTop:14, fontSize:22, letterSpacing:10, textAlign:'center' },
  error: { color:'#dc2626', fontSize:12, marginTop:8 },
  row: { flexDirection:'row', gap:10, marginTop:16 },
  btn: { flex:1, borderRadius:10, padding:14, alignItems:'center' },
  cancel: { borderWidth:1, borderColor:'#cbd5e1' },
  cancelTxt: { color:'#475569', fontWeight:'700' },
  ok: { backgroundColor:'#0b3d0b' },
  okTxt: { color:'#fff', fontWeight:'800' },
});
