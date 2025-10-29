<?php
namespace App\Lib;

require_once __DIR__ . '/../../vendor/autoload.php';

use Twilio\Rest\Client;
use Exception;

class SmsService {
    private $client;
    private $fromNumber;
    private $whatsappFrom;
    private $messagingServiceSid;

    public function __construct() {
        $config = require __DIR__ . '/../config/twilio.php';
        $sid = trim((string)(getenv('TWILIO_ACCOUNT_SID') ?: ($config['account_sid'] ?? '')));
        $token = trim((string)(getenv('TWILIO_AUTH_TOKEN') ?: ($config['auth_token'] ?? '')));
        $this->fromNumber = trim((string)(getenv('TWILIO_FROM') ?: ($config['from_number'] ?? '')));
        $this->whatsappFrom = trim((string)(getenv('TWILIO_WHATSAPP_FROM') ?: ($config['whatsapp_from'] ?? '')));
        $this->messagingServiceSid = trim((string)(getenv('TWILIO_MESSAGING_SERVICE_SID') ?: ($config['messaging_service_sid'] ?? '')));

        if ($sid === '' || $token === '' || ($this->fromNumber === '' && $this->messagingServiceSid === '')) {
            throw new Exception('Twilio no configurado. Completa account_sid, auth_token y from_number o messaging_service_sid en app/config/twilio.php');
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
            $params = [ 'body' => $message ];
            if ($this->messagingServiceSid !== '') { $params['messagingServiceSid'] = $this->messagingServiceSid; }
            else { $params['from'] = $this->fromNumber; }
            $twMsg = $this->client->messages->create($to, $params);
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

    /** Indica si hay remitente de WhatsApp configurado */
    public function hasWhatsApp(): bool {
        return $this->whatsappFrom !== '';
    }

    /**
     * Envía un mensaje por WhatsApp usando Twilio
     * Requiere tener configurado 'whatsapp_from' en app/config/twilio.php
     */
    public function sendWhatsApp(string $to, string $message): array {
        try {
            if ($this->whatsappFrom === '') {
                throw new Exception('WhatsApp no está configurado. Define whatsapp_from en app/config/twilio.php');
            }
            $to = $this->normalizeNumber($to);
            $twMsg = $this->client->messages->create('whatsapp:' . $to, [
                'from' => 'whatsapp:' . $this->whatsappFrom,
                'body' => $message
            ]);
            return [
                'success' => true,
                'message' => 'Mensaje de WhatsApp enviado correctamente',
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
}
