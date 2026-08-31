<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Politique de sécurité de contenu (CSP)
    |--------------------------------------------------------------------------
    |
    | En mode rapport (défaut), l'en-tête émis est
    | « Content-Security-Policy-Report-Only » : les violations sont observées
    | sans bloquer. On relève les violations en recette, puis on bascule en mode
    | bloquant en passant CSP_REPORT_ONLY à false (décision documentée, lot L04).
    |
    */

    'csp_report_only' => (bool) env('CSP_REPORT_ONLY', true),

];
