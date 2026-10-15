import axios from 'axios';
import * as SecureStore from 'expo-secure-store';
import * as Crypto from 'expo-crypto';
import AsyncStorage from '@react-native-async-storage/async-storage';

// Render live backend - works on any network
export const API_URL = 'https://na-e-ticketing.onrender.com/api';

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

// --- secure storage helpers (SecureStore with AsyncStorage fallback) ---
async function secureGet(key) {
  try { return await SecureStore.getItemAsync(key); } catch { return await AsyncStorage.getItem(key); }
}
async function secureSet(key, val) {
  try { await SecureStore.setItemAsync(key, val); } catch { await AsyncStorage.setItem(key, val); }
}
async function secureDelete(key) {
  try { await SecureStore.deleteItemAsync(key); } catch {}
  await AsyncStorage.removeItem(key);
}

// --- Offline PIN: only a salted SHA-256 hash is ever stored, never the PIN ---
const PIN_HASH_KEY = 'na_offline_pin_hash';
const PIN_SALT_KEY = 'na_offline_pin_salt';
const PIN_FAIL_KEY = 'na_offline_pin_fails';
const PIN_LOCK_KEY = 'na_offline_pin_lock_until';
const MAX_PIN_ATTEMPTS = 5;
const PIN_LOCK_MS = 30000;

async function hashPin(pin, salt) {
  return Crypto.digestStringAsync(Crypto.CryptoDigestAlgorithm.SHA256, `${salt}:${pin}`);
}

export async function hasOfflinePin() {
  return !!(await secureGet(PIN_HASH_KEY));
}

export async function setOfflinePin(pin) {
  const salt = Crypto.randomUUID();
  await secureSet(PIN_SALT_KEY, salt);
  await secureSet(PIN_HASH_KEY, await hashPin(pin, salt));
  await secureDelete(PIN_FAIL_KEY);
  await secureDelete(PIN_LOCK_KEY);
}

export async function clearOfflinePin() {
  await secureDelete(PIN_HASH_KEY);
  await secureDelete(PIN_SALT_KEY);
  await secureDelete(PIN_FAIL_KEY);
  await secureDelete(PIN_LOCK_KEY);
}

// Returns { ok } on match, else { ok:false, reason|attemptsLeft|locked|retryInMs }
export async function verifyOfflinePin(pin) {
  const lockUntil = parseInt((await secureGet(PIN_LOCK_KEY)) || '0', 10);
  if (lockUntil && Date.now() < lockUntil) {
    return { ok: false, locked: true, retryInMs: lockUntil - Date.now() };
  }
  const salt = await secureGet(PIN_SALT_KEY);
  const hash = await secureGet(PIN_HASH_KEY);
  if (!salt || !hash) return { ok: false, reason: 'no_pin' };
  if ((await hashPin(pin, salt)) === hash) {
    await secureDelete(PIN_FAIL_KEY);
    await secureDelete(PIN_LOCK_KEY);
    return { ok: true };
  }
  const fails = parseInt((await secureGet(PIN_FAIL_KEY)) || '0', 10) + 1;
  if (fails >= MAX_PIN_ATTEMPTS) {
    await secureSet(PIN_LOCK_KEY, String(Date.now() + PIN_LOCK_MS));
    await secureSet(PIN_FAIL_KEY, '0');
    return { ok: false, locked: true, retryInMs: PIN_LOCK_MS };
  }
  await secureSet(PIN_FAIL_KEY, String(fails));
  return { ok: false, attemptsLeft: MAX_PIN_ATTEMPTS - fails };
}

// --- Cached profile so a PIN login works with no network ---
const USER_KEY = 'na_cached_user';
export async function setCachedUser(user) { await AsyncStorage.setItem(USER_KEY, JSON.stringify(user)); }
export async function getCachedUser() {
  try { const v = await AsyncStorage.getItem(USER_KEY); return v ? JSON.parse(v) : null; } catch { return null; }
}
export async function clearCachedUser() { await AsyncStorage.removeItem(USER_KEY); }

export function isNetworkError(e) { return !e?.response; }

let showLoader = null;
let hideLoader = null;
export function bindLoading(show, hide) { showLoader = show; hideLoader = hide; }

client.interceptors.request.use(async (config) => {
  const token = await getToken();
  if (token) config.headers.Authorization = `Bearer ${token}`;
  // show rolling blocker for every network op (auto-hides on response)
  if (!config.__silent && showLoader) showLoader(config.__loadingMsg || 'Processing, please wait...');
  return config;
});

client.interceptors.response.use(
  (res) => { if (!res.config.__silent && hideLoader) hideLoader(); return res; },
  (err) => { if (!err.config?.__silent && hideLoader) hideLoader(); return Promise.reject(err); }
);

// Offline queue helpers - for border areas with no signal
const QUEUE_KEY = 'na_offline_tickets';
export async function queueTicket(ticket) {
  const q = JSON.parse((await AsyncStorage.getItem(QUEUE_KEY)) || '[]');
  q.push({ client_uuid: ticket.client_uuid || Crypto.randomUUID(), ...ticket, offline_created: true, queued_at: new Date().toISOString() });
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
      await client.post('/tickets', t, { __silent: true });
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
