<?php

namespace App\Http\Controllers\Diagnostic;

use App\Http\Controllers\Controller;
use App\Models\Diagnostic;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class HistoryController extends Controller
{
    public function index(): Response
    {
        $diagnostics = Diagnostic::query()
            ->where('user_id', auth()->id())
            ->with(['questionnaire:id,title,slug', 'resultInterpretation:id,title,color'])
            ->latest('completed_at')
            ->paginate(20);

        return Inertia::render('public/Diagnostic/History', [
            'diagnostics' => $diagnostics,
        ]);
    }

    public function show(Diagnostic $diagnostic): Response
    {
        if ($diagnostic->user_id !== auth()->id()) {
            throw new AccessDeniedHttpException;
        }

        $diagnostic->load([
            'questionnaire:id,title,slug',
            'resultInterpretation',
            'responses.question:id,text',
            'responses.answerOption:id,label,score',
        ]);

        return Inertia::render('public/Diagnostic/HistoryShow', [
            'diagnostic' => $diagnostic,
        ]);
    }

    /**
     * Suppression unitaire d'un diagnostic : c'est le mécanisme de retrait du
     * consentement (L08). Efface réellement la ligne et, par cascade, ses
     * réponses.
     */
    public function destroy(Diagnostic $diagnostic): RedirectResponse
    {
        if ($diagnostic->user_id !== auth()->id()) {
            throw new AccessDeniedHttpException;
        }

        $diagnostic->delete();

        return to_route('diagnostic.history')
            ->with('status', __('Le diagnostic a été supprimé.'));
    }
}
