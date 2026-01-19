<?php
namespace App\Services;

use OpenAI\Laravel\Facades\OpenAI;

class AIIntentService
{
   public function analyze(string $message): array
    {
        $response = OpenAI::chat()->create([
            'model' => 'gpt-4o-mini',
            'temperature' => 0.2,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => file_get_contents(
                        storage_path('app/prompts/sales_prompt.txt')
                    )
                ],
                [
                    'role' => 'user',
                    'content' => $message
                ]
            ]
        ]);

        return json_decode(
            $response->choices[0]->message->content,
            true
        );
    }
}
