<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Questions\StoreQuestionRequest;
use App\Http\Requests\Admin\Questions\UpdateQuestionRequest;
use App\Models\Question;
use App\Models\Questionnaire;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class QuestionController extends Controller
{
    public function create(Questionnaire $questionnaire): Response
    {
        $this->authorize('create', Question::class);

        return Inertia::render('admin/questions/Create', [
            'questionnaire' => $questionnaire,
        ]);
    }

    public function store(StoreQuestionRequest $request, Questionnaire $questionnaire): RedirectResponse
    {
        $questionnaire->questions()->create($request->validated());

        return redirect()
            ->route('admin.questionnaires.edit', $questionnaire)
            ->with('status', __('Question ajoutée.'));
    }

    public function edit(Questionnaire $questionnaire, Question $question): Response
    {
        $this->authorize('update', $question);

        $question->load(['answerOptions' => fn ($q) => $q->orderBy('position')]);

        return Inertia::render('admin/questions/Edit', [
            'questionnaire' => $questionnaire,
            'question' => $question,
        ]);
    }

    public function update(
        UpdateQuestionRequest $request,
        Questionnaire $questionnaire,
        Question $question
    ): RedirectResponse {
        $question->update($request->validated());

        return redirect()
            ->route('admin.questionnaires.edit', $questionnaire)
            ->with('status', __('Question mise à jour.'));
    }

    public function destroy(Questionnaire $questionnaire, Question $question): RedirectResponse
    {
        $this->authorize('delete', $question);

        $question->delete();

        return redirect()
            ->route('admin.questionnaires.edit', $questionnaire)
            ->with('status', __('Question supprimée.'));
    }
}
