<?php

declare (strict_types = 1);

namespace GenshinTeam\Utils;

/**
 * Interface représentant un service d’envoi d’email pour les réinitialisations de mot de passe.
 *
 * Cette abstraction permet de déléguer la logique d’envoi de mails à une implémentation dédiée,
 * facilitant ainsi le test unitaire et le remplacement du transport (SMTP, API tiers, etc.).
 *
 * @package GenshinTeam\Utils
 */
interface MailSenderInterface
{
    /**
     * Envoie un email de réinitialisation de mot de passe à l'adresse spécifiée,
     * en intégrant le token fourni dans le lien de réinitialisation.
     *
     * @param string $to    L’adresse email du destinataire
     * @param string $token Le token sécurisé à inclure dans le lien de réinitialisation
     */
    public function sendPasswordReset(string $to, string $token): void;
}
