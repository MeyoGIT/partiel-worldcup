import { createContext, useContext, useState, useEffect } from 'react';
import { getMe, login as apiLogin, logout as apiLogout } from '../services/api';

const AuthContext = createContext(null);

export const AuthProvider = ({ children }) => {
  const [user, setUser] = useState(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    checkAuth();
  }, []);

  const checkAuth = async () => {
    try {
      const response = await getMe();
      if (response.data.authenticated) {
        setUser(response.data.user);
      }
    } catch (error) {
      setUser(null);
    } finally {
      setLoading(false);
    }
  };

  const login = async (email, password) => {
    const response = await apiLogin(email, password);
    if (response.data.success) {
      setUser(response.data.user);
      return true;
    }
    return false;
  };

  const logout = async () => {
    try {
      await apiLogout();
    } catch (error) {
      // Ignore logout errors
    }
    setUser(null);
  };

  const isAdmin = () => {
    return user?.roles?.includes('ROLE_ADMIN');
  };

  return (
    <AuthContext.Provider value={{ user, loading, login, logout, isAdmin, checkAuth }}>
      {children}
    </AuthContext.Provider>
  );
};

export const useAuth = () => {
  const context = useContext(AuthContext);
  if (!context) {
    throw new Error('useAuth must be used within an AuthProvider');
  }
  return context;
};

export default AuthContext;
