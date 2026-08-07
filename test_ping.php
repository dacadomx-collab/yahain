<?php
// test_ping.php — Diagnóstico ultraligero de disponibilidad de servidor.
// Sin dependencias (no toca BD ni .env). Si esto responde 403, el bloqueo
// es de VHOST/hosting, no de la aplicación. Eliminar tras la auditoría.
header('Content-Type: text/plain; charset=UTF-8');
echo "pong " . date('Y-m-d H:i:s');
