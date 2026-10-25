<?php
// ONE HUB / EMAILSEND ONE: renombrar config.example.php a config.php
// Requiere una BASE DE DATOS MYSQL SEPARADA y VACÍA de los datos del cliente.
return [
  'app_name' => 'ONE HUB · EMAILSEND ONE',
  'app_url' => 'https://SU_DOMINIO/crm/emailsend',
  'app_key' => 'CAMBIAR_POR_CADENA_ALEATORIA_DE_64_CARACTERES',
  'demo_mode' => true, // Não envia emails ao instalar
  'seed_demo' => true, // Dados fictícios
  'db' => [
    'host' => 'localhost', 'port' => '3306',
    'name' => 'NOMBRE_DE_BD_EMAILSEND',
    'user' => 'USUARIO_DE_BD_EMAILSEND',
    'pass' => 'CONTRASENA_DE_BD_EMAILSEND',
  ],
];
