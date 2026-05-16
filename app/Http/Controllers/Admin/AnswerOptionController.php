<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AnswerOptions\StoreAnswerOptionRequest;
use App\Http\Requests\Admin\AnswerOptions\UpdateAnswerOptionRequest;
use App\Models\AnswerOption;
use App\Models\Question;
use App\Models\Questionnaire;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class AnswerOptionController extends Controller
{
    public function create(Questionnaire $questionnaire, Question $question): Response
    {
        $this->authorize('create', AnswerOption::class);

        return Inertia::render('admin/answer-options/Create', [
            'questionnaire' => $questionnaire,
            'question' => $question,
        ]);
    }

    public function store(
        StoreAnswerOptionRequest $request,
        Questionnaire $questionnaire,
        Question $question
    ): RedirectResponse {
        $question->answerOptions()->create($request->validated());

        return redirect()
            ->route('admin.questionnaires.questions.edit', [$questionnaire, $question])
            ->with('status', __('Option ajoutée.'));
    }

    public function edit(Questionnaire $questionnaire, Question $question, AnswerOption $answerOption): Response
    {
        $this->authorize('update', $answerOption);

        return Inertia::render('admin/answer-options/Edit', [
            'questionnaire' => $questionnaire,
            'question' => $question,
            'option' => $answerOption,
        ]);
    }

    public function update(
        UpdateAnswerOptionRequest $request,
        Questionnaire $questionnaire,
        Question $question,
        AnswerOption $answerOption
    ): RedirectResponse {
        $option->update($request->validated());

        return redirect()
            ->route('admin.questionnaires.questions.edit', [$questionnaire, $question])
            ->with('status', __('Option mise à jour.'));
    }

    public function destroy(
        Questionnaire $questionnaire,
        Question $question,
        AnswerOption $answerOption
    ): RedirectResponse {
        $this->authorize('delete', $option);

        $option->delete();

        return redirect()
            ->route('admin.questionnaires.questions.edit', [$questionnaire, $question])
            ->with('status', __('Option supprimée.'));
    }
}
