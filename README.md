# Documentation du Système de Gestion des Pauses

## Table des matières

1. [Introduction](#introduction)
2. [Architecture du système](#architecture-du-système)
3. [Fonctionnalités](#fonctionnalités)
4. [Flux de travail](#flux-de-travail)
5. [Base de données](#base-de-données)
6. [Guide d'utilisation](#guide-dutilisation)
7. [Dépannage](#dépannage)

## Introduction

Le Système de Gestion des Pauses est une application web conçue pour gérer les pauses des employés dans un environnement de travail. Il permet aux employés de réserver des créneaux de pause, d'activer leurs pauses au moment de les prendre, et aux administrateurs de suivre l'utilisation des pauses en temps réel.

## Architecture du système

L'application est développée en PHP avec une base de données MySQL. Elle utilise une architecture MVC simplifiée :

- **Modèle** : Fonctions dans `includes/functions.php` pour interagir avec la base de données
- **Vue** : Pages PHP avec HTML/CSS pour l'interface utilisateur
- **Contrôleur** : Logique de traitement dans les pages PHP

### Technologies utilisées

- PHP 7.4+
- MySQL 5.7+
- Bootstrap 5
- Font Awesome 6
- JavaScript (minimal)

## Fonctionnalités

### Pour les employés

1. **Réservation de pauses**

   - Réserver des créneaux de pause pour le matin et l'après-midi
   - Visualiser les créneaux disponibles et leur occupation

2. **Activation des pauses**
   - Activer une pause au moment de la prendre
   - Voir le temps restant pour une pause active
   - Voir l'historique des pauses prises

### Pour les administrateurs

1. **Tableau de bord**

   - Voir les pauses en cours en temps réel
   - Consulter les statistiques d'utilisation des pauses
   - Accéder à l'historique complet des pauses

2. **Gestion des employés**
   - Ajouter/consulter les employés
   - Voir les pauses réservées par employé

## Flux de travail

### Cycle de vie d'une pause

1. **Réservation** : L'employé réserve un créneau de pause (statut: `reserved`)
2. **Activation** : L'employé active sa pause au moment de la prendre (statut: `started`)
3. **Fin automatique** : La pause se termine automatiquement après 10 minutes (statut: `completed`)
4. **Alternatives** :
   - Si l'employé n'active pas sa pause, elle est marquée comme non prise (statut: `missed`)
   - Si l'employé active sa pause en retard, elle est marquée comme décalée (statut: `delayed`)

### Statuts des pauses

- `reserved` : Pause réservée mais pas encore activée
- `started` : Pause en cours
- `completed` : Pause terminée
- `missed` : Pause non prise
- `delayed` : Pause activée en retard

## Base de données

### Structure

- **admins** : Administrateurs du système
- **employees** : Employés
- **break_slots** : Créneaux de pause disponibles
- **break_reservations** : Réservations de pauses

### Schéma

\`\`\`sql
-- Table des administrateurs
CREATE TABLE admins (
id INT NOT NULL AUTO_INCREMENT,
username VARCHAR(50) NOT NULL,
password VARCHAR(255) NOT NULL,
created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
PRIMARY KEY (id),
UNIQUE KEY username (username)
);

-- Table des employés
CREATE TABLE employees (
id INT NOT NULL AUTO_INCREMENT,
name VARCHAR(100) NOT NULL,
created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
PRIMARY KEY (id),
UNIQUE KEY name (name)
);

-- Table des créneaux de pause
CREATE TABLE break_slots (
id INT NOT NULL AUTO_INCREMENT,
period ENUM('morning', 'afternoon') NOT NULL,
start_time TIME NOT NULL,
end_time TIME NOT NULL,
PRIMARY KEY (id),
UNIQUE KEY period_start_time (period, start_time)
);

-- Table des réservations de pause
CREATE TABLE break_reservations (
id INT NOT NULL AUTO_INCREMENT,
employee_id INT NOT NULL,
slot_id INT NOT NULL,
reservation_date DATE NOT NULL,
status ENUM('reserved', 'started', 'completed', 'missed', 'delayed') DEFAULT 'reserved',
start_timestamp DATETIME NULL,
end_timestamp DATETIME NULL,
created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
PRIMARY KEY (id),
UNIQUE KEY employee_slot_date (employee_id, slot_id, reservation_date),
FOREIGN KEY (employee_id) REFERENCES employees (id) ON DELETE CASCADE,
FOREIGN KEY (slot_id) REFERENCES break_slots (id) ON DELETE CASCADE
);
\`\`\`

## Guide d'utilisation

### Pour les employés

#### Réservation de pauses

1. Accédez à la page d'accueil
2. Entrez votre nom
3. Sélectionnez un créneau de pause du matin et/ou de l'après-midi
4. Cliquez sur "Réserver mes pauses"

#### Activation de pauses

1. Accédez à la page "Activer ma pause"
2. Entrez votre nom pour vous identifier
3. Vous verrez vos pauses réservées pour aujourd'hui
4. Au moment de prendre votre pause, cliquez sur "Activer ma pause"
5. Un compte à rebours de 10 minutes démarre
6. La pause se termine automatiquement après 10 minutes

### Pour les administrateurs

#### Connexion

1. Cliquez sur "Administration" dans la barre de navigation
2. Entrez vos identifiants (par défaut: admin/admin123)

#### Tableau de bord

- Visualisez les pauses en cours
- Consultez les statistiques d'utilisation
- Accédez à l'historique des pauses

#### Gestion des employés

- Ajoutez de nouveaux employés
- Consultez la liste des employés

## Dépannage

### Problèmes courants

#### Erreur 500

Si vous rencontrez une erreur 500, vérifiez :

1. Les permissions des fichiers
2. La connexion à la base de données
3. Les erreurs PHP dans le fichier `error_log.txt`

#### Pause non visible pour activation

Si un employé ne voit pas sa pause à activer :

1. Vérifiez que la pause a bien été réservée (page "Mes pauses")
2. Vérifiez que la date de réservation correspond à aujourd'hui
3. Vérifiez que la pause n'a pas déjà été activée ou marquée comme manquée

#### Problèmes de session

Si les informations d'un employé restent affichées pour un autre :

1. Utilisez le lien "Ce n'est pas vous ?" pour vous déconnecter
2. Effacez les cookies du navigateur
   \`\`\`
