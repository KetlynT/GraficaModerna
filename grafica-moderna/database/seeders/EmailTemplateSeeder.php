<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\EmailTemplate;

class EmailTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'event_type' => 'REGISTRATION_CONFIRMATION',
                'subject' => 'Bem-vindo à Gráfica Moderna!',
                'description' => 'Enviado ao criar conta',
                'html_content' => <<<HTML
<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #007bff; color: white; padding: 20px; text-align: center; }
        .content { padding: 20px; background-color: #f9f9f9; }
        .button { display: inline-block; padding: 10px 20px; background-color: #007bff; color: white; text-decoration: none; border-radius: 5px; }
        .footer { text-align: center; margin-top: 20px; font-size: 12px; color: #888; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Bem-vindo!</h1>
        </div>
        <div class="content">
            <p>Olá, <strong>{{fullName}}</strong>!</p>
            <p>Obrigado por se cadastrar na Gráfica Moderna. Sua conta foi criada com sucesso.</p>
            <p>Agora você pode acessar nosso painel, fazer orçamentos e acompanhar seus pedidos.</p>
            <p style="text-align: center; margin-top: 30px;">
                <a href="{{frontendUrl}}/login" class="button">Acessar Minha Conta</a>
            </p>
        </div>
        <div class="footer">
            <p>&copy; 2024 Gráfica Moderna. Todos os direitos reservados.</p>
        </div>
    </div>
</body>
</html>
HTML
            ],
            [
                'event_type' => 'ORDER_PLACED',
                'subject' => 'Recebemos seu Pedido #{{orderNumber}}',
                'description' => 'Confirmar recebimento do pedido',
                'html_content' => <<<HTML
<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #eee; }
        .summary { background: #f4f4f4; padding: 15px; margin: 20px 0; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Pedido Recebido!</h2>
        <p>Olá, {{fullName}}.</p>
        <p>Seu pedido <strong>#{{orderNumber}}</strong> foi recebido e está aguardando confirmação de pagamento.</p>
        
        <div class="summary">
            <p><strong>Total:</strong> R$ {{totalAmount}}</p>
            <p><strong>Frete:</strong> {{shippingMethod}}</p>
        </div>

        <p>Você será notificado assim que o pagamento for aprovado e a produção iniciar.</p>
        
        <p><a href="{{frontendUrl}}/perfil/orders">Clique aqui para ver os detalhes do pedido</a></p>
    </div>
</body>
</html>
HTML
            ],
            [
                'event_type' => 'ORDER_STATUS_CHANGED',
                'subject' => 'Atualização do Pedido #{{orderNumber}}',
                'description' => 'Mudança de status (Aprovado, Enviado, etc)',
                'html_content' => <<<HTML
<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; color: #333; }
        .status-box { background-color: #d4edda; color: #155724; padding: 15px; border-radius: 5px; text-align: center; font-weight: bold; }
    </style>
</head>
<body>
    <div style="max-width: 600px; margin: 0 auto;">
        <h2>Status Atualizado</h2>
        <p>O status do seu pedido <strong>#{{orderNumber}}</strong> mudou para:</p>
        
        <div class="status-box">
            {{newStatus}}
        </div>

        <p>{{message}}</p>

        <p>Acesse sua conta para mais detalhes ou para rastrear o envio.</p>
    </div>
</body>
</html>
HTML
            ],
            [
                'event_type' => 'PASSWORD_RESET',
                'subject' => 'Recuperação de Senha',
                'description' => 'Link para resetar senha',
                'html_content' => <<<HTML
<!DOCTYPE html>
<html>
<body>
    <div style="font-family: sans-serif; max-width: 600px; margin: auto;">
        <h2>Recuperação de Senha</h2>
        <p>Recebemos uma solicitação para redefinir sua senha.</p>
        <p>Clique no botão abaixo para criar uma nova senha:</p>
        <p>
            <a href="{{frontendUrl}}/login/alterar-senha?token={{token}}&email={{email}}" 
               style="background: #dc3545; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;">
               Redefinir Senha
            </a>
        </p>
        <p>Se você não solicitou isso, ignore este e-mail.</p>
    </div>
</body>
</html>
HTML
            ]
        ];

        foreach ($templates as $tmpl) {
            EmailTemplate::updateOrCreate(
                ['event_type' => $tmpl['event_type']], // Busca por chave única
                $tmpl // Atualiza ou cria
            );
        }
    }
}