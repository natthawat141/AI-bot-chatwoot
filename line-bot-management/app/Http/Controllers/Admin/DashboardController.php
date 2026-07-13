<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BotInteraction;
use App\Models\Faq;
use App\Models\KnowledgeEntry;
use App\Models\ServicePackage;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        $totalInteractions = BotInteraction::count();
        $answeredInteractions = BotInteraction::where('status', 'answered')->count();

        return Inertia::render('Dashboard', [
            'stats' => [
                'packages' => ServicePackage::count(),
                'publishedPackages' => ServicePackage::where('is_published', true)->where('is_active', true)->count(),
                'faqs' => Faq::count(),
                'knowledge' => KnowledgeEntry::count(),
            ],
            'analytics' => [
                'total' => $totalInteractions,
                'today' => BotInteraction::whereDate('created_at', today())->count(),
                'successRate' => $totalInteractions > 0
                    ? round(($answeredInteractions / $totalInteractions) * 100, 1)
                    : 0,
                'latest' => BotInteraction::query()
                    ->latest()
                    ->limit(10)
                    ->get()
                    ->map(fn (BotInteraction $interaction) => [
                        'id' => $interaction->id,
                        'question' => $interaction->question,
                        'answer' => $interaction->answer,
                        'response_type' => $interaction->response_type,
                        'status' => $interaction->status,
                        'model' => $interaction->model,
                        'duration_ms' => $interaction->duration_ms,
                        'created_at' => $interaction->created_at?->toIso8601String(),
                    ]),
            ],
        ]);
    }
}
