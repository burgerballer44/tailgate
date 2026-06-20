<?php

namespace App\Http\Controllers\Developer;

use App\Http\Controllers\Controller;
use App\Services\Contracts\PredictionPolicyEvaluatorInterface;
use Illuminate\Contracts\View\View;

class DeveloperRuleController extends Controller
{
    public function __construct(private readonly PredictionPolicyEvaluatorInterface $predictionPolicyEvaluator) {}

    public function index(): View
    {
        return view('developer.rules.index', [
            'appRules' => $this->predictionPolicyEvaluator->appRules(),
            'groupRules' => $this->predictionPolicyEvaluator->groupRules(),
        ]);
    }
}