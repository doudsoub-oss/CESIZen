<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Questionnaires\StoreQuestionnaireRequest;
use App\Http\Requests\Admin\Questionnaires\UpdateQuestionnaireRequest;
use App\Models\Questionnaire;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class QuestionnaireController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', Questionnaire::class);

        $questionnaires = Questionnaire::query()
            ->withCount(['questions', 'interpretations', 'diagnostics'])
            ->orderBy('title')
            ->paginate(20);

        return Inertia::render('admin/questionnaires/Index', [
            'questionnaires' => $questionnaires,
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Questionnaire::class);

        return Inertia::render('admin/questionnaires/Create');
    }

    public function store(StoreQuestionnaireRequest $request): RedirectResponse
    {
        Questionnaire::create([
            ...$request->validated(),
            'created_by' => $request->user()->id,
        ]);

        return redirect()
            ->route('admin.questionnaires.index')
            ->with('status', __('Questionnaire créé.'));
    }

    public function edit(Questionnaire $questionnaire): Response
    {
        $this->authorize('update', $questionnaire);

        $questionnaire->load([
            'questions' => fn ($q) => $q->orderBy('position'),
            'questions.answerOptions' => fn ($q) => $q->orderBy('position'),
            'interpretations' => fn ($q) => $q->orderBy('min_score'),
        ]);

        return Inertia::render('admin/questionnaires/Edit', [
            'questionnaire' => $questionnaire,
        ]);
    }

    public function update(UpdateQuestionnaireRequest $request, Questionnaire $questionnaire): RedirectResponse
    {
        $questionnaire->update($request->validated());

        return redirect()
            ->route('admin.questionnaires.index')
            ->with('status', __('Questionnaire mis à jour.'));
    }

    public function destroy(Questionnaire $questionnaire): RedirectResponse
    {
        $this->authorize('delete', $questionnaire);

        $questionnaire->delete();

        return redirect()
            ->route('admin.questionnaires.index')
            ->with('status', __('Questionnaire supprimé.'));
    }
}
