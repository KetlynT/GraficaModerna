<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #f8f9fa; padding: 20px; text-align: center; border-bottom: 2px solid #eee; }
        .content { padding: 30px 20px; background: #fff; }
        .footer { text-align: center; font-size: 12px; color: #999; margin-top: 30px; }
        .btn { background: #2563eb; color: #fff; text-decoration: none; padding: 10px 20px; border-radius: 5px; display: inline-block; margin: 10px 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>{{ $subject ?? 'Notificação' }}</h2>
        </div>
        
        <div class="content">
            {!! $body !!}
        </div>

        <div class="footer">
            <p>&copy; {{ date('Y') }} Gráfica Moderna. Todos os direitos reservados.</p>
        </div>
    </div>
</body>
</html>