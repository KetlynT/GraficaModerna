<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\EmailTemplate;

class EmailTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            // --- AUTH ---
            [
                'event_type' => 'RegisterConfirmation',
                'subject' => 'Bem-vindo à Gráfica Moderna!',
                'html_content' => '<p>Olá {name}, clique <a href="{link}">aqui</a> para confirmar seu email.</p>',
                'description' => 'Cadastro'
            ],
            [
                'event_type' => 'LoginAlert',
                'subject' => 'Alerta de Login',
                'html_content' => '<p>Olá {name}, detectamos um login em {date}.</p>',
                'description' => 'Segurança'
            ],
            [
                'event_type' => 'ForgotPassword',
                'subject' => 'Recuperar Senha',
                'html_content' => '<p>Use este link para resetar sua senha: <a href="{link}">Resetar</a></p>',
                'description' => 'Recuperação'
            ],
            [
                'event_type' => 'PasswordChanged',
                'subject' => 'Senha Alterada',
                'html_content' => '<p>Sua senha foi alterada com sucesso.</p>',
                'description' => 'Aviso'
            ],

            // --- PEDIDOS (OrderService) ---
            [
                'event_type' => 'OrderReceived',
                'subject' => 'Pedido #{orderNumber} Recebido',
                'html_content' => '<h2>Olá {name}, recebemos seu pedido!</h2><p>Total: R$ {total}</p><p>Itens:</p>{items}',
                'description' => 'Pedido criado'
            ],
            [
                'event_type' => 'PaymentConfirmed',
                'subject' => 'Pagamento Aprovado - Pedido #{orderNumber}',
                'html_content' => '<p>O pagamento do seu pedido foi confirmado! Em breve iniciaremos a produção.</p>',
                'description' => 'Pagamento Stripe confirmado'
            ],
            [
                'event_type' => 'OrderShipped',
                'subject' => 'Seu pedido #{orderNumber} foi enviado!',
                'html_content' => '<p>Seu pedido está a caminho. Código de Rastreio: <strong>{trackingCode}</strong></p>',
                'description' => 'Pedido despachado'
            ],
            [
                'event_type' => 'OrderDelivered',
                'subject' => 'Pedido #{orderNumber} Entregue',
                'html_content' => '<p>Seu pedido foi entregue. Esperamos que goste!</p>',
                'description' => 'Pedido concluído'
            ],
            [
                'event_type' => 'OrderCanceled',
                'subject' => 'Pedido #{orderNumber} Cancelado',
                'html_content' => '<p>Seu pedido foi cancelado.</p>',
                'description' => 'Cancelamento'
            ],
            [
                'event_type' => 'OrderCancelledOutOfStock',
                'subject' => 'Problema com seu Pedido - Estoque Esgotado',
                'html_content' => '<p>Infelizmente alguns itens acabaram no momento da compra. Seu pagamento foi estornado.</p>',
                'description' => 'Falha de estoque no webhook'
            ],
            
            // --- REEMBOLSOS ---
            [
                'event_type' => 'OrderRefunded',
                'subject' => 'Reembolso Confirmado - Pedido #{orderNumber}',
                'html_content' => '<p>O reembolso total do seu pedido foi processado.</p>',
                'description' => 'Reembolso total'
            ],
            [
                'event_type' => 'OrderPartiallyRefunded',
                'subject' => 'Reembolso Parcial - Pedido #{orderNumber}',
                'html_content' => '<p>Um reembolso parcial foi processado para seu pedido.</p>',
                'description' => 'Reembolso parcial'
            ],
            [
                'event_type' => 'OrderReturnInstructions',
                'subject' => 'Instruções de Devolução - Pedido #{orderNumber}',
                'html_content' => '<p>Siga as instruções para devolução: {returnInstructions}</p><p>Código Logística Reversa: {reverseLogisticsCode}</p>',
                'description' => 'Instruções de devolução'
            ],
            [
                'event_type' => 'OrderRefundRejected',
                'subject' => 'Atualização sobre Reembolso - Pedido #{orderNumber}',
                'html_content' => '<p>Sua solicitação de reembolso não foi aprovada. Motivo: {refundRejectionReason}</p>',
                'description' => 'Reembolso negado'
            ],

            // --- ADMIN ALERTS ---
            [
                'event_type' => 'SecurityAlertPaymentMismatch',
                'subject' => '[ALERTA] Divergência de Pagamento ID #{OrderId}',
                'html_content' => '<p>Esperado: {ExpectedAmount} | Recebido: {ReceivedAmount}</p><p>Transaction: {TransactionId}</p>',
                'description' => 'Alerta para Admin'
            ],
        ];

        foreach ($templates as $tmpl) {
            EmailTemplate::updateOrCreate(['event_type' => $tmpl['event_type']], $tmpl);
        }
    }
}