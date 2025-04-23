# Documentation complète du système de gestion des pauses

## Table des matières

1. [Introduction](#introduction)
2. [Architecture du système](#architecture-du-système)
3. [Structure de la base de données](#structure-de-la-base-de-données)
4. [Flux de travail](#flux-de-travail)
5. [Fonctionnalités principales](#fonctionnalités-principales)
6. [Guide d'utilisation](#guide-dutilisation)
7. [Dépannage](#dépannage)
8. [Évolutions futures](#évolutions-futures)

## Introduction

Le système de gestion des pauses est une application web développée en PHP/MySQL qui permet aux employés de réserver et d'activer leurs pauses quotidiennes, et aux administrateurs de suivre l'utilisation des pauses en temps réel.

### Objectifs du système

- Permettre aux employés de réserver leurs pauses à l'avance
- Limiter le nombre d'employés en pause simultanément (3 maximum par créneau)
- Suivre en temps réel les pauses en cours
- Fournir des statistiques sur l'utilisation des pauses
- Assurer une gestion équitable des pauses

## Architecture du système

Le système est basé sur une architecture MVC simplifiée avec les composants suivants:

![Architecture du système](../diagrammes/architecture-systeme.png)

### Composants principaux

1. **Interface utilisateur (Vues)**
   - Pages PHP avec HTML/CSS (Bootstrap 5)
   - Formulaires de réservation et d'activation
   - Tableaux de bord et rapports

2. **Logique métier (Contrôleurs)**
   - Traitement des formulaires
   - Gestion des sessions
   - Validation des données

3. **Accès aux données (Modèle)**
   - Fonctions dans `includes/functions.php`
   - Requêtes SQL préparées
   - Gestion des erreurs

4. **Base de données MySQL**
   - Tables pour les administrateurs, employés, créneaux et réservations
   - Contraintes d'intégrité référentielle
   - Indexation pour les performances

## Structure de la base de données

Le système utilise une base de données MySQL avec quatre tables principales:

![Schéma de la base de données](../diagrammes/base-donnees.png)

### Description des tables

1. **admins**
   - Stocke les informations des administrateurs du système
   - Champs: id, username, password (haché), created_at

2. **employees**
   - Stocke les informations des employés
   - Champs: id, name, created_at

3. **break_slots**
   - Définit les créneaux de pause disponibles
   - Champs: id, period (matin/après-midi), start_time, end_time

4. **break_reservations**
   - Enregistre les réservations de pause
   - Champs: id, employee_id, slot_id, reservation_date, status, start_timestamp, end_timestamp, created_at
   - Status peut être: reserved, started, completed, missed, delayed

## Flux de travail

### Cycle de vie d'une pause

![Cycle de vie d'une pause](../diagrammes/cycle-vie-pause.png)

### Processus de réservation et d'activation

![Processus de réservation et d'activation](../diagrammes/processus-reservation-activation.png)

## Fonctionnalités principales

### Interface employé

![Navigation employé](../diagrammes/navigation-employe.png)

1. **Réservation de pauses**
   - Sélection des créneaux disponibles
   - Visualisation de l'occupation des créneaux
   - Confirmation de réservation

2. **Consultation des pauses**
   - Historique des pauses réservées
   - Statut des pauses (réservée, en cours, terminée, etc.)

3. **Activation des pauses**
   - Activation au moment de prendre la pause
   - Compte à rebours pendant la pause
   - Statut en temps réel

### Interface administrateur

![Navigation administrateur](../diagrammes/navigation-admin.png)

1. **Tableau de bord**
   - Vue en temps réel des pauses en cours
   - Statistiques d'utilisation
   - Filtrage par date

2. **Gestion des employés**
   - Ajout de nouveaux employés
   - Liste des employés existants

## Guide d'utilisation

### Pour les employés

#### Réservation d'une pause

1. Accédez à la page d'accueil (`index.php`)
2. Entrez votre nom dans le champ prévu
3. Sélectionnez un créneau de pause du matin et/ou de l'après-midi
4. Cliquez sur "Réserver mes pauses"
5. Une confirmation s'affiche si la réservation est réussie

![Processus de réservation](../diagrammes/processus-reservation.png)

#### Activation d'une pause

1. Accédez à la page "Activer ma pause" (`activate-break.php`)
2. Entrez votre nom pour vous identifier
3. Vous verrez vos pauses réservées pour aujourd'hui
4. Au moment de prendre votre pause, cliquez sur "Activer ma pause"
5. Un compte à rebours de 10 minutes démarre
6. La pause se termine automatiquement après 10 minutes

![Processus d'activation](../diagrammes/processus-activation.png)

### Pour les administrateurs

#### Connexion

1. Cliquez sur "Administration" dans la barre de navigation
2. Entrez vos identifiants (par défaut: admin/admin123)
3. Vous êtes redirigé vers le tableau de bord administrateur

#### Tableau de bord

- La section "Pauses en cours" affiche les pauses actives en temps réel
- La section "Statistiques du jour" montre l'utilisation des pauses
- La section "Historique des pauses" permet de consulter les pauses par date

#### Gestion des employés

1. Cliquez sur "Gestion des employés" dans la barre de navigation
2. Pour ajouter un employé, remplissez le formulaire à gauche
3. La liste des employés existants s'affiche à droite

## Dépannage

### Problèmes courants et solutions

![Arbre de décision pour le dépannage](../diagrammes/depannage.png)

#### Erreur 500
- Vérifiez les permissions des fichiers
- Assurez-vous que la connexion à la base de données fonctionne
- Consultez le fichier `error_log.txt` pour plus de détails

#### Pause non visible pour activation
- Vérifiez que la pause a bien été réservée (page "Mes pauses")
- Assurez-vous que la date de réservation correspond à aujourd'hui
- Vérifiez que la pause n'a pas déjà été activée ou marquée comme manquée

#### Problèmes de session
- Utilisez le lien "Ce n'est pas vous ?" pour vous déconnecter
- Effacez les cookies du navigateur
- Redémarrez le navigateur si nécessaire

## Évolutions futures

![Roadmap des fonctionnalités](../diagrammes/roadmap.png)

### Fonctionnalités planifiées

1. **Authentification des employés**
   - Système de connexion sécurisé pour les employés
   - Profils personnalisés avec préférences

2. **Système de notifications**
   - Alertes par email ou SMS
   - Rappels avant les pauses réservées

3. **Application mobile**
   - Version mobile native
   - Notifications push

4. **Intégration avec d'autres systèmes**
   - Système de pointage
   - Calendrier d'entreprise

5. **Rapports avancés**
   - Analyses statistiques détaillées
   - Exportation des données

6. **Calendrier visuel**
   - Vue calendrier des pauses
   - Planification visuelle

---

## Annexe: Structure des fichiers

![Structure des fichiers](../diagrammes/structure-fichiers.png)

### Fichiers principaux

- **index.php**: Page d'accueil et réservation de pauses
- **my-breaks.php**: Consultation des pauses réservées
- **activate-break.php**: Activation des pauses
- **admin-login.php**: Connexion administrateur
- **admin/dashboard.php**: Tableau de bord administrateur
- **admin/employees.php**: Gestion des employés
- **includes/functions.php**: Fonctions d'accès aux données
- **config/database.php**: Configuration de la base de données
\`\`\`
