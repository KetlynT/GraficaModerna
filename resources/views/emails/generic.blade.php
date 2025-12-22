<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .footer { margin-top: 30px; font-size: 12px; color: #777; border-top: 1px solid #eee; padding-top: 10px; }
        a { color: #2563eb; }
    </style>
</head>
<body>
    <div class="container">
        {{-- 
            Atenção: O uso de {!! !!} é intencional para renderizar HTML vindo do banco (CKEditor/RichText).
            Certifique-se de sanitizar o HTML no AdminController ao salvar o template.
        --}}
        {!! $content !!}
        
        <div class="footer">
            <p>Este é um e-mail automático, por favor não responda.</p>
            <p>&copy; {{ date('Y') }} {{ config('app.name') }}. Todos os direitos reservados.</p>
        </div>
    </div>
</body>
</html>