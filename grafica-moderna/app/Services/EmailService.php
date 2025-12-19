<?php

namespace App\Services;

use App\Models\EmailTemplate;
use App\DynamicTemplateMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class EmailService
{
    protected $templateService;

    public function __construct(TemplateService $templateService)
    {
        $this->templateService = $templateService;
    }

    public function send(string $to, string $eventType, array $variables)
    {
        try {
            $template = EmailTemplate::where('event_type', $eventType)->first();

            // Fallback se o template não existir no banco
            if (!$template) {
                Log::warning("Template de email não encontrado: {$eventType}");
                return;
            }

            // Processa Assunto e Corpo
            $parsedBody = $this->templateService->parse($template->html_content, $variables);
            $parsedSubject = $this->templateService->parse($template->subject, $variables);

            // Envia (Queue é recomendado em produção)
            Mail::to($to)->send(new DynamicTemplateMail($parsedSubject, $parsedBody));

        } catch (\Exception $e) {
            Log::error("Erro ao enviar email ({$eventType}) para {$to}: " . $e->getMessage());
            // Não relançamos o erro para não travar o fluxo do usuário (ex: checkout)
        }
    }
}