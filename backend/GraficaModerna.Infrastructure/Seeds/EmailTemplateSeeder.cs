using GraficaModerna.Domain.Entities;
using GraficaModerna.Infrastructure.Context;
using Microsoft.EntityFrameworkCore;

namespace GraficaModerna.Infrastructure.Seeds;

public class EmailTemplateSeeder(AppDbContext context)
{
    public async Task SeedAsync()
    {
        if (await context.EmailTemplates.AnyAsync()) return;

        var templates = new List<EmailTemplate>
        {
            new()
            {
                Key = "Welcome",
                Description = "E-mail de boas-vindas ao criar conta",
                Subject = "Bem-vindo à Gráfica Moderna, {{ user_name }}!",
                BodyContent = @"
                    <h1>Olá, {{ user_name }}!</h1>
                    <p>Estamos muito felizes em ter você conosco.</p>
                    <p>Sua conta foi criada com sucesso. Agora você pode fazer orçamentos e pedidos diretamente pelo site.</p>
                    <br>
                    <a href='{{ site_url }}/login' style='padding: 10px 20px; background-color: #2563eb; color: white; text-decoration: none; border-radius: 5px;'>Acessar Minha Conta</a>
                "
            },
            new()
            {
                Key = "OrderConfirmation",
                Description = "Enviado quando o cliente finaliza um pedido",
                Subject = "Recebemos seu pedido #{{ order_number }}",
                BodyContent = @"
                    <h2>Olá, {{ user_name }}</h2>
                    <p>Recebemos seu pedido <strong>#{{ order_number }}</strong> com sucesso!</p>
                    <p>Valor Total: <strong>{{ total_amount }}</strong></p>
                    <hr>
                    <h3>Itens do Pedido:</h3>
                    <ul>
                        {{ for item in items }}
                        <li>{{ item.product_name }} - {{ item.quantity }}x ({{ item.unit_price }})</li>
                        {{ end }}
                    </ul>
                    <p>Estamos aguardando a confirmação do pagamento para iniciar a produção.</p>
                "
            },
            new()
            {
                Key = "PaymentApproved",
                Description = "Enviado quando o pagamento é confirmado",
                Subject = "Pagamento Aprovado: Pedido #{{ order_number }}",
                BodyContent = @"
                    <h2 style='color: green;'>Tudo certo!</h2>
                    <p>O pagamento do pedido <strong>#{{ order_number }}</strong> foi confirmado.</p>
                    <p>Nossa equipe de design iniciará a verificação dos arquivos em breve.</p>
                    <br>
                    <p>Acompanhe o status no painel do cliente.</p>
                "
            },
            new()
            {
                Key = "OrderShipped",
                Description = "Enviado quando o pedido é despachado",
                Subject = "Seu pedido #{{ order_number }} está a caminho!",
                BodyContent = @"
                    <h2>Boas notícias!</h2>
                    <p>Seu pedido foi enviado.</p>
                    <p><strong>Código de Rastreio:</strong> {{ tracking_code }}</p>
                    <p>Transportadora: {{ carrier_name }}</p>
                    <br>
                    <a href='{{ tracking_url }}'>Rastrear Encomenda</a>
                "
            },
            new()
            {
                Key = "ForgotPassword",
                Description = "Recuperação de senha",
                Subject = "Recuperação de Senha - Gráfica Moderna",
                BodyContent = @"
                    <p>Você solicitou a redefinição de sua senha.</p>
                    <p>Use o código abaixo ou clique no link para redefinir:</p>
                    <h3 style='background: #f3f4f6; padding: 10px; display: inline-block;'>{{ reset_token }}</h3>
                    <br>
                    <a href='{{ reset_link }}'>Redefinir Minha Senha</a>
                    <p><small>Se não foi você, ignore este e-mail.</small></p>
                "
            }
        };

        await context.EmailTemplates.AddRangeAsync(templates);
        await context.SaveChangesAsync();
    }
}