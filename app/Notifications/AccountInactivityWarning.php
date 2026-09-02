<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Préavis de suppression pour inactivité (L09, Tableau 12) : envoyé à 23 mois
 * d'inactivité, un mois avant la purge à 24 mois.
 */
class AccountInactivityWarning extends Notification
{
    use Queueable;

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Votre compte CESIZen sera supprimé pour inactivité')
            ->greeting("Bonjour {$notifiable->name},")
            ->line('Votre compte CESIZen est inactif depuis près de 23 mois.')
            ->line('Conformément à notre politique de conservation des données, '.
                'il sera supprimé après 24 mois d\'inactivité, avec l\'ensemble des '.
                'données qui y sont rattachées.')
            ->action('Me reconnecter', url('/login'))
            ->line('Il vous suffit de vous reconnecter pour conserver votre compte.');
    }
}
