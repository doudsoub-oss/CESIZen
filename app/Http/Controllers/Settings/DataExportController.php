<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Droit à la portabilité (article 20 du RGPD, Tableau 13, traite R11).
 *
 * Génère à la volée un export JSON structuré des données de la personne
 * connectée — et d'elle seule. Les résultats de diagnostic sont restitués en
 * clair (déchiffrés) : c'est la personne concernée qui les reçoit. Aucun secret
 * (empreinte de mot de passe, secret 2FA, jetons) n'est inclus. Rien n'est écrit
 * sur disque : la réponse est diffusée en flux.
 */
class DataExportController extends Controller
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    public function __invoke(Request $request): StreamedResponse
    {
        $user = $request->user();

        $payload = $this->buildPayload($user);

        // Action sensible au sens de 2.3 : elle est consignée au journal d'audit.
        $this->auditLogger->log('user.data_exported', $user);

        $filename = 'cesizen-mes-donnees-'.now()->format('Y-m-d-His').'.json';

        return response()->streamDownload(function () use ($payload): void {
            echo json_encode(
                $payload,
                JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            );
        }, $filename, [
            'Content-Type' => 'application/json',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildPayload(User $user): array
    {
        $user->load(['diagnostics' => fn ($q) => $q->with([
            'questionnaire:id,title',
            'resultInterpretation:id,title',
            'responses.question:id,text',
            'responses.answerOption:id,label',
        ])]);

        return [
            'meta' => [
                'genere_le' => now()->toIso8601String(),
                'version_format' => '1.0',
                'responsable_de_traitement' => 'Ministère de la Santé et de la Prévention',
                'finalite' => 'Sensibilisation à la santé mentale et gestion du stress. '.
                    'Export fourni au titre du droit à la portabilité (article 20 du RGPD).',
            ],
            'compte' => [
                'nom' => $user->name,
                'email' => $user->email,
                'cree_le' => $user->created_at?->toIso8601String(),
                'actif' => (bool) $user->is_active,
                'role' => $user->role->value,
            ],
            'diagnostics' => $user->diagnostics->map(fn ($diagnostic) => [
                'questionnaire' => $diagnostic->questionnaire?->title,
                'score' => (int) $diagnostic->score_total,
                'interpretation' => $diagnostic->resultInterpretation?->title,
                'complete_le' => $diagnostic->completed_at?->toIso8601String(),
                'reponses' => $diagnostic->responses->map(fn ($response) => [
                    'question' => $response->question?->text,
                    'reponse' => $response->answerOption?->label,
                    'score' => (int) $response->score,
                ])->values(),
            ])->values(),
        ];
    }
}
