import React, { useEffect, useState, useCallback } from 'react';
import { TouchableOpacity, Text, Alert } from 'react-native';
import { getQueue, syncQueue } from '../api/client';
import { useAuth } from '../context/AuthContext';
import PinModal from './PinModal';

// Sync requires: an online session (service number + password login) AND the device PIN.
export default function SyncButton({ style, textStyle, onDone }) {
  const { user, offline, verifyPin } = useAuth();
  const [queueLen, setQueueLen] = useState(0);
  const [showPin, setShowPin] = useState(false);
  const [pinError, setPinError] = useState('');
  const [submitting, setSubmitting] = useState(false);

  const refresh = useCallback(async () => { setQueueLen((await getQueue()).length); }, []);
  useEffect(() => { refresh(); const id = setInterval(refresh, 8000); return () => clearInterval(id); }, [refresh]);

  const start = () => {
    if (!user) return;
    if (queueLen === 0) return Alert.alert('Nothing to sync', 'Your offline queue is empty.');
    if (offline) {
      return Alert.alert(
        'Log in online first',
        'You are in an offline session. Connect to the internet and log in with your service number + password, then sync.'
      );
    }
    setPinError('');
    setShowPin(true);
  };

  const confirm = async (pin) => {
    setSubmitting(true);
    try {
      const r = await verifyPin(pin);
      if (!r.ok) {
        setPinError(r.locked
          ? `Too many wrong attempts. Try again in ${Math.ceil((r.retryInMs || 0) / 1000)}s.`
          : `Wrong PIN. ${r.attemptsLeft ?? 0} attempt(s) left.`);
        return;
      }
      setShowPin(false);
      const res = await syncQueue();
      await refresh();
      onDone?.();
      Alert.alert('Sync complete', `Synced ${res.synced}, failed ${res.failed}, remaining ${res.remaining ?? 0}.`);
    } catch (e) {
      Alert.alert('Sync failed', e?.message || 'Could not sync. Check your connection.');
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <>
      <TouchableOpacity style={style} onPress={start}>
        <Text style={textStyle}>Sync Offline Queue ({queueLen})</Text>
      </TouchableOpacity>
      <PinModal
        visible={showPin}
        title="Confirm PIN to sync"
        subtitle="Enter the 4-digit PIN you set. Your queued tickets will then be pushed online."
        onSubmit={confirm}
        onCancel={() => setShowPin(false)}
        error={pinError}
        submitting={submitting}
      />
    </>
  );
}
