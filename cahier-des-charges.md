# Cahier des Charges - Système de Gestion des Pauses

## 1. Présentation du projet

### 1.1 Contexte

Le système de gestion des pauses est une application web destinée à organiser et suivre les pauses des employés dans un environnement de travail. Il permet de réserver des créneaux de pause, d'activer les pauses en temps réel, et de suivre leur utilisation.

### 1.2 Objectifs

- Permettre aux employés de réserver leurs pauses à l'avance
- Limiter le nombre d'employés en pause simultanément (3 maximum par créneau)
- Suivre en temps réel les pauses en cours
- Fournir des statistiques sur l'utilisation des pauses
- Assurer une gestion équitable des pauses

## 2. Spécifications fonctionnelles

### 2.1 Fonctionnalités pour les employés

#### 2.1.1 Réservation de pauses

- Consulter les créneaux disponibles pour la journée
- Réserver un créneau de pause du matin et/ou de l'après-midi
- Visualiser le taux d'occupation de chaque créneau
- Consulter l'historique de ses propres pauses

#### 2.1.2 Activation des pauses

- Activer une pause réservée au moment de la prendre
- Voir le temps restant pour une pause active
- Recevoir une notification lorsque la pause est sur le point de se terminer
- Possibilité d'activer une pause en retard (avec statut spécial)

### 2.2 Fonctionnalités pour les administrateurs

#### 2.2.1 Tableau de bord

- Visualiser les pauses en cours en temps réel
- Consulter les statistiques d'utilisation des pauses
- Filtrer l'historique des pauses par date
- Exporter les données pour analyse

#### 2.2.2 Gestion des employés

- Ajouter, modifier ou supprimer des employés
- Consulter l'historique des pauses par employé
- Gérer les droits d'accès

## 3. Spécifications techniques

### 3.1 Architecture

- Application web PHP/MySQL
- Interface responsive (compatible mobile et desktop)
- Mise à jour automatique des statuts des pauses

### 3.2 Base de données

- Table des administrateurs
- Table des employés
- Table des créneaux de pause
- Table des réservations de pause avec statuts

### 3.3 Sécurité

- Authentification pour les administrateurs
- Protection contre les injections SQL
- Validation des données côté serveur

## 4. Cycle de vie d'une pause

### 4.1 États possibles d'une pause

- **Réservée (reserved)** : Pause planifiée mais pas encore activée
- **En cours (started)** : Pause activée et en cours
- **Terminée (completed)** : Pause terminée après la durée prévue
- **Non prise (missed)** : Pause réservée mais jamais activée
- **Décalée (delayed)** : Pause activée en retard par rapport à l'horaire prévu

### 4.2 Transitions entre les états

1. L'employé réserve une pause → État **Réservée**
2. L'employé active sa pause → État **En cours**
3. Après 10 minutes, la pause se termine automatiquement → État **Terminée**
4. Si l'employé n'active pas sa pause avant la fin du créneau → État **Non prise**
5. Si l'employé active sa pause en retard → État **Décalée**

## 5. Interface utilisateur

### 5.1 Pages principales

- Page d'accueil (réservation de pauses)
- Page "Mes pauses" (historique des pauses de l'employé)
- Page "Activer ma pause" (activation des pauses réservées)
- Page de connexion administrateur
- Tableau de bord administrateur
- Page de gestion des employés

### 5.2 Éléments d'interface

- Formulaires de réservation et d'activation
- Tableaux pour l'affichage des données
- Indicateurs visuels pour les statuts des pauses
- Compteur de temps pour les pauses en cours

## 6. Contraintes et règles métier

### 6.1 Contraintes temporelles

- Une pause dure exactement 10 minutes
- Maximum 3 employés en pause simultanément
- Les créneaux sont espacés de 15 minutes

### 6.2 Règles d'activation

- Une pause peut être activée 5 minutes avant l'heure prévue
- Une pause activée en retard est marquée comme "décalée"
- Une pause non activée est automatiquement marquée comme "non prise"

## 7. Évolutions futures

### 7.1 Fonctionnalités à développer

- Système de notifications par email ou SMS
- Application mobile dédiée
- Intégration avec le système de pointage
- Rapports statistiques avancés
- Calendrier visuel des pauses

### 7.2 Améliorations techniques

- API REST pour intégration avec d'autres systèmes
- Authentification des employés
- Interface d'administration plus complète
- Optimisation des performances

## 8. Conclusion

Ce système de gestion des pauses offre une solution complète pour organiser et suivre les pauses des employés. Il permet d'optimiser la gestion du temps de travail tout en offrant une flexibilité aux employés pour prendre leurs pauses quand ils en ont besoin, dans le respect des contraintes organisationnelles.
\`\`\`
