#!/bin/sh
# Entrée du conteneur de sauvegarde (L18).
#
# Image de base : postgres:16-alpine (fournit pg_dump/pg_restore à la MÊME
# version que la base — indispensable à la compatibilité des dumps). On y ajoute
# gnupg (chiffrement AES-256) et rclone (externalisation) au démarrage, sans
# reconstruire d'image ni compiler quoi que ce soit sur le serveur.
#
# Par défaut, lance le démon cron (sauvegarde quotidienne). Toute commande passée
# en argument est exécutée à la place (ex. « sauvegarder.sh --verifier » appelé
# avant migration en production).
set -eu

# Outils requis, installés une seule fois par cycle de vie du conteneur.
if ! command -v gpg >/dev/null 2>&1 || ! command -v rclone >/dev/null 2>&1; then
    apk add --no-cache gnupg rclone >/dev/null
fi

# Exécution ponctuelle (ex. vérification avant migration) : on exécute et on sort.
if [ "$#" -gt 0 ]; then
    exec "$@"
fi

# Planification quotidienne via le cron de busybox.
mkdir -p /etc/crontabs
cp /usr/local/bin/sauvegarde.crontab /etc/crontabs/root
crontab /etc/crontabs/root
echo "[sauvegarde] cron installé — sauvegarde quotidienne planifiée"
exec crond -f -d 8
