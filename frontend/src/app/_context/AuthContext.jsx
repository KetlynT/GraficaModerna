'use client'

import { createContext, useState, useEffect, useContext } from 'react';
import { useRouter } from 'next/navigation';
import * as authService from '@/app/(website)/login/_services/authService';
import toast from 'react-hot-toast';

const AuthContext = createContext({});

export const AuthProvider = ({ children }) => {
  const [user, setUser] = useState(null);
  const [loading, setLoading] = useState(true);
  const router = useRouter();

  useEffect(() => {
    const checkLoginStatus = async () => {
      try {
        const status = await authService.checkAuth();
        
        if (status && status.isAuthenticated) {
          try {
             const userProfile = await authService.getProfile();
             setUser({ ...userProfile, role: status.role });
          } catch (profileError) {
             console.error("Sessão válida, mas falha ao carregar perfil:", profileError);
             setUser(null);
          }
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
    try {
      const data = await authService.login(credentials, isAdmin);
      
      if (data) {
          const userProfile = await authService.getProfile(); 
          setUser({ ...userProfile, role: data.role });
          
          toast.success(`Bem-vindo, ${userProfile.firstName || 'Usuário'}!`);
          router.refresh();
          return data;
      }
    } catch (error) {
      console.error("Login falhou:", error);
      throw error; 
    }
  };

  const register = async (userData) => {
    try {
      const data = await authService.register(userData);
      if (data) {
         const userProfile = await authService.getProfile();
         setUser({ ...userProfile, role: data.role });
         toast.success("Conta criada com sucesso!");
         router.refresh();
         return data;
      }
    } catch (error) {
      console.error("Cadastro falhou:", error);
      throw error;
    }
  };

  const logout = async () => {
    try {
      await authService.logout();
      setUser(null);
      toast.success("Você saiu com sucesso.");
      router.push('/');
      router.refresh(); 
    } catch (e) {
      console.error("Erro ao sair:", e);
      setUser(null);
      router.push('/');
    }
  };

  return (
    <AuthContext.Provider value={{ user, login, register, logout, loading, isAuthenticated: !!user }}>
      {!loading && children}
    </AuthContext.Provider>
  );
};

export const useAuth = () => useContext(AuthContext);
export default AuthContext;