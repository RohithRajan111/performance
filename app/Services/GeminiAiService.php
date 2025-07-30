<?php

namespace App\Services;

use Gemini\Laravel\Facades\Gemini;
use Gemini\Data\Content;
use Gemini\Enums\MimeType;
use Gemini\Data\Blob;
use Gemini\Resources\ChatSession;
use Exception;
use Illuminate\Support\Facades\Log;

class GeminiAIService
{
    /**
     * Generate text content using a prompt.
     *
     * @param string $prompt
     * @param string $model
     * @return string|null
     */
    public function generateText(string $prompt, string $model = 'gemini-1.5-flash'): ?string
    {
        try {
            // --- CORRECTION IS HERE ---

 $response = Gemini::generativeModel($model)->generateContent($prompt);
            return $response->text();
        } catch (Exception $e) {
            Log::error("Gemini API Error: " . $e->getMessage(), [
                'model' => $model,
                'prompt' => $prompt
            ]);
            return null;
        }
    }

    /**
     * Start a new chat session.
     *
     * @param string $model
     * @return \Gemini\Resources\ChatSession
     */
    public function startNewChat(string $model = 'gemini-1.5-flash'): ChatSession
    {
        // --- CORRECTION IS HERE ---
        return Gemini::generativeModel($model)->startChat();
    }

    /**
     * Send a message in an existing chat session.
     *
     * @param \Gemini\Resources\ChatSession $chat
     * @param string $message
     * @return string|null
     */
    public function sendMessageInChat(ChatSession $chat, string $message): ?string
    {
        try {
            $response = $chat->sendMessage($message);
            return $response->text();
        } catch (Exception $e) {
            Log::error("Gemini Chat API Error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Generate content with an image and text.
     *
     * @param string $textPrompt
     * @param string $imagePath - full path to the image file
     * @param string $model
     * @return string|null
     */
    public function generateContentWithImage(string $textPrompt, string $imagePath, string $model = 'gemini-1.5-pro'): ?string
    {
        try {
            if (!file_exists($imagePath)) {
                throw new Exception("Image file not found: " . $imagePath);
            }

            $imageData = file_get_contents($imagePath);
            $mimeType = mime_content_type($imagePath);

            // --- CORRECTION IS HERE ---
            $response = Gemini::generativeModel($model)->generateContent([
                $textPrompt,
                new Blob(
                    mimeType: $mimeType ?: MimeType::IMAGE_JPEG,
                    data: $imageData
                )
            ]);
            return $response->text();
        } catch (Exception $e) {
            Log::error("Gemini Multimodal API Error: " . $e->getMessage(), [
                'model' => $model
            ]);
            return null;
        }
    }
}
