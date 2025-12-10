import React, { createContext, useState, useEffect, useContext } from 'react';
import authService from '../services/authService';
import PropTypes from 'prop-types';

const AuthContext = createContext({});

export const AuthProvider = ({ children }) => {
  const [user, setUser] = useState(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const checkLoginStatus = async () => {
      try {
        // Agora verificamos a sessão diretamente com o backend (cookie)
        // em vez de procurar token no localStorage
        const status = await authService.checkAuth();
        
        if (status.isAuthenticated) {
          const userProfile = await authService.getProfile();
          setUser({ ...userProfile, role: status.role });
        } else {
          setUser(null);
        }
      } catch (error) {
        setUser(null);
      } finally {
        setLoading(false);
      }
    };

    checkLoginStatus();
  }, []);

  const login = async (credentials, isAdmin = false) => {
    const data = await authService.login(credentials, isAdmin);
    // Não precisamos mais salvar token manualmente
    const userProfile = await authService.getProfile(); 
    setUser({ ...userProfile, role: data.role });
    return data;
  };

  const register = async (userData) => {
    const data = await authService.register(userData);
    // Login automático após registro
    const userProfile = await authService.getProfile();
    setUser({ ...userProfile, role: data.role });
    return data;
  };

  const logout = async () => {
    try {
      await authService.logout();
      window.location.href = '/';
    } catch (e) {
      console.error(e);
    } finally {
      setUser(null);
    }
  };

  return (
    <AuthContext.Provider value={{ user, login, register, logout, loading, isAuthenticated: !!user }}>
      {!loading && children}
    </AuthContext.Provider>
  );
};

AuthProvider.propTypes = {
  children: PropTypes.node.isRequired
};

export const useAuth = () => useContext(AuthContext);
export default AuthContext;