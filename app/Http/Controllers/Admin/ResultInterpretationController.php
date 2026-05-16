<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ResultInterpretations\StoreResultInterpretationRequest;
use App\Http\Requests\Admin\ResultInterpretations\UpdateResultInterpretationRequest;
use App\Models\Questionnaire;
use App\Models\ResultInterpretation;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ResultInterpretationController extends Controller
{
    public function create(Questionnaire $questionnaire): Response
    {
        $this->authorize('create', ResultInterpretation::class);

        return Inertia::render('admin/interpretations/Create', [
            'questionnaire' => $questionnaire,
        ]);
    }

    public function store(
        StoreResultInterpretationRequest $request,
        Questionnaire $questionnaire
    ): RedirectResponse {
        $questionnaire->interpretations()->create($request->validated());

        return redirect()
            ->route('admin.questionnaires.edit', $questionnaire)
            ->with('status', __('Interprétation ajoutée.'));
    }

    public function edit(Questionnaire $questionnaire, ResultInterpretation $interpretation): Response
    {
        $this->authorize('update', $interpretation);

        return Inertia::render('admin/interpretations/Edit', [
            'questionnaire' => $questionnaire,
            'interpretation' => $interpretation,
        ]);
    }

    public function update(
        UpdateResultInterpretationRequest $request,
        Questionnaire $questionnaire,
        ResultInterpretation $interpretation
    ): RedirectResponse {
        $interpretation->update($request->validated());

        return redirect()
            ->route('admin.questionnaires.edit', $questionnaire)
            ->with('status', __('Interprétation mise à jour.'));
    }

    public function destroy(
        Questionnaire $questionnaire,
        ResultInterpretation $interpretation
    ): RedirectResponse {
        $this->authorize('delete', $interpretation);

        $interpretation->delete();

        return redirect()
            ->route('admin.questionnaires.edit', $questionnaire)
            ->with('status', __('Interprétation supprimée.'));
    }
}
