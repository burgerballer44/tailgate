<?php

namespace App\Http\Controllers\Developer;

use App\Http\Controllers\Controller;
use App\Services\Contracts\PredictionPolicyEvaluatorInterface;
use Illuminate\Contracts\View\View;

class DeveloperRuleController extends Controller
{
    /**
     * Build the rules controller with the evaluator used to resolve app and group policies.
     *
     * @param PredictionPolicyEvaluatorInterface $predictionPolicyEvaluator Service that exposes configured prediction rules.
     * @return void Initializes controller dependencies.
     */
    public function __construct(private readonly PredictionPolicyEvaluatorInterface $predictionPolicyEvaluator) {}

    /**
     * Display all configured prediction rules for developer troubleshooting.
     *
     * @return View Renders the developer rules screen with app-level and group-level policy sets.
     */
    public function index(): View
    {
        return view('developer.rules.index', [
            'appRules' => $this->predictionPolicyEvaluator->appRules(),
            'groupRules' => $this->predictionPolicyEvaluator->groupRules(),
        ]);
    }
}