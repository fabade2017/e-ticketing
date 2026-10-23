import React, { createContext, useContext, useState, useEffect, useCallback } from 'react';
import client, {
  getToken, setToken, clearToken,
  isNetworkError, setCachedUser, getCachedUser, clearCachedUser,
  hasOfflinePin, setOfflinePin, verifyOfflinePin, clearOfflinePin,
  clearAnonymousTokens,
} from '../api/client';

const AuthContext = createContext(null);

export function AuthProvider({ children }) {
  const [user, setUser] = useState(null);
  const [offline, setOffline] = useState(false);
  const [pinSet, setPinSet] = useState(false);
  const [loading, setLoading] = useState(true);

  const refreshPinState = useCallback(async () => {
    const has = await hasOfflinePin();
    setPinSet(has);
    return has;
  }, []);

  const fetchMe = async () => {
    try {
      const { data } = await client.get('/user', { __silent: true });
      setUser(data);
      setOffline(false);
      await setCachedUser(data);
      return data;
    } catch (e) {
      // No signal but we have a cached profile + PIN -> stay logged in for offline work
      if (isNetworkError(e) && (await hasOfflinePin())) {
        const cached = await getCachedUser();
        if (cached) {
          setUser(cached);
          setOffline(true);
          return cached;
        }
      }
      setUser(null);
      setOffline(false);
      return null;
    }
  };

  useEffect(() => {
    (async () => {
      await refreshPinState();
      const token = await getToken();
      if (token) await fetchMe();
      setLoading(false);
    })();
  }, [refreshPinState]);

  // Online login with service number + password
  const login = async (service_number, password, device_name = 'soldier-phone') => {
    const { data } = await client.post('/login', { service_number, password, device_name });
    await setToken(data.token);
    await setCachedUser(data.user);
    setUser(data.user);
    setOffline(false);
    return data;
  };

  // Offline (or fallback) login with the device PIN -> restores the cached profile
  const loginWithPin = async (pin) => {
    const result = await verifyOfflinePin(pin);
    if (!result.ok) {
      const err = new Error('pin');
      err.pinResult = result;
      throw err;
    }
    const cached = await getCachedUser();
    if (!cached) throw new Error('No offline profile stored on this device. Log in online once first.');
    setUser(cached);
    setOffline(true);
    return cached;
  };

  const setPin = async (pin) => {
    await setOfflinePin(pin);
    await refreshPinState();
  };

  const verifyPin = (pin) => verifyOfflinePin(pin);

  const hasPin = () => hasOfflinePin();

  const logout = async () => {
    try { await client.post('/logout'); } catch {}
    await clearToken();
    await clearAnonymousTokens();
    setUser(null);
    setOffline(false);
  };

  // Wipe the offline credential + cached profile from this device
  const forgetOffline = async () => {
    await clearOfflinePin();
    await clearCachedUser();
    await refreshPinState();
  };

  return (
    <AuthContext.Provider value={{
      user, loading, offline, pinSet,
      login, loginWithPin, logout, fetchMe,
      setPin, verifyPin, hasPin, refreshPinState, forgetOffline,
      isSoldier: user?.roles?.includes('Soldier'),
    }}>
      {children}
    </AuthContext.Provider>
  );
}

export const useAuth = () => {
  const ctx = useContext(AuthContext);
  if (!ctx) throw new Error('useAuth must be inside AuthProvider');
  return ctx;
};
