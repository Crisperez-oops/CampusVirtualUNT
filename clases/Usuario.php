<?php
/**
 * clases/Usuario.php
 * Maneja registro, autenticación y datos de los estudiantes.
 */

require_once __DIR__ . '/Database.php';

class Usuario
{
    public int $id;
    public string $nombre;
    public string $email;
    public int $facultad_id;

    /**
     * Valida que el correo sea estrictamente del dominio institucional.
     * Regex estricto: exige formato local-part válido + dominio exacto
     * "@unitru.edu.pe" (no subdominios falsos como unitru.edu.pe.fake.com).
     */
    public static function esCorreoInstitucionalValido(string $email): bool
    {
        $patron = '/^[a-zA-Z0-9._%+-]+@unitru\.edu\.pe$/';
        return (bool) preg_match($patron, trim($email));
    }

    /**
     * Valida formato de nombre (solo letras, espacios y tildes, 3-150 chars).
     */
    public static function esNombreValido(string $nombre): bool
    {
        $patron = '/^[A-Za-zÁÉÍÓÚáéíóúÑñ\s]{3,150}$/u';
        return (bool) preg_match($patron, trim($nombre));
    }

    /**
     * Verifica si ya existe una cuenta con ese email (regla de 1 cuenta x alumno).
     */
    public static function existeEmail(string $email): bool
    {
        $pdo = Database::obtenerConexion();
        $stmt = $pdo->prepare('SELECT id FROM usuarios WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => strtolower(trim($email))]);
        return (bool) $stmt->fetch();
    }

    /**
     * Registra un nuevo estudiante. Devuelve ['ok' => bool, 'error' => string|null, 'id' => int|null]
     */
    public static function registrar(string $nombre, string $email, string $password, int $facultadId): array
    {
        $nombre = trim($nombre);
        $email  = strtolower(trim($email));

        if (!self::esNombreValido($nombre)) {
            return ['ok' => false, 'error' => 'El nombre solo debe contener letras y espacios (3 a 150 caracteres).'];
        }

        if (!self::esCorreoInstitucionalValido($email)) {
            return ['ok' => false, 'error' => 'Debes registrarte con tu correo institucional @unitru.edu.pe'];
        }

        if (strlen($password) < 8) {
            return ['ok' => false, 'error' => 'La contraseña debe tener al menos 8 caracteres.'];
        }

        if (self::existeEmail($email)) {
            return ['ok' => false, 'error' => 'Ya existe una cuenta registrada con ese correo institucional.'];
        }

        $facultad = Facultad::obtenerPorId($facultadId);
        if (!$facultad) {
            return ['ok' => false, 'error' => 'Selecciona una facultad válida.'];
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);

        // Asignamos un color de avatar aleatorio agradable para el Hub
        $paletaAvatares = ['#3B82F6', '#EF4444', '#10B981', '#F59E0B', '#8B5CF6', '#EC4899', '#14B8A6', '#FB923C'];
        $colorAvatar = $paletaAvatares[array_rand($paletaAvatares)];

        $pdo = Database::obtenerConexion();
        $stmt = $pdo->prepare(
            'INSERT INTO usuarios (nombre, email, password, facultad_id, avatar_color, fecha_registro)
             VALUES (:nombre, :email, :password, :facultad_id, :avatar_color, NOW())'
        );

        try {
            $stmt->execute([
                'nombre'       => $nombre,
                'email'        => $email,
                'password'     => $hash,
                'facultad_id'  => $facultadId,
                'avatar_color' => $colorAvatar,
            ]);
        } catch (PDOException $e) {
            // Captura condición de carrera: dos registros simultáneos con mismo email
            if ($e->getCode() === '23000') {
                return ['ok' => false, 'error' => 'Ya existe una cuenta registrada con ese correo institucional.'];
            }
            return ['ok' => false, 'error' => 'No se pudo completar el registro. Intenta nuevamente.'];
        }

        $nuevoId = (int) $pdo->lastInsertId();

        // Crea fila vacía de perfil para que el alumno la complete después
        $stmtPerfil = $pdo->prepare(
            'INSERT INTO perfiles_habilidades (usuario_id, descripcion, habilidades_tags) VALUES (:uid, :desc, :tags)'
        );
        $stmtPerfil->execute(['uid' => $nuevoId, 'desc' => '', 'tags' => '']);

        return ['ok' => true, 'error' => null, 'id' => $nuevoId];
    }

    /**
     * Intenta iniciar sesión. Devuelve datos del usuario si las credenciales
     * son correctas, o null si fallan.
     */
    public static function autenticar(string $email, string $password): ?array
    {
        $email = strtolower(trim($email));

        $pdo = Database::obtenerConexion();
        $stmt = $pdo->prepare(
            'SELECT u.id, u.nombre, u.email, u.password, u.facultad_id, u.avatar_color, u.rol,
                    f.nombre AS facultad_nombre, f.codigo AS facultad_codigo, f.color_tema AS facultad_color
             FROM usuarios u
             INNER JOIN facultades f ON f.id = u.facultad_id
             WHERE u.email = :email AND u.activo = 1
             LIMIT 1'
        );
        $stmt->execute(['email' => $email]);
        $usuario = $stmt->fetch();

        if (!$usuario || !password_verify($password, $usuario['password'])) {
            return null;
        }

        // Actualiza última conexión (útil para mostrar "en línea" en el hub)
        $update = $pdo->prepare('UPDATE usuarios SET ultima_conexion = NOW() WHERE id = :id');
        $update->execute(['id' => $usuario['id']]);

        unset($usuario['password']); // nunca devolver el hash hacia afuera
        return $usuario;
    }

    /**
     * Obtiene usuarios de OTRAS facultades (o todas) filtrando por
     * habilidades, para el Buscador de Talentos.
     *
     * @param int|null $facultadId Filtra por una facultad específica (null = todas)
     * @param string $habilidad Texto de búsqueda dentro de habilidades_tags
     * @param int $excluirUsuarioId El propio usuario nunca debe salir en su búsqueda
     */
    public static function buscarTalentos(?int $facultadId, string $habilidad, int $excluirUsuarioId): array
    {
        $pdo = Database::obtenerConexion();

        $sql = 'SELECT u.id, u.nombre, u.avatar_color, f.nombre AS facultad_nombre,
                       f.codigo AS facultad_codigo, f.color_tema AS facultad_color,
p.descripcion, p.habilidades_tags, p.foto
                FROM usuarios u
                INNER JOIN facultades f ON f.id = u.facultad_id
                LEFT JOIN perfiles_habilidades p ON p.usuario_id = u.id
                WHERE u.activo = 1 AND u.id != :excluir_id';

        $parametros = ['excluir_id' => $excluirUsuarioId];

        if ($facultadId !== null && $facultadId > 0) {
            $sql .= ' AND u.facultad_id = :facultad_id';
            $parametros['facultad_id'] = $facultadId;
        }

        if ($habilidad !== '') {
            $sql .= ' AND p.habilidades_tags LIKE :habilidad';
            $parametros['habilidad'] = '%' . $habilidad . '%';
        }

        $sql .= ' ORDER BY u.nombre ASC LIMIT 50';

        $stmt = $pdo->prepare($sql);
        $stmt->execute($parametros);
        return $stmt->fetchAll();
    }

    public static function obtenerPorId(int $id): ?array
    {
        $pdo = Database::obtenerConexion();
        $stmt = $pdo->prepare(
            'SELECT id, nombre, email, facultad_id, avatar_color FROM usuarios WHERE id = :id'
        );
        $stmt->execute(['id' => $id]);
        $resultado = $stmt->fetch();
        return $resultado ?: null;
    }

    public static function listarConectadosAhora(int $usuarioActualId, int $limite = 6): array
    {
        $pdo = Database::obtenerConexion();
        $stmt = $pdo->prepare(
            'SELECT u.id, u.nombre, u.avatar_color
             FROM usuarios u
             WHERE u.activo = 1 AND u.id != :uid
               AND u.ultima_conexion >= DATE_SUB(NOW(), INTERVAL 15 MINUTE)
             ORDER BY u.ultima_conexion DESC
             LIMIT :limite'
        );
        $stmt->bindValue('uid', $usuarioActualId, PDO::PARAM_INT);
        $stmt->bindValue('limite', $limite, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function registrarVisitaYObtenerRacha(int $usuarioId): int
    {
        $pdo = Database::obtenerConexion();
        
        // Crear tabla de racha si no existe
        $pdo->exec("CREATE TABLE IF NOT EXISTS racha_visitas (
            usuario_id INT UNSIGNED PRIMARY KEY,
            ultima_fecha DATE NOT NULL,
            racha INT NOT NULL DEFAULT 1,
            FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $hoy = date('Y-m-d');
        $stmt = $pdo->prepare("SELECT ultima_fecha, racha FROM racha_visitas WHERE usuario_id = ?");
        $stmt->execute([$usuarioId]);
        $row = $stmt->fetch();

        if ($row) {
            if ($row['ultima_fecha'] === $hoy) {
                return (int)$row['racha'];
            } elseif ($row['ultima_fecha'] === date('Y-m-d', strtotime('-1 day'))) {
                $racha = (int)$row['racha'] + 1;
                $pdo->prepare("UPDATE racha_visitas SET ultima_fecha = ?, racha = ? WHERE usuario_id = ?")
                    ->execute([$hoy, $racha, $usuarioId]);
                $pdo->prepare("UPDATE usuarios SET ultima_conexion = NOW() WHERE id = ?")->execute([$usuarioId]);
                return $racha;
            } else {
                $pdo->prepare("UPDATE racha_visitas SET ultima_fecha = ?, racha = 1 WHERE usuario_id = ?")
                    ->execute([$hoy, $usuarioId]);
                $pdo->prepare("UPDATE usuarios SET ultima_conexion = NOW() WHERE id = ?")->execute([$usuarioId]);
                return 1;
            }
        } else {
            $pdo->prepare("INSERT INTO racha_visitas (usuario_id, ultima_fecha, racha) VALUES (?,?,1)")
                ->execute([$usuarioId, $hoy]);
            $pdo->prepare("UPDATE usuarios SET ultima_conexion = NOW() WHERE id = ?")->execute([$usuarioId]);
            return 1;
        }
    }
}
