import React, { createContext, useContext, useState, useEffect } from 'react';
import client, { getToken, setToken, clearToken } from '../api/client';

const AuthContext = createContext(null);

export function AuthProvider({ children }) {
  const [user, setUser] = useState(null);
  const [loading, setLoading] = useState(true);

  const fetchMe = async () => {
    try {
      const { data } = await client.get('/user');
      setUser(data);
      return data;
    } catch {
      setUser(null);
      return null;
    }
  };

  useEffect(() => {
    (async () => {
      const token = await getToken();
      if (token) await fetchMe();
      setLoading(false);
    })();
  }, []);

  const login = async (service_number, password, device_name = 'soldier-phone') => {
    const { data } = await client.post('/login', { service_number, password, device_name });
    await setToken(data.token);
    setUser(data.user);
    return data;
  };

  const faceLogin = async (service_number, embedding, device_name = 'soldier-face') => {
    const { data } = await client.post('/face/verify', { service_number, embedding, device_name });
    await setToken(data.token);
    setUser(data.user);
    return data;
  };

  const logout = async () => {
    try { await client.post('/logout'); } catch {}
    await clearToken();
    setUser(null);
  };

  return (
    <AuthContext.Provider value={{ user, loading, login, faceLogin, logout, fetchMe, isSoldier: user?.roles?.includes('Soldier') }}>
      {children}
    </AuthContext.Provider>
  );
}

export const useAuth = () => {
  const ctx = useContext(AuthContext);
  if (!ctx) throw new Error('useAuth must be inside AuthProvider');
  return ctx;
};
