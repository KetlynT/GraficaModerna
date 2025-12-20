<?php

namespace App\Application\Interfaces;

interface IEmailService
{
    public function sendEmail(string $to, string $subject, string $body): void;
}