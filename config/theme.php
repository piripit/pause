<?php
// Configuration des thèmes par périmètre
$theme_config = [
    'campus' => [
        'color' => 'primary',
        'icon' => 'fa-university',
        'name' => 'Campus'
    ],
    'entreprise' => [
        'color' => 'primary',
        'icon' => 'fa-building',
        'name' => 'Entreprise'
    ],
    'asn' => [
        'color' => 'primary',
        'icon' => 'fa-shield-alt',
        'name' => 'ASN'
    ],
    'all' => [
        'color' => 'primary',
        'icon' => 'fa-coffee',
        'name' => 'Tous les périmètres'
    ]
];

// Fonction utilitaire pour obtenir les informations de thème
function getThemeInfo($perimeter)
{
    global $theme_config;
    return $theme_config[$perimeter] ?? $theme_config['all'];
}
