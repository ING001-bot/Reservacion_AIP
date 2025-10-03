<?php
namespace App\Lib;

class MailboxChecker {
    /**
     * Intenta validar la existencia del buzón con un RCPT TO contra el servidor MX.
     * Retorna:
     *  - true: el servidor indica que el buzón existe (250 OK)
     *  - false: el servidor indica que NO existe (550/551)
     *  - null: indeterminado (timeout, greylisting, política que siempre responde 250, etc.)
     */
    public function check(string $email, int $timeoutSec = 5): ?bool {
        $email = trim($email);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }
        $domain = substr(strrchr($email, '@'), 1);
        if (!$domain) return false;

        // Obtener MX records
        $mxHosts = [];
        if (function_exists('dns_get_record')) {
            $mxRecords = @dns_get_record($domain, DNS_MX);
            if (is_array($mxRecords)) {
                // ordenar por prioridad
                usort($mxRecords, function($a,$b){ return ($a['pri'] ?? 0) <=> ($b['pri'] ?? 0); });
                foreach ($mxRecords as $rec) {
                    if (!empty($rec['target'])) $mxHosts[] = $rec['target'];
                }
            }
        }
        if (empty($mxHosts)) {
            // Sin MX: indeterminado, algunos dominios usan A
            $mxHosts[] = $domain;
        }

        $from = 'verifier@' . ($this->getLocalDomain() ?: 'localhost');
        foreach ($mxHosts as $host) {
            $fp = @fsockopen($host, 25, $errno, $errstr, $timeoutSec);
            if (!$fp) continue; // intentar siguiente MX
            stream_set_timeout($fp, $timeoutSec);
            $read = function() use ($fp) { return fgets($fp, 515) ?: ''; };
            $write = function($cmd) use ($fp) { fwrite($fp, $cmd."\r\n"); };

            $greet = $read();
            if (!$this->isPositive($greet)) { fclose($fp); continue; }
            $write('HELO ' . ($this->getLocalDomain() ?: 'localhost'));
            $helo = $read();
            if (!$this->isPositive($helo)) { fclose($fp); continue; }
            $write('MAIL FROM:<' . $from . '>');
            $mf = $read();
            if (!$this->isPositive($mf)) { fclose($fp); continue; }
            $write('RCPT TO:<' . $email . '>');
            $rcpt = $read();
            $write('QUIT');
            fclose($fp);

            if (preg_match('/^25\d/', $rcpt)) {
                return true; // aceptado
            }
            if (preg_match('/^(550|551)/', $rcpt)) {
                return false; // inexistente
            }
            // codigos 450/451/452 u otros -> indeterminado
            return null;
        }
        return null; // no se pudo conectar a ningún MX
    }

    private function isPositive(string $line): bool {
        return (bool)preg_match('/^2\d\d/', $line);
    }

    private function getLocalDomain(): string {
        $host = gethostname();
        if ($host) return $host;
        return 'localhost';
    }
}
