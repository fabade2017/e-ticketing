import React, { createContext, useContext, useState, useCallback, useRef } from 'react';
import { View, Text, ActivityIndicator, StyleSheet, Modal } from 'react-native';
import { bindLoading } from '../api/client';

const LoadingContext = createContext(null);

let externalShow = null;
let externalHide = null;

export function setGlobalLoadingHandlers(show, hide) {
  externalShow = show;
  externalHide = hide;
  bindLoading(show, hide);
}
export function showGlobalLoader(msg) { if (externalShow) externalShow(msg); }
export function hideGlobalLoader() { if (externalHide) externalHide(); }

export function LoadingProvider({ children }) {
  const [visible, setVisible] = useState(false);
  const [message, setMessage] = useState('Processing, please wait...');
  const countRef = useRef(0);

  const show = useCallback((msg) => {
    countRef.current += 1;
    if (msg) setMessage(msg);
    setVisible(true);
  }, []);
  const hide = useCallback(() => {
    countRef.current = Math.max(0, countRef.current - 1);
    if (countRef.current === 0) setVisible(false);
  }, []);
  const forceHide = useCallback(() => {
    countRef.current = 0;
    setVisible(false);
  }, []);

  // expose for client.js interceptors
  React.useEffect(() => {
    setGlobalLoadingHandlers(show, hide);
    return () => setGlobalLoadingHandlers(null, null);
  }, [show, hide]);

  return (
    <LoadingContext.Provider value={{ show, hide, forceHide, visible }}>
      {children}
      <Modal visible={visible} transparent animationType="fade" statusBarTranslucent>
        <View style={styles.overlay} pointerEvents="auto">
          <View style={styles.box}>
            <ActivityIndicator size="large" color="#0b3d0b" />
            <Text style={styles.title}>{message}</Text>
            <Text style={styles.sub}>Please wait — do not close the app</Text>
            <View style={styles.dots}>
              <View style={[styles.dot, { opacity: 0.9 }]} />
              <View style={[styles.dot, { opacity: 0.6 }]} />
              <View style={[styles.dot, { opacity: 0.3 }]} />
            </View>
          </View>
        </View>
      </Modal>
    </LoadingContext.Provider>
  );
}

export const useLoading = () => {
  const ctx = useContext(LoadingContext);
  if (!ctx) throw new Error('useLoading must be inside LoadingProvider');
  return ctx;
};

const styles = StyleSheet.create({
  overlay: { flex: 1, backgroundColor: 'rgba(0,0,0,0.45)', justifyContent: 'center', alignItems: 'center', padding: 24 },
  box: { backgroundColor: '#fff', borderRadius: 20, paddingVertical: 28, paddingHorizontal: 32, alignItems: 'center', minWidth: 260, elevation: 8, shadowColor: '#000', shadowOpacity: 0.2, shadowRadius: 12, shadowOffset: { width: 0, height: 6 }, borderWidth: 1, borderColor: '#e2e8f0' },
  title: { marginTop: 16, fontWeight: '800', fontSize: 14, color: '#0f172a', textAlign: 'center' },
  sub: { marginTop: 6, fontSize: 11, color: '#64748b', textAlign: 'center' },
  dots: { flexDirection: 'row', gap: 6, marginTop: 14 },
  dot: { width: 8, height: 8, borderRadius: 4, backgroundColor: '#0b3d0b' },
});
