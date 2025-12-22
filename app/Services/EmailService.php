<?php

namespace App\Services;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Models\EmailTemplate;
use App\Mail\DynamicTemplateMail;

class EmailService
{
    public function send(string $to, string $templateType, array $data = [])
    {
        try {
            $template = EmailTemplate::where('event_type', $templateType)->first();

            if (!$template) {
                Log::warning("EmailService: Template '{$templateType}' não encontrado.");
                return;
            }

            $subject = $this->replaceVariables($template->subject, $data);
            $body = $this->replaceVariables($template->html_content, $data);

            Mail::to($to)->send(new DynamicTemplateMail($subject, $body));

            Log::info("Email '{$templateType}' enviado para {$to}");

        } catch (\Exception $e) {
            Log::error("Erro envio email ({$templateType}): " . $e->getMessage());
        }
    }

    private function replaceVariables(string $content, array $data): string
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $listHtml = "<ul>";
                foreach ($value as $item) {
                    if (isset($item['productName'])) {
                        $listHtml .= "<li>{$item['quantity']}x {$item['productName']} (R$ {$item['price']})</li>";
                    } else {
                        $listHtml .= "<li>" . print_r($item, true) . "</li>";
                    }
                }
                $listHtml .= "</ul>";
                $content = str_replace('{' . $key . '}', $listHtml, $content);
            } 
            elseif (is_string($value) || is_numeric($value)) {
                $content = str_replace('{' . $key . '}', (string) $value, $content);
            }
        }
        return $content;
    }
}