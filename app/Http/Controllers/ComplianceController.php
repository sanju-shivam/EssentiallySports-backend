<?php
// app/Http/Controllers/ComplianceController.php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\FeedConfig;
use App\Models\ComplianceRule;
use App\Services\ComplianceEngine;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ComplianceController extends Controller
{
    protected ComplianceEngine $complianceEngine;

    public function __construct(ComplianceEngine $complianceEngine)
    {
        $this->complianceEngine = $complianceEngine;
    }

    public function checkCompliance(Article $article, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'feed_id' => 'required|integer|exists:feed_configs,id',
        ]);

        $feedConfig = FeedConfig::where('id', $validated['feed_id'])->first();
        $results = $this->complianceEngine->validateArticle($article, $feedConfig);

        $passedCount = collect($results)->filter(fn($r) => $r['passed'])->count();
        $totalCount = count($results);

        return response()->json([
            'article_id' => $article->id,
            'feed_name' => $feedConfig->name,
            'overall_status' => $passedCount === $totalCount ? 'PASSED' : 'FAILED',
            'passed_checks' => $passedCount,
            'total_checks' => $totalCount,
            'results' => $results,
            'checked_at' => now()->toISOString(),
        ]);
    }

    public function getRules(): JsonResponse
    {
        $rules = ComplianceRule::orderBy('priority')->get();
        return response()->json($rules);
    }

    public function createRule(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:compliance_rules',
            'validator_class' => 'required|string',
            'parameters' => 'nullable|array',
            'description' => 'required|string',
            'priority' => 'integer|min:0',
        ]);

        $rule = ComplianceRule::create($validated);
        return response()->json($rule, 201);
    }

    public function updateRule(ComplianceRule $rule, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'validator_class' => 'sometimes|string',
            'parameters' => 'sometimes|nullable|array',
            'description' => 'sometimes|string',
            'is_active' => 'sometimes|boolean',
            'priority' => 'sometimes|integer|min:0',
        ]);

        $rule->update($validated);
        return response()->json($rule);
    }

    public function getAvailableValidators(): JsonResponse
    {
        return response()->json([
            'validators' => $this->complianceEngine->getAvailableValidators(),
        ]);
    }
}