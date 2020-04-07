![Logo](../assets/images/Diagral_eOne_icon.png)

# Présentation 

Diagral est un constructeur français d'alarme, appartenant au groupe HAGER :
[https://www.diagral.fr/qui-sommes-nous](https://www.diagral.fr/qui-sommes-nous)

Les nouvelles alarmes Diagral sont sous l'appellation e-ONE.
Par cette appellation, Diagral entre dans l'ère des alarmes connectés au travers d'une application portant le même nom.

Ce plugin Jeedom vous permet d'interragir avec votre alarme Diagral e-ONE au même titre que l'application officielle.


# Principe 

Diagral propose uniquement une méthode Cloud d'interraction avec votre alarme, par conséquent ce plugin utilise une connexion internet pour interragir avec votre alarme.

C'est donc une interface __CLOUD__

# Configuration

__Le plugin se veut simple (et non simpliste) en utilisation et en configuration__.
Tout ce que le plugin peut récupérer via le Cloud Diagral est fait afin de vous éviter des configurations inutiles et source d'erreurs.

Le plugin comporte une configuration générale.
Vous y saissez les informations suivantes :

-   Identifiant : adresse email utilisée sur l'application mobile Diagral e-ONE (de préférence le compte principal afin de pouvoir déconnecter les autres sessions)
-   Mot de passe : Le mot de passe associé à l'identifiant
-   Nombre de tentatives : nombre de tentatives en cas d'échec de connexion au Cloud Diagral (default : 1)
-   Délai entre les tentatives : délais entre les tentatives (en secondes)
-   Suivi d'installation (cf chapitre dédié)
-   Verbose : Permettant de définir un niveau de verbosité pour les requètes vers le Cloud Diagral (_Pas encore utilisable_)

![Configuration Générale](../assets/images/ConfigurationGenerale.png)


## Configuration d'un équipement

La création de l'équipement se fait de façon automatique. Il vous suffit de cliquer sur Synchronisation dans le plugin, pour que chacune des alarmes configurées et finalisées dans votre compte Diagral, soit créée. Elles sont automatiquement nommées telles que dans l'application Mobile Diagral.

Une fois le/les alarmes créées, il faut entrer dans chacune d'elles afin de configurer :
-   le __Master Code__ (celui que vous utilisez pour vous connecter à votre alarme avec l'application Mobile Digral e-ONE).
-   l'objet parent
-   activer l'équipement

![Configuration Equipement](../assets/images/ConfigurationDevice.png)


> Une fonctionnalité de sécurisation du désarmement (SecureDisam) est disponible avec l'option __Sécurisation désarmement__.
> 
> Cette fonctionnalité permet si elle est activée (case cochée) d'empêcher le désarmement de l'alarme au travers de Jeedom.
> Les commandes sont cependant toujours présente mais bloquée.
> 
> Cette fonctionnalité ne prend de sens que pour empêcher la désactivation de l'alarme à un compte _Utilisateur_ ou _Utilisateur limité_ Jeedom. Par exemple, pour une tablette qui déporte l'affichage de votre Jeedom. En tant qu'administrateur, même si la fonctionnalitée SecureDisarm est active, elle est outre-passée.
> 
> <span style="color:red">__FONCTIONNALITE UNIQUEMENT EN BETA POUR LE MOMENT__</span>

Lors de la sauvegarde, de nouvelles commandes vont se créer car elle nécessitent le MasterCode (ex : _Activation Partielle ou Lancement Scenario).

Ces commandes sont en fait les commandes qui permettent d'avoir une liste déroulante dans les scénarios. Ainsi le plugin va personnaliser les listes déroulantes avec les informations présentes sur votre compte.

# Déclenchement d'alarme

Le plugin peut détecter un déclenchement d'alarme a condition qu'il recoivent l'information par MAIL ou SMS (cf chapitre dédié à la reception des mail ou sms Diagral)

> <span style="color:red">__FONCTIONNALITE UNIQUEMENT EN BETA POUR LE MOMENT__</span>
> 
> <span style="color:red">__FONCTIONNALITE DISPONIBLE UNIQUEMENT POUR LES RECPETIONS EMAILS__</span>

Une commande info binaire ("Alarme déclenchée") existe pour récuperer le statut de l'alarme. Vous pouvez ainsi l'utiliser dans un scénario pour lancer des actions (notification ou baisser les volets par exemple).
Cette commande info à 2 valeurs possibles :
-   0 : Aucune alarme déclenchée
-   1 : Une alarme en cours

Le statut de l'alarme repasse à 0 automatiquement lorsque le plugin detecte soit que :
-   l'alarme est désormais désactivée
-   l'alarme n'est plus active pour la zone qui a déclenché l'alerte (armement partiel)

# Alias de Badge

Vous pouvez définir dans l'équipement (onglet Badges), des alias sur un maximum de 8 Badges.

![Configuration des alias de badges](../assets/images/ConfigurationBadgesAlias.png)

Lorsque vous recevez un email d'activation ou désactivation au travers d'un badge, il n'indique pas d'utilisateur. Afin de faciliter l'usage, cette fonctionnalité permet d'ajouter un alias à un badge qui sera positionné dans la commande __IMPORT - Dernier utilisateur__ au même titre que ce champs est rempli lorsque qu'un utilisateur active ou désactive l'alarme via l'application Diagral ou son code personnel.

# Rafraichissement

## Automatique

Une tâche CRON est automatiquement créée sur base du délai entre les tentatives indiqué dans la configuration du plugin.

![Configuration Générale](../assets/images/ConfigurationGenerale.png)

## Manuel

Dans un scénario, vous pouvez utiliser la commande __Rafraichir__ afin de reforcer un refresh de l'alarme.

## A réception d'un Email ou SMS

Le plugin permet de recevoir des informations non disponibles par l'API (utilisateur intervenu, déclenchement d'alarme, etc...) au travers d'Email ou SMS.
Le plugin ne gère pas la collecte Email ou SMS. Il faut donc utiliser un autre plugin pour transmettre le message au plugin.
La commande utilisée pour recevoir le message est __Importer Message__

> Actuellement, seul le plugin [Mail Listener](https://www.jeedom.com/market/index.php?v=d&p=market&author=lunarok&&name=Mail%20Listener) de Lunarok est validé comme compatible.
> 
> Mais il n'y a pas de raison que cela ne fonctionne pas avec d'autres plugins.

![Exemple de scénario avec le plugin Mail Listener](../assets/images/ScenarioImporterMessage.png)

Dans la commande, vous devez avoir :
- dans __Options__
  - Le sujet du message (si vous n'en avez pas comme dans le cas d'un SMS ne pas le mettre)
  - Un "pipe ou barre verticale " qui sert de séparateur (_uniquement si j'ai un sujet avant_)
  - Les arguments
    - source (choix possible : email ou sms)
- dans __Message__
  - Le message reçu

> Actuellement la gestion des messages Email n'est pas complete
> 
> N'hésitez pas à participer en indiquant les messages non encore gérés afin de les integrer
> Pour cela, il suffit de récupérer le message dans le log _Warning_ généré (cf. Diagnostique pour définir le niveau de log) et d'[ouvrir une demande sur le Github du plugin](https://github.com/mguyard/Jeedom-Diagral_eOne/issues/new?assignees=mguyard&labels=enhancement&template=message---ajout-du-support-d-un-nouveau-message.md&title=%5BMSG-ADD%5D)

# Caching

Afin d'éviter de trop requêter les serveurs Diagral, le plugin met en cache les informations suivantes :
-   Les groupes configurés dans l'alarme
-   Les scénarios configurés dans l'alarme

Le cache est regénéré - par une tâche CRON - automatiquement tous les dimanches à une heure définie aléatoirement lors de l'installation.

Vous pouvez forcer la regénération manuellement avec les commandes 
-   __Force Groups Refresh__ 
-   __Force Scenarios Refresh__

# Commandes

Il existe actuellement plusieurs commandes qui sont décrites ci-dessous :

## Action

-   __Rafraichir__ : Mise à jour du statut de l'Alarme
  
-   __Désactivation Totale__ : Arrêt de l'alarme

-   __Désactivation Partielle__ : Permet de désactiver un groupe de l'alarme
    -   _La désactivation d'un groupe ne peut être effectuée que lorsque l'alarme est activée (à l'exclusion du mode présence)_

-   __Activation Mode Presence__ : Activation avec le mode spécial Présence

-   __Activation Partielle__ : Activation de quelques groupes uniquement.
    -   _Le choix des groupes est définissable uniquement au travers d'une action dans un scénario_
    -   _Lors d'un 'Tester' sur la commande, seul le premier groupe de la liste déroulante (à savoir le moins long) sera executé_

-   __Importer Message__ : Collecte d'un message (mail ou SMS)

-   __Force Groups Refresh__ : Rafraichissement des fichiers de groupes
    -   _Le plugin génère des fichiers JSON (#pluginDirectory#/core/class/config/groups_*.json) afin d'éviter d'interroger très regulièrement le cloud Diagral et ainsi éviter de se faire Blacklister temporairement le compte.
    -   Un fichier JSON par équipement. Le * correspond au systemID indiqué sur chaque équipement
    -   Une CRON Diagral_eOne/generateJsonAllDevices se lance tous les dimanches pour mettre à jour ces fichiers (l'heure est définie aléatoirement à l'installation pour éviter les effets de masse sur le Cloud Diagral).

-   __Force Scenarios Refresh__ : Rafraichissement des fichiers de scénarios
    -   _Le plugin génère des fichiers JSON (#pluginDirectory#/core/class/config/scenarios_*.json) afin d'éviter d'interroger très regulièrement le cloud Diagral et ainsi éviter de se faire Blacklister temporairement le compte.
    -   Un fichier JSON par équipement. Le * correspond au systemID indiqué sur chaque équipement
    -   Une CRON Diagral_eOne/generateJsonAllDevices se lance tous les dimanches pour mettre à jour ces fichiers (l'heure est définie aléatoirement à l'installation pour éviter les effets de masse sur le Cloud Diagral).

-   __Lancement Scenario__ : Exécute un scénario

## Info

-   Statut : Statut de l'alarme
    -   Off : Alarme désactivée
    -   presence : Alarme en mode présence
    -   group : Alarme activée uniquement sur certains groupes
    -   tempogroup : Alarme en cours d'activation
-   Alarme déclenchée : 1 correspond à une alarme en cours. 0 dans les autres cas
-   Mises à jour disponibles : Indique le nombre de mises à jour disponibles sur l'alarme
-   Groupes Actifs : Liste des groupes actifs par leur nom (ex. Détecteur Ouverture Étage + Garage)
-   IMPORT - Dernier Message : Dernier message reçu par message (mail ou sms)
-   IMPORT - Derniere action : Dernière action reçue par message (mail ou sms)
    -   _Exemple "une mise à l'arrêt à distance"_
-   IMPORT - Derniere methode : Dernière methode reçue par message (mail ou sms)
    -   _Exemple "code d'accès principal"_
-   IMPORT - Dernier utilisateur : Dernière methode ayant intervenu sur l'alarme, reçue par message (mail ou sms)

# Dashboard

Le plugin inclus un dashboard qui permet de :
-   Connaitre le statut de l'alarme (y compris si une alarme est en cours)
-   Connaitre le nombre de mise à jour de l'alarme disponible
-   Activation totale de l'alarme
-   Activation partielle de l'alarme
-   Activation du mode présence
-   Désactivation totale de l'alarme
-   Désactivation partielle de l'alarme
-   Lancement d'un scénario

![Dashboard](../assets/images/Dashboard.png)

# Diagnostique

Le plugin inclus beaucoup de verbosité en mode DEBUG, il suffit alors de la configurer au niveau du plugin.
A partir de là, vous pouvez identifier les soucis que vous pouvez rencontrer.

![Debug Logs](../assets/images/DebugLogs.png)

# Suivi d'installation

> Dans le cadre des plugins gratuit, Jeedom ne trace pas les installations ou tout du moins pas les utilisateurs du plugin.
>
> Cependant, à plusieurs reprise ces informations m'ont manqué car j'aurais voulu pouvoir faire un sondage rapide aux utilisateurs du plugin, ou envoyer une information.
> 
> Par conséquent, voulant avoir un moyen de contacter les utilisateurs du plugin, deux choix s'offrait à moi :
> -   passer le plugin payant
> -   développer un système me permettant de palier à ce manque
> 
> Vous l'aurez compris, j'ai pris le choix **Numero 2**.

Les informations envoyées au developpeur sont :
-   Clé unique de votre installation Jeedom (code généré par Jeedom)
-   Votre login market
-   Votre adresse email (uniquement si vous la saisissez)

Vous pouvez choisir de désactiver l'envoi des informations ou bien n'envoyer que les informations anonymisées (clé unique Jeedom)

Vous avez aussi la possibilité de supprimer les informations stockés (action automatique à la désinstallation) en cliquant sur le bouton reservé à cet effet. Cette action désactive automatiquement l'envoi des données à venir.

Le login market permet de pouvoir vous contacter par [Community](https://community.jeedom.com) si besoin.

> <span style="color:red">__FONCTIONNALITE UNIQUEMENT EN BETA POUR LE MOMENT__</span>


# Roadmap

- Ajout de nouveaux messages SMS/EMAIL.
- Intégration dans Homebridge
- Génération automatique du scénario sur utilisation de reception SMS ou Email.

> C'est aussi vous qui aller donner la direction que prendra ce plugin, donc je suis à votre écoute
> 
> Plus une fonctionnalité sera demandée (si elle est réalisable), plus elle sera prioritaire au développement (pondéré par sa difficulté d'integration)
> Vous pouvez faire votre demande par en créant une demande "enhancement" [GitHub](https://github.com/mguyard/Jeedom-Diagral_eOne/issues/new) mais cela me fera plaisir si vous passez d'abord m'en parler sur le forum Jeedom.