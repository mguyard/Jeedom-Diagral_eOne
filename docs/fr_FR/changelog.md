# Important à lire !

__Lorsque aucunes informations ne sont disponibles sur le changelog, cela implique un changement de documentation uniquement__

__Le changelog de la version de developpement se trouve [ici](https://github.com/mguyard/Jeedom-Diagral_eOne/blob/develop/docs/fr_FR/changelog.md)__

# 2020-06-18 - v1.2.5

- Correction de l'[issue #20](https://github.com/mguyard/Jeedom-Diagral_eOne/issues/20) impéchant la synchronisation et l'ajout de plusieures alarmes.

# 2020-04-22 - v1.2.4

- Correction sur la gestion des suivis d'installation

# 2020-04-22 - v1.2.3

- __Passage de la version en STABLE__

# 2020-04-22 - v1.2.2

- Ajout de plus de logs sur les suivi d'installation [issue #14](https://github.com/mguyard/Jeedom-Diagral_eOne/issues/17)
- Intégration d'une latence aleatoire entre 2 et 10 secondes pour les requêtes du suivi d'installation afin d'eviter que les requêtes ne soient trop en simultanées (aucun impact sur les refresh de l'alarme même si c'est la même CRON)

# 2020-04-19 - v1.2.1

- __Passage de la version en STABLE__
- Ajout de la branche du plugin dans les données de suivi d'installation

# 2020-04-13 - v1.2.0

- Intégration de la fonctionalité de génération de scénario pour les réceptions de notification Diagral
- Mise à jour de la documentation

# 2020-04-08 - v1.1.0

- Amélioration de la [feature #14](https://github.com/mguyard/Jeedom-Diagral_eOne/issues/14) en ajoutant l'envoi de la plateforme Jeedom.
- Ajustement de la configuration

# 2020-04-07 - v1.0.0

- Amélioration de la [feature #14](https://github.com/mguyard/Jeedom-Diagral_eOne/issues/14) en permettant la suppression de ses données de suivie.

# 2020-04-01

- Integration de la [feature #14](https://github.com/mguyard/Jeedom-Diagral_eOne/issues/14) permettant la synchronisation d'information pour gérer la base installée des plugins Diagral-eOne (cf. Doc)

# 2020-03-27

- Integration de la [feature #13](https://github.com/mguyard/Jeedom-Diagral_eOne/issues/13) "Gestion de déclenchement d'alarme"

# 2020-02-15

- Intégration de la fonctionnalité "Badge Alias" qui permet de définir un alias pour les badges afin d'être mis dans la commande "IMPORT - Dernier utilisateur" lors de la réception d'emails Diagral

# 2020-02-14

- Integration de la fonctionnalité "SecureDisarm" pour bloquer le désarmement de l'alarme au travers de Jeedom pour les compte _Utilisateur_ et _Utilisateur Limité_ (cf. Documentation pour plus de détails)

# 2020-02-08

- Amélioration du parsing des emails pour gérer les badges qui ne sont pas attaché à un utilisateur.

# 2020-01-11

- Correction de l'[issue #10](https://github.com/mguyard/Jeedom-Diagral_eOne/issues/10) qui remontait un nombre de mise à jour disponibles incorrect

# 2020-01-05

- Integration de la [feature #6](https://github.com/mguyard/Jeedom-Diagral_eOne/issues/6) afin de ne pas afficher les scénarios sur la tuile lorsque la commande n'est pas configuré en visible sur l'équipement

# 2019-11-03

- Integration de la [feature #3](https://github.com/mguyard/Jeedom-Diagral_eOne/issues/3) afin de ne pas afficher les scénarios sur la tuile lorsqu'aucun sont configurés.

# 2019-09-22

- Correction de l'[issue #2](https://github.com/mguyard/Jeedom-Diagral_eOne/issues/2) lorsque aucun scénarios ne sont configurés.
