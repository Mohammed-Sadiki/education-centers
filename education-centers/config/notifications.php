<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Notifications internes — réglages explicites et documentés
    |--------------------------------------------------------------------------
    |
    | Valeurs volontairement centralisées ici pour ne jamais « inventer » de
    | seuils enfouis dans le code. Chaque paramètre est documenté et peut être
    | surchargé via le fichier .env sans toucher à la logique métier.
    |
    */

    /*
    | Seuil (en DH) au-delà duquel une dépense est considérée « importante » et
    | notifie le Super Admin. Une dépense est notifiée si son montant total est
    | STRICTEMENT SUPÉRIEUR à ce seuil.
    */
    'expense_threshold' => (float) env('NOTIF_EXPENSE_THRESHOLD', 5000),

    /*
    | Nombre de jours avant une échéance (facture) pour générer un rappel
    | « paiement à venir ». L'échéance doit être réellement enregistrée
    | (Invoice.due_date) et rester impayée.
    */
    'due_soon_days' => (int) env('NOTIF_DUE_SOON_DAYS', 3),

    /*
    | Délai (en jours) pendant lequel on ne renvoie PAS de nouveau rappel de
    | retard pour la même facture, afin d'éviter les alertes quotidiennes
    | répétitives pour un même retard.
    */
    'overdue_cooldown_days' => (int) env('NOTIF_OVERDUE_COOLDOWN_DAYS', 15),

    /*
    | Jours d'avance pour rappeler un salaire professeur dont la date de
    | versement prévue (scheduled_payment_date) est connue et non encore réglé.
    */
    'salary_soon_days' => (int) env('NOTIF_SALARY_SOON_DAYS', 5),

    /*
    | Fréquence (en secondes) du rafraîchissement automatique de la cloche
    | côté client (polling léger, sans WebSocket).
    */
    'poll_interval' => (int) env('NOTIF_POLL_INTERVAL', 45),

    /*
    | Nombre maximal de notifications renvoyées par le dropdown de la cloche.
    */
    'dropdown_limit' => (int) env('NOTIF_DROPDOWN_LIMIT', 8),
];
