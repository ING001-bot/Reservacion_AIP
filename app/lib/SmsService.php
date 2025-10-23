<?php
namespace App\Lib;

require_once __DIR__ . '/../../vendor/autoload.php';

use Twilio\Rest\Client;
use Exception;

class SmsService {
    private $client;
    private $fromNumber;

    public function __construct() {
        $config = require __DIR__ . '/../config/twilio.php';
        $sid = trim((string)($config['account_sid'] ?? ''));
        $token = trim((string)($config['auth_token'] ?? ''));
        $this->fromNumber = trim((string)($config['from_number'] ?? ''));

        if ($sid === '' || $token === '' || $this->fromNumber === '') {
            throw new Exception('Twilio no configurado. Completa account_sid, auth_token y from_number en app/config/twilio.php');
        }
        $this->client = new Client($sid, $token);
    }

    /**
     * Envía un mensaje SMS
     * 
     * @param string $to Número de teléfono del destinatario (con código de país)
     * @param string $message Contenido del mensaje
     * @return array Resultado del envío
     */
    public function sendSms($to, $message) {
        try {
            // Normalizar número (E.164, Perú por defecto si aplica)
            $to = $this->normalizeNumber($to);
            $twMsg = $this->client->messages->create($to, [
                'from' => $this->fromNumber,
                'body' => $message
            ]);
            return [
                'success' => true,
                'message' => 'Mensaje enviado correctamente',
                'sid' => $twMsg->sid,
                'status' => $twMsg->status
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'code' => $e->getCode()
            ];
        }
    }

    /**
     * Normaliza números al formato E.164. Por defecto asume Perú (+51) si no se provee prefijo internacional.
     */
    private function normalizeNumber(string $to): string {
        // quitar espacios, guiones y paréntesis
        $n = preg_replace('/[\s\-()]/', '', trim($to));
        if ($n === '') return $n;
        if ($n[0] === '+') return $n; // ya en internacional

        // Si ya viene con 51 al inicio sin '+', agregarlo
        if (preg_match('/^51\d{9}$/', $n)) {
            return '+'.$n;
        }
        // Móviles Perú suelen tener 9 dígitos. Anteponer +51
        if (preg_match('/^\d{9}$/', $n)) {
            return '+51'.$n;
        }
        // Si empieza con 0, quitarlo y asumir Perú
        if ($n[0] === '0') {
            $n = ltrim($n, '0');
            if (preg_match('/^\d{9}$/', $n)) {
                return '+51'.$n;
            }
        }
        // Fallback: devolver como está, Twilio validará
        return $to;
    }

    /**
     * Verifica si el servicio está configurado correctamente
     * 
     * @return array Resultado de la verificación
     */
    public function verifyConnection() {
        try {
            $account = $this->client->api->v2010->accounts($this->client->getAccountSid())->fetch();
            return [
                'success' => true,
                'account' => [
                    'friendly_name' => $account->friendlyName,
                    'status' => $account->status,
                    'type' => $account->type
                ]
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'code' => $e->getCode()
            ];
        }
    }
}
