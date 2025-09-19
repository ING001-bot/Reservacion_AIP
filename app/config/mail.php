<?php
return [
    'driver' => 'smtp',

    // Configuración de Gmail
    'host' => 'smtp.gmail.com',
    'port' => 465,
    'encryption' => 'ssl',

    // Tu cuenta Gmail de la institución
    'username' => 'juantomisstack1974@gmail.com',
    'password' => 'eohngnfmlntlkmlx', // 
    // Dirección y nombre del remitente
    'from_email' => 'juantomisstack1974@gmail.com',
    'from_name'  => 'Colegio Monseñor Juan Tomis Stack',

    // Debug SMTP (temporal, poner en false cuando todo funcione)
    'debug' => true,
];
