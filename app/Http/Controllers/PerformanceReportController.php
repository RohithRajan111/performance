<?php

namespace App\Http\Controllers;

use App\Actions\Performance\ShowPerformance;
use App\Models\User;
use App\Services\GeminiAiService; // Your Gemini service
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth; // [+] Add this to get the logged-in user
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class PerformanceReportController extends Controller
{
    /**
     * Display the performance report for a user.
     * (Your existing admin-facing method)
     */
    public function show(User $user, ShowPerformance $action): \Inertia\Response
    {
        return Inertia::render('Performance/Show', $action->handle($user));
    }

    /**
     * Generate an AI performance summary for a given user.
     * (Your existing admin-facing method)
     */
    public function generateSummary(Request $request, User $user, GeminiAIService $geminiAiService)
    {
        $stats = $request->validate([
            'taskStats' => 'required|array',
            'taskStats.completion_rate' => 'required|numeric',
            'taskStats.completed' => 'required|integer',
            'taskStats.total' => 'required|integer',
            'timeStats' => 'required|array',
            'timeStats.current_month' => 'required|numeric',
            'leaveStats' => 'required|array',
            'leaveStats.current_year' => 'required|numeric',
            'leaveStats.balance' => 'required|numeric',
            'performanceScore' => 'required|numeric',
        ]);

        $prompt = "
            As an HR manager, write a concise, professional, and encouraging performance review summary for an employee named {$user->name}.
            The tone should be supportive, highlighting strengths and gently suggesting areas for growth.
            Do not use markdown formatting. Write in plain text paragraphs.

            Use the following data to inform your summary:
            - Overall Performance Score: {$stats['performanceScore']}%
            - Task Completion Rate: {$stats['taskStats']['completion_rate']}% ({$stats['taskStats']['completed']} of {$stats['taskStats']['total']} tasks completed).
            - Total Hours Logged this Month: {$stats['timeStats']['current_month']} hours.
            - Leave Days Taken This Year: {$stats['leaveStats']['current_year']} out of a total allowance of {$stats['leaveStats']['balance']} days.

            Based on these metrics, provide a 1-2 paragraph summary of their performance.
        ";

        $summary = $geminiAiService->generateText($prompt);

        if (!$summary) {
            Log::error("Failed to generate performance summary for employee: {$user->id}");
            return response()->json(['error' => 'The AI summary could not be generated at this time.'], 500);
        }

        return response()->json(['summary' => $summary]);
    }


    /**
     * [+] ADD THIS ENTIRE NEW METHOD
     * Generate an AI performance summary for the currently authenticated user.
     * This is called by the button on the user's own dashboard.
     */
    public function generateMySummary(Request $request, GeminiAIService $geminiAiService)
    {
        // 1. Validate the stats passed from the user's own dashboard.
        $stats = $request->validate([
            'taskStats' => 'required|array',
            'taskStats.completion_rate' => 'required|numeric',
            'timeStats' => 'required|array',
            'timeStats.current_month' => 'required|numeric',
            'leaveStats' => 'required|array',
            'leaveStats.current_year' => 'required|numeric',
            'leaveStats.balance' => 'required|numeric',
            'performanceScore' => 'required|numeric',
        ]);

        // 2. Get the currently authenticated user.
        $user = Auth::user();

        // 3. Engineer the prompt for this specific user.
        $prompt = "
            As an HR manager, write a concise, professional, and encouraging performance review summary for an employee named {$user->name}.
            The tone should be supportive, highlighting strengths and gently suggesting areas for growth.
            Do not use markdown formatting. Write in plain text paragraphs.

            Use the following data to inform your summary:
            - Overall Performance Score: {$stats['performanceScore']}%
            - Task Completion Rate: {$stats['taskStats']['completion_rate']}%
            - Total Hours Logged this Month: {$stats['timeStats']['current_month']} hours.
            - Leave Days Taken This Year: {$stats['leaveStats']['current_year']} out of a total allowance of {$stats['leaveStats']['balance']} days.

            Based on these metrics, provide a 1-2 paragraph summary of their performance.
        ";

        // 4. Call the AI service.
        $summary = $geminiAiService->generateText($prompt);

        // 5. Handle potential errors and return the JSON response.
        if (!$summary) {
            Log::error("Failed to generate 'my summary' for user: {$user->id}");
            return response()->json(['error' => 'The AI summary could not be generated at this time.'], 500);
        }

        return response()->json(['summary' => $summary]);
    }
}
