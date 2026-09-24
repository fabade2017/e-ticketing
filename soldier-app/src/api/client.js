import axios from 'axios';
import * as SecureStore from 'expo-secure-store';
import AsyncStorage from '@react-native-async-storage/async-storage';

// Backend host - update if Mac IP changes (run ipconfig getifaddr en0)
export const API_URL = 'http://10.97.186.174:8004/api';

const client = axios.create({
  baseURL: API_URL,
  headers: { Accept: 'application/json', 'Content-Type': 'application/json' },
  timeout: 15000,
});

// Token helpers - SecureStore for military sensitivity, fallback to AsyncStorage
export async function getToken() {
  try {
    return await SecureStore.getItemAsync('na_token');
  } catch {
    return await AsyncStorage.getItem('na_token');
  }
}
export async function setToken(token) {
  try {
    await SecureStore.setItemAsync('na_token', token);
  } catch {
    await AsyncStorage.setItem('na_token', token);
  }
}
export async function clearToken() {
  try {
    await SecureStore.deleteItemAsync('na_token');
  } catch {}
  await AsyncStorage.removeItem('na_token');
}

client.interceptors.request.use(async (config) => {
  const token = await getToken();
  if (token) config.headers.Authorization = `Bearer ${token}`;
  return config;
});

// Offline queue helpers - for border areas with no signal
const QUEUE_KEY = 'na_offline_tickets';
export async function queueTicket(ticket) {
  const q = JSON.parse((await AsyncStorage.getItem(QUEUE_KEY)) || '[]');
  q.push({ ...ticket, offline_created: true, queued_at: new Date().toISOString() });
  await AsyncStorage.setItem(QUEUE_KEY, JSON.stringify(q));
  return q.length;
}
export async function getQueue() {
  return JSON.parse((await AsyncStorage.getItem(QUEUE_KEY)) || '[]');
}
export async function clearQueue() {
  await AsyncStorage.removeItem(QUEUE_KEY);
}
export async function syncQueue() {
  const q = await getQueue();
  if (q.length === 0) return { synced: 0, failed: 0 };
  let synced = 0, failed = 0;
  const remaining = [];
  for (const t of q) {
    try {
      await client.post('/tickets', t);
      synced++;
    } catch (e) {
      // keep 4xx validation errors for manual fix, retry only network errors
      const status = e.response?.status;
      if (status >= 400 && status < 500 && status !== 429) {
        failed++;
        remaining.push({ ...t, _error: e.response?.data?.message || 'Validation failed' });
      } else {
        remaining.push(t);
      }
    }
  }
  await AsyncStorage.setItem(QUEUE_KEY, JSON.stringify(remaining));
  return { synced, failed, remaining: remaining.length };
}

export default client;
