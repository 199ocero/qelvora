<?php

namespace App\Actions\Mail;

use App\Models\EmailTemplate;

class UpdateTemplate
{
    /**
     * Update a template's name and content. The slug is stable once created.
     *
     * @param  array<string, mixed>  $data
     */
    public function handle(EmailTemplate $template, array $data): EmailTemplate
    {
        $template->update([
            'name' => $data['name'],
            'subject' => $data['subject'] ?? null,
            'html' => $data['html'] ?? null,
            'text' => $data['text'] ?? null,
        ]);

        return $template;
    }
}
