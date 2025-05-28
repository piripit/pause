# 📚 Documentation Technique - Système de Gestion des Pauses

## 📋 Table des Matières

1. [Vue d'Ensemble](#vue-densemble)
2. [Architecture Système](#architecture-système)
3. [Base de Données](#base-de-données)
4. [API et Endpoints](#api-et-endpoints)
5. [Sécurité](#sécurité)
6. [Configuration](#configuration)
7. [Déploiement](#déploiement)
8. [Maintenance](#maintenance)
9. [Dépannage](#dépannage)

---

## 🏗️ Vue d'Ensemble

### Architecture Générale

```
┌─────────────────────────────────────────────────────────────┐
│                    COUCHE PRÉSENTATION                     │
├─────────────────────────────────────────────────────────────┤
│ • Interface Employé (campus.php, entreprise.php, asn.php)  │
│ • Interface Admin (admin/dashboard.php, admin/slots.php)   │
│ • AJAX (ajax/refresh-slots.php)                            │
│ • Assets (CSS, JS, Images)                                 │
└─────────────────────────────────────────────────────────────┘
                                │
                                ▼
┌─────────────────────────────────────────────────────────────┐
│                     COUCHE MÉTIER                          │
├─────────────────────────────────────────────────────────────┤
│ • Logique Applicative (includes/functions.php)            │
│ • Validation des Données                                   │
│ • Gestion des Sessions                                     │
│ • Contrôle d'Accès                                        │
└─────────────────────────────────────────────────────────────┘
                                │
                                ▼
┌─────────────────────────────────────────────────────────────┐
│                     COUCHE DONNÉES                         │
├─────────────────────────────────────────────────────────────┤
│ • Base MySQL (employees, break_slots, break_reservations)  │
│ • Connexions PDO/MySQLi                                   │
│ • Transactions et Intégrité                               │
└─────────────────────────────────────────────────────────────┘
```

### Technologies Utilisées

| Composant           | Technologie | Version | Rôle                     |
| ------------------- | ----------- | ------- | ------------------------ |
| **Backend**         | PHP         | 8.0+    | Logique serveur          |
| **Base de données** | MySQL       | 8.0+    | Stockage des données     |
| **Frontend**        | HTML5/CSS3  | -       | Structure et style       |
| **Framework CSS**   | Bootstrap   | 5.3     | Interface responsive     |
| **JavaScript**      | ES6+        | -       | Interactivité client     |
| **AJAX**            | Fetch API   | -       | Communication asynchrone |
| **Serveur Web**     | Apache      | 2.4+    | Serveur HTTP             |

---

## 🏗️ Architecture Système

### Structure des Fichiers

```
pause/
├── 📁 admin/                    # Interface d'administration
│   ├── dashboard.php           # Tableau de bord principal
│   ├── employees.php           # Gestion des employés
│   ├── slots.php              # Configuration des créneaux
│   ├── breaks.php             # Suivi des pauses
│   ├── logout.php             # Déconnexion
│   ├── switch-context.php     # Changement de périmètre
│   ├── init_slots.php         # Initialisation des créneaux
│   └── create_default_slots.php # Création des créneaux par défaut
├── 📁 ajax/                    # Endpoints AJAX
│   └── refresh-slots.php      # Actualisation temps réel
├── 📁 assets/                  # Ressources statiques
│   ├── css/
│   │   └── style.css          # Styles personnalisés
│   ├── js/                    # Scripts JavaScript
│   └── images/                # Images et icônes
├── 📁 config/                  # Configuration
│   ├── database.php           # Connexion base de données
│   └── theme.php              # Configuration des thèmes
├── 📁 includes/                # Fonctions métier
│   └── functions.php          # Logique applicative
├── 📁 documentations/          # Documentation
│   ├── guide-utilisation-admin.md
│   ├── guide-utilisation-employe.md
│   └── documentation-technique.md
├── campus.php                  # Interface périmètre Campus
├── entreprise.php             # Interface périmètre Entreprise
├── asn.php                    # Interface périmètre ASN
├── index.php                  # Page d'accueil
├── activate-break.php         # Activation des pauses
├── my-breaks.php              # Consultation des pauses
├── admin-login.php            # Authentification admin
├── reset-admin.php            # Réinitialisation admin
├── debug-info.php             # Informations de débogage
├── test-activation.php        # Tests de diagnostic
├── database.sql               # Structure de la base
└── README.md                  # Documentation projet
```

### Flux de Données

#### Réservation de Pause

```
Employé → Interface Périmètre → Validation → Base de Données
    ↓
Confirmation → Mise à jour AJAX → Autres Interfaces
```

#### Activation de Pause

```
Employé → activate-break.php → Validation Temporelle → Mise à jour Statut
    ↓
Chronomètre → Auto-completion → Statistiques
```

#### Administration

```
Admin → Interface Admin → Modification Créneaux → Base de Données
    ↓
Propagation AJAX → Interfaces Employé → Mise à jour Temps Réel
```

---

## 🗄️ Base de Données

### Schéma Relationnel

```sql
-- Table des employés
CREATE TABLE employees (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table des créneaux de pause
CREATE TABLE break_slots (
    id INT AUTO_INCREMENT PRIMARY KEY,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    period ENUM('morning', 'afternoon') NOT NULL,
    quota INT NOT NULL DEFAULT 3,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    perimeter ENUM('campus', 'entreprise', 'asn', 'all') NOT NULL DEFAULT 'all'
);

-- Table des réservations
CREATE TABLE break_reservations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    slot_id INT NOT NULL,
    reservation_date DATE NOT NULL,
    status ENUM('reserved', 'started', 'completed', 'missed', 'delayed') DEFAULT 'reserved',
    start_timestamp TIMESTAMP NULL,
    end_timestamp TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    FOREIGN KEY (slot_id) REFERENCES break_slots(id) ON DELETE CASCADE,
    UNIQUE KEY unique_employee_slot_date (employee_id, slot_id, reservation_date)
);

-- Table des administrateurs
CREATE TABLE admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    perimeter ENUM('campus', 'entreprise', 'asn', 'all') NOT NULL DEFAULT 'all',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### Index et Optimisations

```sql
-- Index pour les performances
CREATE INDEX idx_reservations_date ON break_reservations(reservation_date);
CREATE INDEX idx_reservations_status ON break_reservations(status);
CREATE INDEX idx_slots_period ON break_slots(period);
CREATE INDEX idx_slots_perimeter ON break_slots(perimeter);
CREATE INDEX idx_employees_name ON employees(name);

-- Index composites pour les requêtes fréquentes
CREATE INDEX idx_reservations_employee_date ON break_reservations(employee_id, reservation_date);
CREATE INDEX idx_reservations_slot_date ON break_reservations(slot_id, reservation_date);
CREATE INDEX idx_slots_period_perimeter ON break_slots(period, perimeter);
```

### Contraintes Métier

```sql
-- Contrainte : Un employé ne peut avoir qu'une pause par période par jour
ALTER TABLE break_reservations
ADD CONSTRAINT unique_employee_period_date
UNIQUE (employee_id, reservation_date,
    (SELECT period FROM break_slots WHERE id = slot_id));

-- Contrainte : Les heures de fin doivent être après les heures de début
ALTER TABLE break_slots
ADD CONSTRAINT check_time_order
CHECK (end_time > start_time);

-- Contrainte : Le quota doit être positif
ALTER TABLE break_slots
ADD CONSTRAINT check_positive_quota
CHECK (quota > 0);
```

---

## 🔌 API et Endpoints

### Endpoints AJAX

#### `ajax/refresh-slots.php`

**Méthode** : GET  
**Paramètres** :

- `perimeter` (string) : Périmètre à filtrer (campus|entreprise|asn|all)

**Réponse** :

```json
{
    "success": true,
    "morning_slots": [
        {
            "id": 1,
            "start_time": "09:00:00",
            "end_time": "09:10:00",
            "count": 2,
            "quota": 3,
            "is_active": true
        }
    ],
    "afternoon_slots": [...],
    "timestamp": 1640995200
}
```

**Codes d'erreur** :

```json
{
  "success": false,
  "error": "Périmètre invalide"
}
```

### Fonctions API Principales

#### `includes/functions.php`

##### Gestion des Créneaux

```php
// Récupérer les créneaux par période et périmètre
function getBreakSlots($period, $perimeter = 'all')

// Obtenir le nombre de réservations pour un créneau
function getSlotCount($slot_id)

// Obtenir le quota maximum d'un créneau
function getSlotQuota($slot_id)

// Vérifier si un créneau est complet
function isSlotFull($slot_id)

// Vérifier si un créneau est actif
function isSlotActive($slot_id, $perimeter = 'all')
```

##### Gestion des Employés

```php
// Récupérer un employé par nom
function getEmployeeByName($name)

// Récupérer un employé par ID
function getEmployeeById($id)

// Ajouter un nouvel employé
function addEmployee($name)

// Récupérer tous les employés
function getAllEmployees()
```

##### Gestion des Réservations

```php
// Réserver une pause
function reserveBreak($employee_id, $slot_id)

// Activer une pause
function activateBreak($reservation_id)

// Récupérer les pauses d'un employé
function getEmployeeBreaks($employee_id)

// Récupérer les pauses actives d'un employé
function getEmployeeActiveBreaks($employee_id)

// Récupérer les pauses à venir d'un employé
function getEmployeeUpcomingBreaks($employee_id)
```

##### Statistiques et Monitoring

```php
// Récupérer les pauses en cours
function getCurrentBreaks()

// Récupérer l'historique des pauses
function getBreakHistory($date = null)

// Récupérer les statistiques générales
function getBreakStats($date = null)

// Récupérer les statistiques détaillées
function getDetailedBreakStats($date, $perimeter = 'all')
```

---

## 🔐 Sécurité

### Authentification

#### Système de Login Admin

```php
// Vérification des identifiants
$stmt = $conn->prepare("SELECT id, username, password, perimeter
                       FROM admins WHERE username = ?");
$stmt->bind_param("s", $username);

// Vérification du mot de passe
if (password_verify($password, $admin['password'])) {
    $_SESSION['admin_id'] = $admin['id'];
    $_SESSION['admin_username'] = $admin['username'];
    $_SESSION['admin_perimeter'] = $admin['perimeter'];
}
```

#### Gestion des Sessions

```php
// Vérification de session admin
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../admin-login.php');
    exit;
}

// Timeout de session (30 minutes)
if (isset($_SESSION['last_activity']) &&
    (time() - $_SESSION['last_activity'] > 1800)) {
    session_unset();
    session_destroy();
    header('Location: admin-login.php');
    exit;
}
$_SESSION['last_activity'] = time();
```

### Protection contre les Attaques

#### Injection SQL

```php
// Utilisation de requêtes préparées
$stmt = $conn->prepare("SELECT * FROM employees WHERE name = ?");
$stmt->bind_param("s", $employee_name);
$stmt->execute();
```

#### XSS (Cross-Site Scripting)

```php
// Échappement des données de sortie
echo htmlspecialchars($employee['name'], ENT_QUOTES, 'UTF-8');

// Validation des entrées
function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}
```

#### CSRF (Cross-Site Request Forgery)

```php
// Génération de token CSRF
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Vérification du token
if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    die('Token CSRF invalide');
}
```

### Contrôle d'Accès

#### Matrice des Permissions

```php
function checkPermission($admin_perimeter, $requested_perimeter) {
    // Admin global peut tout faire
    if ($admin_perimeter === 'all') {
        return true;
    }

    // Admin spécifique ne peut gérer que son périmètre
    return $admin_perimeter === $requested_perimeter;
}
```

#### Isolation des Données

```php
// Filtrage par périmètre employé
function filterByEmployeePerimeter($employees, $perimeter) {
    if ($perimeter === 'all') return $employees;

    $prefix = '[' . strtoupper($perimeter) . ']';
    return array_filter($employees, function($emp) use ($prefix) {
        return strpos($emp['name'], $prefix) === 0;
    });
}
```

---

## ⚙️ Configuration

### Configuration Base de Données

#### `config/database.php`

```php
<?php
// Configuration de la base de données
define('DB_HOST', 'localhost');
define('DB_NAME', 'pause_management');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

function getConnection() {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]);
        return $pdo;
    } catch (PDOException $e) {
        error_log("Erreur de connexion BDD: " . $e->getMessage());
        return false;
    }
}
?>
```

### Configuration des Thèmes

#### `config/theme.php`

```php
<?php
$theme_config = [
    'campus' => [
        'color' => 'primary',
        'icon' => 'fa-university',
        'name' => 'Campus'
    ],
    'entreprise' => [
        'color' => 'success',
        'icon' => 'fa-building',
        'name' => 'Entreprise'
    ],
    'asn' => [
        'color' => 'danger',
        'icon' => 'fa-shield-alt',
        'name' => 'ASN'
    ],
    'all' => [
        'color' => 'primary',
        'icon' => 'fa-coffee',
        'name' => 'Tous les périmètres'
    ]
];

function getThemeInfo($perimeter) {
    global $theme_config;
    return $theme_config[$perimeter] ?? $theme_config['all'];
}
?>
```

### Variables d'Environnement

#### `.env` (optionnel)

```env
# Base de données
DB_HOST=localhost
DB_NAME=pause_management
DB_USER=root
DB_PASS=

# Application
APP_ENV=production
APP_DEBUG=false
APP_TIMEZONE=Europe/Paris

# Sécurité
SESSION_TIMEOUT=1800
CSRF_TOKEN_LENGTH=32

# AJAX
REFRESH_INTERVAL=30000
```

---

## 🚀 Déploiement

### Prérequis Serveur

#### Configuration Minimale

- **OS** : Linux (Ubuntu 20.04+ recommandé) ou Windows Server
- **Serveur Web** : Apache 2.4+ ou Nginx 1.18+
- **PHP** : 8.0+ avec extensions mysqli, json, session
- **Base de données** : MySQL 8.0+ ou MariaDB 10.5+
- **RAM** : 2GB minimum, 4GB recommandé
- **Stockage** : 1GB pour l'application + logs

#### Extensions PHP Requises

```bash
# Vérifier les extensions
php -m | grep -E "(mysqli|json|session|pdo|pdo_mysql)"

# Installer si manquantes (Ubuntu)
sudo apt-get install php-mysqli php-json php-pdo php-pdo-mysql
```

### Procédure de Déploiement

#### 1. Préparation de l'Environnement

```bash
# Créer le répertoire de l'application
sudo mkdir -p /var/www/pause
sudo chown www-data:www-data /var/www/pause

# Cloner ou copier les fichiers
git clone [repository] /var/www/pause
# ou
cp -r pause/* /var/www/pause/
```

#### 2. Configuration Apache

```apache
# /etc/apache2/sites-available/pause.conf
<VirtualHost *:80>
    ServerName pause.mondomaine.com
    DocumentRoot /var/www/pause

    <Directory /var/www/pause>
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/pause_error.log
    CustomLog ${APACHE_LOG_DIR}/pause_access.log combined
</VirtualHost>
```

#### 3. Configuration Base de Données

```bash
# Créer la base de données
mysql -u root -p
CREATE DATABASE pause_management CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'pause_user'@'localhost' IDENTIFIED BY 'mot_de_passe_fort';
GRANT ALL PRIVILEGES ON pause_management.* TO 'pause_user'@'localhost';
FLUSH PRIVILEGES;

# Importer la structure
mysql -u pause_user -p pause_management < /var/www/pause/database.sql
```

#### 4. Configuration de l'Application

```bash
# Modifier la configuration
nano /var/www/pause/config/database.php

# Définir les permissions
sudo chown -R www-data:www-data /var/www/pause
sudo chmod -R 755 /var/www/pause
sudo chmod 644 /var/www/pause/config/database.php
```

#### 5. Initialisation

```bash
# Créer les créneaux par défaut
php /var/www/pause/admin/init_slots.php

# Créer un administrateur
php /var/www/pause/reset-admin.php
```

### Configuration SSL (Recommandé)

#### Avec Let's Encrypt

```bash
# Installer Certbot
sudo apt-get install certbot python3-certbot-apache

# Obtenir le certificat
sudo certbot --apache -d pause.mondomaine.com

# Renouvellement automatique
sudo crontab -e
0 12 * * * /usr/bin/certbot renew --quiet
```

---

## 🔧 Maintenance

### Tâches de Maintenance Régulières

#### Quotidiennes

```bash
# Nettoyage des logs (garder 30 jours)
find /var/log/apache2/ -name "pause_*.log" -mtime +30 -delete

# Vérification de l'espace disque
df -h /var/www/pause

# Sauvegarde de la base de données
mysqldump -u pause_user -p pause_management > backup_$(date +%Y%m%d).sql
```

#### Hebdomadaires

```bash
# Optimisation des tables
mysql -u pause_user -p -e "OPTIMIZE TABLE pause_management.break_reservations;"
mysql -u pause_user -p -e "OPTIMIZE TABLE pause_management.employees;"

# Nettoyage des anciennes réservations (> 6 mois)
mysql -u pause_user -p -e "DELETE FROM pause_management.break_reservations
                          WHERE reservation_date < DATE_SUB(NOW(), INTERVAL 6 MONTH);"
```

#### Mensuelles

```bash
# Mise à jour du système
sudo apt-get update && sudo apt-get upgrade

# Vérification des logs d'erreur
tail -100 /var/log/apache2/pause_error.log

# Analyse des performances
mysql -u pause_user -p -e "SHOW PROCESSLIST;"
```

### Scripts de Maintenance

#### `maintenance/cleanup.php`

```php
<?php
// Script de nettoyage automatique
require_once '../config/database.php';

// Supprimer les anciennes réservations
$conn = getConnection();
$stmt = $conn->prepare("DELETE FROM break_reservations
                       WHERE reservation_date < DATE_SUB(NOW(), INTERVAL 6 MONTH)");
$stmt->execute();
echo "Anciennes réservations supprimées: " . $stmt->rowCount() . "\n";

// Optimiser les tables
$tables = ['break_reservations', 'employees', 'break_slots', 'admins'];
foreach ($tables as $table) {
    $conn->query("OPTIMIZE TABLE $table");
    echo "Table $table optimisée\n";
}
?>
```

#### `maintenance/backup.sh`

```bash
#!/bin/bash
# Script de sauvegarde automatique

DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/var/backups/pause"
DB_NAME="pause_management"
DB_USER="pause_user"

# Créer le répertoire de sauvegarde
mkdir -p $BACKUP_DIR

# Sauvegarde de la base de données
mysqldump -u $DB_USER -p$DB_PASS $DB_NAME > $BACKUP_DIR/db_$DATE.sql

# Sauvegarde des fichiers
tar -czf $BACKUP_DIR/files_$DATE.tar.gz /var/www/pause

# Supprimer les sauvegardes de plus de 30 jours
find $BACKUP_DIR -name "*.sql" -mtime +30 -delete
find $BACKUP_DIR -name "*.tar.gz" -mtime +30 -delete

echo "Sauvegarde terminée: $DATE"
```

### Monitoring et Alertes

#### Script de Monitoring

```php
<?php
// monitoring/health_check.php
require_once '../config/database.php';

$status = [
    'database' => false,
    'disk_space' => false,
    'response_time' => 0
];

// Test de connexion BDD
$start_time = microtime(true);
$conn = getConnection();
if ($conn) {
    $status['database'] = true;
}
$status['response_time'] = round((microtime(true) - $start_time) * 1000, 2);

// Vérification espace disque
$disk_free = disk_free_space('/var/www/pause');
$disk_total = disk_total_space('/var/www/pause');
$disk_usage = (1 - $disk_free / $disk_total) * 100;
$status['disk_space'] = $disk_usage < 90;

// Retourner le statut
header('Content-Type: application/json');
echo json_encode($status);
?>
```

---

## 🔍 Dépannage

### Problèmes Courants

#### 1. Erreur de Connexion Base de Données

**Symptômes** : Page blanche, erreur 500
**Diagnostic** :

```bash
# Vérifier le service MySQL
sudo systemctl status mysql

# Tester la connexion
mysql -u pause_user -p pause_management
```

**Solutions** :

- Vérifier les identifiants dans `config/database.php`
- Redémarrer MySQL : `sudo systemctl restart mysql`
- Vérifier les permissions utilisateur

#### 2. AJAX ne Fonctionne Pas

**Symptômes** : Créneaux ne se mettent pas à jour
**Diagnostic** :

```javascript
// Dans la console du navigateur
fetch("ajax/refresh-slots.php?perimeter=campus")
  .then((response) => response.json())
  .then((data) => console.log(data));
```

**Solutions** :

- Vérifier les permissions du fichier `ajax/refresh-slots.php`
- Contrôler les logs d'erreur Apache
- Valider la syntaxe JSON retournée

#### 3. Sessions qui Expirent Rapidement

**Symptômes** : Déconnexions fréquentes
**Diagnostic** :

```php
// Vérifier la configuration PHP
echo "session.gc_maxlifetime: " . ini_get('session.gc_maxlifetime');
echo "session.cookie_lifetime: " . ini_get('session.cookie_lifetime');
```

**Solutions** :

- Augmenter `session.gc_maxlifetime` dans php.ini
- Vérifier l'espace disque pour les fichiers de session
- Configurer un répertoire de session dédié

#### 4. Performance Lente

**Symptômes** : Pages qui se chargent lentement
**Diagnostic** :

```sql
-- Analyser les requêtes lentes
SHOW PROCESSLIST;
SHOW STATUS LIKE 'Slow_queries';

-- Vérifier les index
EXPLAIN SELECT * FROM break_reservations
WHERE reservation_date = CURDATE();
```

**Solutions** :

- Ajouter des index sur les colonnes fréquemment utilisées
- Optimiser les requêtes SQL
- Activer le cache de requêtes MySQL

### Logs et Debugging

#### Activation du Debug

```php
// En tête des fichiers PHP pour le debug
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Logging personnalisé
function logError($message, $context = []) {
    $logFile = __DIR__ . '/logs/error.log';
    $timestamp = date('Y-m-d H:i:s');
    $contextStr = !empty($context) ? ' - Context: ' . json_encode($context) : '';
    $logMessage = "[$timestamp] $message$contextStr\n";
    file_put_contents($logFile, $logMessage, FILE_APPEND);
}
```

#### Analyse des Logs

```bash
# Logs Apache
tail -f /var/log/apache2/pause_error.log

# Logs MySQL
tail -f /var/log/mysql/error.log

# Logs PHP
tail -f /var/log/php/error.log

# Logs application
tail -f /var/www/pause/logs/error.log
```

### Outils de Diagnostic

#### `debug-info.php`

Fournit des informations complètes sur :

- Configuration PHP
- État de la base de données
- Permissions des fichiers
- Variables d'environnement

#### `test-activation.php`

Teste spécifiquement :

- Connexion base de données
- Fonctions d'activation
- État des créneaux
- Intégrité des données

### Procédures de Récupération

#### Restauration de Base de Données

```bash
# Arrêter l'application
sudo systemctl stop apache2

# Restaurer la sauvegarde
mysql -u pause_user -p pause_management < backup_20241201.sql

# Redémarrer l'application
sudo systemctl start apache2
```

#### Récupération de Fichiers

```bash
# Restaurer les fichiers depuis la sauvegarde
cd /var/www
sudo tar -xzf /var/backups/pause/files_20241201.tar.gz

# Rétablir les permissions
sudo chown -R www-data:www-data pause/
sudo chmod -R 755 pause/
```

---

_Documentation technique maintenue à jour - Version 1.0_  
_Dernière mise à jour : Décembre 2024_
