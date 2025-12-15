'use client'

import { useEffect, useState, Suspense } from 'react';
import { useSearchParams, useRouter } from 'next/navigation';
import { CheckCircle, XCircle, Loader } from 'lucide-react';
import authService from '@/app/(website)/login/_services/authService';

function ConfirmEmailContent() {
  const searchParams = useSearchParams();
  const [status, setStatus] = useState('loading');
  const router = useRouter();

  useEffect(() => {
    const confirm = async () => {
      const userId = searchParams.get('userid');
      const token = searchParams.get('token');

      if (!userId || !token) {
        setStatus('error');
        return;
      }

      try {
        await authService.confirmEmail(userId, token);
        setStatus('success');
        setTimeout(() => router.replace('/login'), 5000);
      } catch (error) {
        console.error(error);
        setStatus('error');
      }
    };

    confirm();
  }, [searchParams, router]);

  return (
    <div className="min-h-screen flex items-center justify-center bg-gray-50 p-4">
      <div className="max-w-md w-full bg-white rounded-2xl shadow-xl p-8 text-center">
        {status === 'loading' && (
          <div className="flex flex-col items-center">
            <Loader size={48} className="text-primary animate-spin mb-4" />
            <h2 className="text-xl font-bold text-gray-800">Verificando seu e-mail...</h2>
          </div>
        )}

        {status === 'success' && (
          <div className="flex flex-col items-center">
            <CheckCircle size={64} className="text-green-500 mb-4" />
            <h2 className="text-2xl font-bold text-gray-800 mb-2">E-mail Confirmado!</h2>
            <p className="text-gray-600">Sua conta foi ativada com sucesso.</p>
            <p className="text-sm text-gray-400 mt-4">Você será redirecionado para o login em instantes...</p>
          </div>
        )}

        {status === 'error' && (
          <div className="flex flex-col items-center">
            <XCircle size={64} className="text-red-500 mb-4" />
            <h2 className="text-2xl font-bold text-gray-800 mb-2">Falha na Confirmação</h2>
            <p className="text-gray-600">O link é inválido ou já expirou.</p>
            <button 
              onClick={() => router.replace('/')}
              className="mt-6 px-6 py-2 bg-gray-100 hover:bg-gray-200 rounded-lg font-bold text-gray-700 transition-colors"
            >
              Voltar ao Início
            </button>
          </div>
        )}
      </div>
    </div>
  );
};

export default function ConfirmEmail() {
    return (
        <Suspense fallback={<div>Carregando...</div>}>
            <ConfirmEmailContent />
        </Suspense>
    );
}