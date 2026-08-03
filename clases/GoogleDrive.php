<?php
/**
 * clases/GoogleDrive.php
 * Envoltorio simple para la API de Google Drive, usando el access_token /
 * refresh_token guardados en la tabla `usuarios`. Sin librería google/apiclient,
 * solo cURL (igual que el resto del proyecto).
 */
class GoogleDrive
{
    private static function credenciales(): array
    {
        return [
            'client_id'     => env('GOOGLE_CLIENT_ID'),
            'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        ];
    }

    /**
     * Devuelve un access_token válido para el usuario, refrescándolo si expiró.
     * Devuelve null si el usuario nunca autorizó el scope de Drive
     * (tendrá que volver a loguearse con Google para otorgarlo).
     */
    public static function obtenerAccessToken(int $usuarioId): ?string
    {
        $db = Database::obtenerConexion();
        $stmt = $db->prepare("SELECT google_access_token, google_refresh_token, google_token_expira FROM usuarios WHERE id = ?");
        $stmt->execute([$usuarioId]);
        $row = $stmt->fetch();

        if (!$row || empty($row['google_refresh_token'])) {
            return null;
        }

        $expira = $row['google_token_expira'] ? strtotime($row['google_token_expira']) : 0;
        if (!empty($row['google_access_token']) && $expira > time() + 60) {
            return $row['google_access_token']; // sigue vigente
        }

        // Refrescar usando el refresh_token
        $cred = self::credenciales();
        $ch = curl_init('https://oauth2.googleapis.com/token');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
            CURLOPT_POSTFIELDS => http_build_query([
                'client_id'     => $cred['client_id'],
                'client_secret' => $cred['client_secret'],
                'refresh_token' => $row['google_refresh_token'],
                'grant_type'    => 'refresh_token',
            ]),
        ]);
        $resp = json_decode(curl_exec($ch), true);
        curl_close($ch);

        if (empty($resp['access_token'])) {
            return null;
        }

        $nuevoExpira = date('Y-m-d H:i:s', time() + (int)($resp['expires_in'] ?? 3600));
        $upd = $db->prepare("UPDATE usuarios SET google_access_token = ?, google_token_expira = ? WHERE id = ?");
        $upd->execute([$resp['access_token'], $nuevoExpira, $usuarioId]);

        return $resp['access_token'];
    }

    /**
     * Guarda los tokens obtenidos en el callback de login (access_token,
     * refresh_token, expires_in) para un usuario ya existente en BD.
     */
    public static function guardarTokens(int $usuarioId, string $accessToken, ?string $refreshToken, int $expiresIn): void
    {
        $db = Database::obtenerConexion();
        $expira = date('Y-m-d H:i:s', time() + $expiresIn);

        if ($refreshToken) {
            $stmt = $db->prepare("UPDATE usuarios SET google_access_token = ?, google_refresh_token = ?, google_token_expira = ? WHERE id = ?");
            $stmt->execute([$accessToken, $refreshToken, $expira, $usuarioId]);
        } else {
            // Google no siempre reenvía refresh_token; si ya teníamos uno guardado, no lo pisamos.
            $stmt = $db->prepare("UPDATE usuarios SET google_access_token = ?, google_token_expira = ? WHERE id = ?");
            $stmt->execute([$accessToken, $expira, $usuarioId]);
        }
    }

    /** Lista archivos del Drive del usuario (raíz o carpeta indicada). */
    public static function listarArchivos(string $accessToken, ?string $carpetaId = null, string $busqueda = ''): array
    {
        $q = $carpetaId
            ? "'" . addslashes($carpetaId) . "' in parents and trashed = false"
            : "'root' in parents and trashed = false";
        if ($busqueda !== '') {
            $q .= " and name contains '" . addslashes($busqueda) . "'";
        }

        $params = http_build_query([
            'q'        => $q,
            'fields'   => 'files(id,name,mimeType,iconLink,webViewLink,size,modifiedTime)',
            'orderBy'  => 'folder,modifiedTime desc',
            'pageSize' => 100,
        ]);

        $ch = curl_init('https://www.googleapis.com/drive/v3/files?' . $params);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $accessToken],
        ]);
        $resp = json_decode(curl_exec($ch), true);
        curl_close($ch);

        return $resp['files'] ?? [];
    }

    /** Sube un archivo (proveniente de $_FILES['campo']) al Drive del usuario. */
    public static function subirArchivo(string $accessToken, array $archivo, ?string $carpetaId = null): array
    {
        $metadata = ['name' => $archivo['name']];
        if ($carpetaId) $metadata['parents'] = [$carpetaId];

        $boundary   = '-------314159265358979323846';
        $delimiter  = "\r\n--" . $boundary . "\r\n";
        $closeDelim = "\r\n--" . $boundary . "--";

        $contenido = file_get_contents($archivo['tmp_name']);
        $mimeType  = $archivo['type'] ?: 'application/octet-stream';

        $body = $delimiter
            . "Content-Type: application/json; charset=UTF-8\r\n\r\n"
            . json_encode($metadata)
            . $delimiter
            . "Content-Type: {$mimeType}\r\n\r\n"
            . $contenido
            . $closeDelim;

        $ch = curl_init('https://www.googleapis.com/upload/drive/v3/files?uploadType=multipart&fields=id,name,webViewLink');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $accessToken,
                'Content-Type: multipart/related; boundary=' . $boundary,
            ],
            CURLOPT_POSTFIELDS => $body,
        ]);
        $resp = json_decode(curl_exec($ch), true);
        curl_close($ch);

        return $resp ?? [];
    }
}