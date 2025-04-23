# Guide d'utilisation - Administrateurs

Ce guide explique comment utiliser le système de gestion des pauses en tant qu'administrateur.

## Sommaire
1. [Connexion à l'interface d'administration](#connexion-à-linterface-dadministration)
2. [Utilisation du tableau de bord](#utilisation-du-tableau-de-bord)
3. [Gestion des employés](#gestion-des-employés)
4. [Dépannage](#dépannage)

## Connexion à l'interface d'administration

### Étape 1: Accéder à la page de connexion
Cliquez sur "Administration" dans la barre de navigation.

### Étape 2: Saisir vos identifiants
- Nom d'utilisateur: admin (par défaut)
- Mot de passe: admin123 (par défaut)

### Étape 3: Se connecter
Cliquez sur le bouton "Connexion".

## Utilisation du tableau de bord

### Vue d'ensemble
Le tableau de bord est divisé en trois sections principales:
1. Pauses en cours
2. Statistiques du jour
3. Historique des pauses

### Pauses en cours
Cette section affiche en temps réel les pauses qui sont actuellement actives:
- Nom de l'employé
- Période (matin/après-midi)
- Horaire prévu
- Statut de la pause
- Temps écoulé pour les pauses en cours

La page se rafraîchit automatiquement toutes les 60 secondes.

### Statistiques du jour
Cette section affiche:
- Nombre total de réservations
- Capacité totale
- Taux d'utilisation des créneaux

### Historique des pauses
Cette section permet de consulter l'historique des pauses par date:
1. Sélectionnez une date dans le calendrier
2. Cliquez sur "Afficher"
3. Consultez la liste des pauses pour cette date

Vous pouvez voir pour chaque pause:
- Nom de l'employé
- Période (matin/après-midi)
- Horaire prévu
- Statut
- Heure de début réelle
- Heure de fin réelle

## Gestion des employés

### Accéder à la gestion des employés
Cliquez sur "Gestion des employés" dans la barre de navigation.

### Ajouter un employé
1. Remplissez le formulaire "Ajouter un employé"
2. Entrez le nom de l'employé
3. Cliquez sur "Ajouter"

### Consulter la liste des employés
La liste des employés s'affiche à droite avec:
- ID de l'employé
- Nom
- Date d'ajout

## Dépannage

### Réinitialisation du compte administrateur
Si vous ne pouvez pas vous connecter:
1. Accédez à la page "reset-admin.php"
2. Suivez les instructions pour réinitialiser le compte administrateur

### Diagnostic des problèmes
Pour diagnostiquer des problèmes techniques:
1. Accédez à la page "debug-info.php"
2. Consultez les informations sur:
   - L'état de la connexion à la base de données
   - Les tables de l'application
   - L'environnement PHP
   - Les permissions des dossiers

### Journaux d'erreurs
En cas de problème, consultez le fichier "error_log.txt" qui contient les erreurs enregistrées par l'application.
\`\`\`

Maintenant, créons les diagrammes au format PNG :
