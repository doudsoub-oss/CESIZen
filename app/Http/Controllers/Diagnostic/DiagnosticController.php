<?php

namespace App\Http\Controllers\Diagnostic;

use App\Http\Controllers\Controller;
use App\Http\Requests\Diagnostic\SubmitDiagnosticRequest;
use App\Models\Diagnostic;
use App\Models\Questionnaire;
use App\Services\DiagnosticScoringService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class DiagnosticController extends Controller
{
    public function __construct(private readonly DiagnosticScoringService $scoring) {}

    /**
     * Picker for available diagnostics. If exactly one is active, redirect
     * straight into it.
     */
    public function index(): Response|RedirectResponse
    {
        $active = Questionnaire::query()->active()->orderBy('title')->get();

        if ($active->count() === 1) {
            return redirect()->route('diagnostic.show', $active->first());
        }

        return Inertia::render('public/Diagnostic/Index', [
            'questionnaires' => $active,
        ]);
    }

    /**
     * Show a questionnaire's run page (questions + options, ordered).
     */
    public function show(Questionnaire $questionnaire): Response
    {
        if (! $questionnaire->is_active) {
            throw new NotFoundHttpException;
        }

        $questionnaire->load([
            'questions.answerOptions:id,question_id,label,score,position',
        ]);

        return Inertia::render('public/Diagnostic/Run', [
            'questionnaire' => $questionnaire,
        ]);
    }

    /**
     * Score the submission. Authenticated users get a persisted diagnostic +
     * responses; anonymous users get the result inline (no DB write).
     */
    public function submit(SubmitDiagnosticRequest $request, Questionnaire $questionnaire): Response
    {
        /** @var array<int, int> $answers */
        $answers = collect($request->input('answers'))
            ->mapWithKeys(fn ($v, $k) => [(int) $k => (int) $v])
            ->all();

        $result = $this->scoring->score($questionnaire, $answers);

        // Le résultat n'est conservé que pour un usager authentifié AYANT donné
        // son consentement explicite (art. 9.2.a). Refus côté serveur, pas
        // seulement côté interface : sans consentement, rien n'est persisté.
        $diagnostic = null;
        if ($request->user() !== null && $request->boolean('consent')) {
            $diagnostic = DB::transaction(function () use ($questionnaire, $result, $request) {
                $d = Diagnostic::create([
                    'user_id' => $request->user()->id,
                    'questionnaire_id' => $questionnaire->id,
                    'score_total' => $result['total'],
                    'result_interpretation_id' => $result['interpretation']?->id,
                    'completed_at' => now(),
                    'consented_at' => now(),
                ]);
                $d->responses()->createMany($result['responses']);

                return $d;
            });
        }

        return Inertia::render('public/Diagnostic/Result', [
            'questionnaire' => $questionnaire,
            'score' => $result['total'],
            'interpretation' => $result['interpretation'],
            'saved' => $diagnostic !== null,
            'diagnosticId' => $diagnostic?->id,
        ]);
    }
}
