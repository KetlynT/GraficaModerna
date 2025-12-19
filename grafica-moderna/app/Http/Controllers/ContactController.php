<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\EmailService;
use App\Services\Security\InputCleaner;

class ContactController extends Controller
{
    protected $emailService;

    public function __construct(EmailService $emailService)
    {
        $this->emailService = $emailService;
    }

    public function sendMessage(Request $request)
    {
        // 1. Validação
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email',
            'subject' => 'required|string|max:150',
            'message' => 'required|string|max:2000'
        ]);

        // 2. Sanitização (Segurança contra XSS)
        $cleanData = InputCleaner::clean($data);

        // 3. Preparar dados para o Email
        // O destinatário será o ADMIN_EMAIL configurado no .env
        $adminEmail = env('ADMIN_EMAIL'); 
        
        if (!$adminEmail) {
            return response()->json(['error' => 'Configuração de email do admin ausente.'], 500);
        }

        // 4. Enviar Email
        // Precisamos criar um template 'ContactForm' no banco ou usar um genérico
        $this->emailService->send($adminEmail, 'ContactForm', [
            'name' => $cleanData['name'],
            'email' => $cleanData['email'], // Email de quem enviou (para responder)
            'subject' => $cleanData['subject'],
            'message' => nl2br($cleanData['message']), // Preserva quebras de linha
            'date' => now()->format('d/m/Y H:i')
        ]);

        return response()->json(['message' => 'Mensagem enviada com sucesso!']);
    }
}