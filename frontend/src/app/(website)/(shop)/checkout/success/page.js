'use client'

import { useEffect, useState, useRef, Suspense } from 'react';
import { useRouter, useSearchParams } from 'next/navigation';
import { CheckCircle, Package, ArrowRight, AlertTriangle, Loader } from 'lucide-react';
import confetti from 'canvas-confetti';
import { Button } from '@/app/_components/ui/Button';
import { PaymentService } from '@/app/(website)/(shop)/_services/paymentService';

function SuccessContent() {
  const searchParams = useSearchParams();
  const router = useRouter();
  const orderId = searchParams.get('order_id'); 

  const [status, setStatus] = useState(orderId ? 'verifying' : 'issue');
  const processingRef = useRef(false);

  const triggerConfetti = () => {
      confetti({ particleCount: 150, spread: 70, origin: { y: 0.6 } });
  };

  useEffect(() => {
    if (processingRef.current || !orderId) return;
    processingRef.current = true;

    let attempts = 0;
    const maxAttempts = 5;
    let timeoutId;

    const checkStatus = async () => {
      try {
        const orderData = await PaymentService.getPaymentStatus(orderId);
        
        if (orderData.status === 'Pago' || orderData.status === 'Paid') {
          setStatus('success');
          triggerConfetti();
        } 
        else if (orderData.status === 'Cancelado' || orderData.status === 'Falha') {
          setStatus('issue'); 
        } 
        else {
          if (attempts < maxAttempts) {
            attempts++;
            timeoutId = setTimeout(checkStatus, 3000);
          } else {
            setStatus('processing');
          }
        }
      } catch (error) {
        console.error("Erro na verificação:", error);
        if (attempts < maxAttempts) {
            attempts++;
            timeoutId = setTimeout(checkStatus, 3000);
        } else {
            setStatus('processing');
        }
      }
    };

    checkStatus();

    return () => clearTimeout(timeoutId);
  }, [orderId]);

  const renderContent = () => {
    switch (status) {
      case 'verifying':
        return (
          <>
            <Loader className="w-16 h-16 text-blue-600 animate-spin mx-auto mb-6" />
            <h1 className="text-2xl font-bold text-gray-900 mb-2">Confirmando seu Pedido...</h1>
            <p className="text-gray-600 mb-4">Estamos validando a transação com o banco.</p>
          </>
        );

      case 'success':
        return (
          <>
            <div className="w-24 h-24 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6">
              <CheckCircle className="text-green-600 w-12 h-12" />
            </div>
            <h1 className="text-3xl font-extrabold text-gray-900 mb-2">Pagamento Confirmado!</h1>
            <p className="text-gray-600 mb-6">
              Recebemos seu pagamento. Seu pedido já está sendo preparado.
            </p>
          </>
        );

      case 'processing':
        return (
          <>
            <div className="w-24 h-24 bg-blue-50 rounded-full flex items-center justify-center mx-auto mb-6">
              <Package className="text-blue-600 w-12 h-12" />
            </div>
            <h1 className="text-2xl font-bold text-gray-900 mb-2">Pedido Realizado!</h1>
            <p className="text-gray-600 mb-6">
              Seu pedido foi gerado com sucesso (<strong>#{orderId?.slice(0, 8)}</strong>).<br/>
              Ainda estamos aguardando a confirmação do pagamento pelo banco, mas você já pode visualizá-lo em sua conta.
            </p>
          </>
        );

      case 'issue':
      default:
        return (
          <>
            <div className="w-24 h-24 bg-orange-100 rounded-full flex items-center justify-center mx-auto mb-6">
              <AlertTriangle className="text-orange-600 w-12 h-12" />
            </div>
            <h1 className="text-2xl font-bold text-gray-900 mb-2">Pedido Registrado</h1>
            <p className="text-gray-600 mb-6">
              O pedido foi criado, mas não conseguimos confirmar o pagamento automaticamente aqui.<br/>
              Por favor, acesse seus pedidos para verificar se é necessário tentar pagar novamente.
            </p>
          </>
        );
    }
  };

  return (
    <div className="min-h-[80vh] flex items-center justify-center bg-gray-50 px-4">
      <div className="bg-white p-10 rounded-2xl shadow-xl text-center max-w-lg w-full border border-gray-200">
        
        {renderContent()}

        <div className="space-y-3 pt-4">
          <Button className="w-full py-3" variant="primary" onClick={() => router.replace('/perfil/orders')}>
            <Package size={18} className="mr-2" /> 
            {status === 'success' ? 'Acompanhar Meus Pedidos' : 'Ver Status do Pedido'}
          </Button>
          
          <Button className="w-full py-3" variant="ghost" onClick={() => router.replace('/')}>
            Voltar para a Loja <ArrowRight size={18} className="ml-2" />
          </Button>
        </div>
      </div>
    </div>
  );
};

export default function SuccessPage() {
    return (
        <Suspense fallback={<div>Processando...</div>}>
            <SuccessContent />
        </Suspense>
    )
}