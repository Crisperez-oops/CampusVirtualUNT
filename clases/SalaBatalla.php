<?php
/**
 * SalaBatalla.php
 * Lógica central de "Classroom Battles": salas, preguntas, respuestas,
 * cálculo de puntaje dinámico y leaderboard.
 *
 * Asume que existe Database::obtenerConexion(): PDO — igual que el resto
 * del proyecto (Usuario.php, Perfil.php). Ajusta esa llamada si tu wrapper
 * de conexión se llama distinto.
 */

class SalaBatalla
{
    /** Puntos otorgados por cada acierto consecutivo dentro de una racha. */
    private const BONUS_RACHA   = 50;
    private const BONUS_RACHA_MAX = 250;

    /** Segundos que se muestran los resultados antes de pasar solos a la siguiente pregunta. */
    private const SEGUNDOS_RESULTADOS = 5;

    // ── Creación de salas ────────────────────────────────────────────────

    public static function crear(int $hostUsuarioId, string $titulo): array
    {
        $pdo = Database::obtenerConexion();
        $codigo = self::generarCodigoUnico($pdo);

        $stmt = $pdo->prepare(
            "INSERT INTO batallas_salas (codigo, host_usuario_id, titulo, estado)
             VALUES (:codigo, :host, :titulo, 'esperando')"
        );
        $stmt->execute([
            ':codigo' => $codigo,
            ':host'   => $hostUsuarioId,
            ':titulo' => $titulo !== '' ? $titulo : 'Classroom Battle',
        ]);

        return [
            'id'     => (int) $pdo->lastInsertId(),
            'codigo' => $codigo,
        ];
    }

    private static function generarCodigoUnico(PDO $pdo): string
    {
        do {
            $codigo = (string) random_int(100000, 999999);
            $stmt = $pdo->prepare("SELECT id FROM batallas_salas WHERE codigo = :codigo AND estado != 'finalizado'");
            $stmt->execute([':codigo' => $codigo]);
        } while ($stmt->fetch());

        return $codigo;
    }

    // ── Preguntas ─────────────────────────────────────────────────────────

    /**
     * @param array $preguntas Lista de ['texto','opcion_a','opcion_b','opcion_c','opcion_d','correcta','tiempo_limite_seg','puntos_base']
     */
    public static function agregarPreguntas(int $salaId, array $preguntas): void
    {
        $pdo = Database::obtenerConexion();
        $stmt = $pdo->prepare(
            "INSERT INTO batallas_preguntas
                (sala_id, orden, texto, opcion_a, opcion_b, opcion_c, opcion_d, correcta, tiempo_limite_seg, puntos_base)
             VALUES (:sala_id, :orden, :texto, :opcion_a, :opcion_b, :opcion_c, :opcion_d, :correcta, :tiempo, :puntos)"
        );

        $orden = 1;
        foreach ($preguntas as $p) {
            $stmt->execute([
                ':sala_id'  => $salaId,
                ':orden'    => $orden++,
                ':texto'    => trim($p['texto']),
                ':opcion_a' => trim($p['opcion_a']),
                ':opcion_b' => trim($p['opcion_b']),
                ':opcion_c' => isset($p['opcion_c']) && $p['opcion_c'] !== '' ? trim($p['opcion_c']) : null,
                ':opcion_d' => isset($p['opcion_d']) && $p['opcion_d'] !== '' ? trim($p['opcion_d']) : null,
                ':correcta' => strtolower(trim($p['correcta'])),
                ':tiempo'   => (int) ($p['tiempo_limite_seg'] ?? 20),
                ':puntos'   => (int) ($p['puntos_base'] ?? 1000),
            ]);
        }
    }

    public static function contarPreguntas(int $salaId): int
    {
        $pdo = Database::obtenerConexion();
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM batallas_preguntas WHERE sala_id = :id");
        $stmt->execute([':id' => $salaId]);
        return (int) $stmt->fetchColumn();
    }

    public static function obtenerPreguntaPorOrden(int $salaId, int $orden): ?array
    {
        $pdo = Database::obtenerConexion();
        $stmt = $pdo->prepare("SELECT * FROM batallas_preguntas WHERE sala_id = :sala AND orden = :orden");
        $stmt->execute([':sala' => $salaId, ':orden' => $orden]);
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);
        return $fila ?: null;
    }

    // ── Salas ────────────────────────────────────────────────────────────

    public static function obtenerPorCodigo(string $codigo): ?array
    {
        $pdo = Database::obtenerConexion();
        $stmt = $pdo->prepare("SELECT * FROM batallas_salas WHERE codigo = :codigo");
        $stmt->execute([':codigo' => $codigo]);
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);
        return $fila ?: null;
    }

    public static function obtenerPorId(int $id): ?array
    {
        $pdo = Database::obtenerConexion();
        $stmt = $pdo->prepare("SELECT * FROM batallas_salas WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);
        return $fila ?: null;
    }

    // ── Participantes ────────────────────────────────────────────────────

    public static function unirse(int $salaId, int $usuarioId, string $apodo, string $avatarColor): array
    {
        $pdo = Database::obtenerConexion();

        // Si ya estaba en la sala (reconexión), solo lo marcamos conectado.
        $stmt = $pdo->prepare("SELECT id FROM batallas_participantes WHERE sala_id = :sala AND usuario_id = :usr");
        $stmt->execute([':sala' => $salaId, ':usr' => $usuarioId]);
        $existente = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existente) {
            $upd = $pdo->prepare("UPDATE batallas_participantes SET conectado = 1, apodo = :apodo WHERE id = :id");
            $upd->execute([':apodo' => $apodo, ':id' => $existente['id']]);
            return ['id' => (int) $existente['id'], 'reconectado' => true];
        }

        $ins = $pdo->prepare(
            "INSERT INTO batallas_participantes (sala_id, usuario_id, apodo, avatar_color)
             VALUES (:sala, :usr, :apodo, :color)"
        );
        $ins->execute([
            ':sala'  => $salaId,
            ':usr'   => $usuarioId,
            ':apodo' => $apodo,
            ':color' => $avatarColor,
        ]);

        return ['id' => (int) $pdo->lastInsertId(), 'reconectado' => false];
    }

    public static function obtenerParticipante(int $participanteId): ?array
    {
        $pdo = Database::obtenerConexion();
        $stmt = $pdo->prepare("SELECT * FROM batallas_participantes WHERE id = :id");
        $stmt->execute([':id' => $participanteId]);
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);
        return $fila ?: null;
    }

    public static function contarParticipantes(int $salaId): int
    {
        $pdo = Database::obtenerConexion();
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM batallas_participantes WHERE sala_id = :sala");
        $stmt->execute([':sala' => $salaId]);
        return (int) $stmt->fetchColumn();
    }

    public static function listarParticipantes(int $salaId): array
    {
        $pdo = Database::obtenerConexion();
        $stmt = $pdo->prepare(
            "SELECT id, apodo, avatar_color, puntaje_total, conectado
             FROM batallas_participantes WHERE sala_id = :sala ORDER BY unido_en ASC"
        );
        $stmt->execute([':sala' => $salaId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ── Control de flujo (host) ─────────────────────────────────────────

    public static function iniciarSiguientePregunta(int $salaId): bool
    {
        $pdo = Database::obtenerConexion();
        $sala = self::obtenerPorId($salaId);
        if (!$sala) return false;

        $siguienteOrden = (int) $sala['pregunta_actual_orden'] + 1;
        $pregunta = self::obtenerPreguntaPorOrden($salaId, $siguienteOrden);
        if (!$pregunta) {
            // No hay más preguntas → terminar batalla
            $stmt = $pdo->prepare("UPDATE batallas_salas SET estado = 'finalizado' WHERE id = :id");
            $stmt->execute([':id' => $salaId]);
            return false;
        }

        $stmt = $pdo->prepare(
            "UPDATE batallas_salas
             SET estado = 'pregunta', pregunta_actual_orden = :orden, pregunta_inicio_en = :inicio
             WHERE id = :id"
        );
        $stmt->execute([
            ':orden'  => $siguienteOrden,
            ':inicio' => (new DateTime())->format('Y-m-d H:i:s.v'),
            ':id'     => $salaId,
        ]);
        return true;
    }

    public static function cerrarPreguntaActual(int $salaId): void
    {
        $pdo = Database::obtenerConexion();
        try {
            $stmt = $pdo->prepare(
                "UPDATE batallas_salas SET estado = 'resultados', resultados_desde = :ts WHERE id = :id"
            );
            $stmt->execute([
                ':ts' => (new DateTime())->format('Y-m-d H:i:s.v'),
                ':id' => $salaId,
            ]);
        } catch (PDOException $e) {
            // Si la columna resultados_desde no existe todavía (falta correr
            // la migración), igual cerramos la pregunta sin el timestamp para
            // no dejar la sala trabada — pero el auto-avance por tiempo no
            // funcionará hasta que se agregue la columna.
            $stmt = $pdo->prepare("UPDATE batallas_salas SET estado = 'resultados' WHERE id = :id");
            $stmt->execute([':id' => $salaId]);
        }
    }

    public static function finalizar(int $salaId): void
    {
        $pdo = Database::obtenerConexion();
        $stmt = $pdo->prepare("UPDATE batallas_salas SET estado = 'finalizado' WHERE id = :id");
        $stmt->execute([':id' => $salaId]);
    }

    // ── Respuestas y puntaje dinámico ────────────────────────────────────

    /**
     * Calcula el puntaje de una respuesta según precisión y velocidad.
     * - Incorrecta → 0 puntos (rompe la racha).
     * - Correcta   → entre 50% y 100% de $puntosBase según qué tan rápido
     *   respondió dentro del tiempo límite, más un bono de racha.
     */
    public static function calcularPuntos(bool $esCorrecta, int $tiempoRespuestaMs, int $tiempoLimiteSeg, int $puntosBase, int $rachaPrevia): int
    {
        if (!$esCorrecta) return 0;

        $tiempoLimiteMs = max(1, $tiempoLimiteSeg * 1000);
        $tiempoMs = max(0, min($tiempoRespuestaMs, $tiempoLimiteMs));

        $fraccionRestante = 1 - ($tiempoMs / $tiempoLimiteMs); // 1 = instantáneo, 0 = justo al límite
        $factorVelocidad  = 0.5 + (0.5 * $fraccionRestante);   // entre 0.5 y 1.0

        $puntosBase = (int) round($puntosBase * $factorVelocidad);
        $bonusRacha = min(self::BONUS_RACHA_MAX, $rachaPrevia * self::BONUS_RACHA);

        return $puntosBase + $bonusRacha;
    }

    /**
     * Registra la respuesta de un participante a la pregunta activa de la sala
     * y actualiza su puntaje total + racha. Idempotente: si ya respondió esa
     * pregunta, no vuelve a puntuar (protegido por índice único en BD).
     */
    public static function registrarRespuesta(int $salaId, int $participanteId, string $opcionElegida): array
    {
        $pdo = Database::obtenerConexion();

        $sala = self::obtenerPorId($salaId);
        if (!$sala || $sala['estado'] !== 'pregunta') {
            return ['ok' => false, 'error' => 'La pregunta ya no está activa.'];
        }

        $pregunta = self::obtenerPreguntaPorOrden($salaId, (int) $sala['pregunta_actual_orden']);
        if (!$pregunta) {
            return ['ok' => false, 'error' => 'No se encontró la pregunta activa.'];
        }

        // Tiempo transcurrido desde que se mostró la pregunta (en ms).
        $inicio = new DateTime($sala['pregunta_inicio_en']);
        $ahora  = new DateTime();
        $tiempoMs = (int) round(($ahora->format('U.u') - $inicio->format('U.u')) * 1000);

        $esCorrecta = strtolower($opcionElegida) === strtolower($pregunta['correcta']);

        $participante = self::obtenerParticipante($participanteId);
        $rachaPrevia = $participante ? (int) $participante['racha_actual'] : 0;

        $puntos = self::calcularPuntos(
            $esCorrecta,
            $tiempoMs,
            (int) $pregunta['tiempo_limite_seg'],
            (int) $pregunta['puntos_base'],
            $esCorrecta ? $rachaPrevia : 0
        );

        try {
            $pdo->beginTransaction();

            $ins = $pdo->prepare(
                "INSERT INTO batallas_respuestas
                    (sala_id, pregunta_id, participante_id, opcion_elegida, es_correcta, tiempo_respuesta_ms, puntos_obtenidos)
                 VALUES (:sala, :pregunta, :participante, :opcion, :correcta, :tiempo, :puntos)"
            );
            $ins->execute([
                ':sala'        => $salaId,
                ':pregunta'    => $pregunta['id'],
                ':participante'=> $participanteId,
                ':opcion'      => strtolower($opcionElegida),
                ':correcta'    => $esCorrecta ? 1 : 0,
                ':tiempo'      => $tiempoMs,
                ':puntos'      => $puntos,
            ]);

            $nuevaRacha = $esCorrecta ? $rachaPrevia + 1 : 0;
            $upd = $pdo->prepare(
                "UPDATE batallas_participantes
                 SET puntaje_total = puntaje_total + :puntos, racha_actual = :racha
                 WHERE id = :id"
            );
            $upd->execute([':puntos' => $puntos, ':racha' => $nuevaRacha, ':id' => $participanteId]);

            $pdo->commit();
        } catch (PDOException $e) {
            $pdo->rollBack();
            // Índice único (pregunta_id, participante_id) → ya había respondido.
            return ['ok' => false, 'error' => 'Ya registraste una respuesta para esta pregunta.'];
        }

        self::cerrarPreguntaSiTodosRespondieron($salaId);

        return [
            'ok'          => true,
            'es_correcta' => $esCorrecta,
            'puntos'      => $puntos,
            'tiempo_ms'   => $tiempoMs,
        ];
    }

    /**
     * Si ya respondieron todos los participantes conectados a la pregunta
     * activa, la cerramos de inmediato en vez de esperar al timer o a que
     * el host la cierre manualmente. Se llama justo después de registrar
     * cada respuesta.
     */
    private static function cerrarPreguntaSiTodosRespondieron(int $salaId): void
    {
        $sala = self::obtenerPorId($salaId);
        if (!$sala || $sala['estado'] !== 'pregunta') return;

        $totalParticipantes = self::contarParticipantes($salaId);
        if ($totalParticipantes === 0) return;

        $totalRespuestas = self::contarRespuestasPreguntaActual($salaId);
        if ($totalRespuestas >= $totalParticipantes) {
            self::cerrarPreguntaActual($salaId);
        }
    }

    public static function contarRespuestasPreguntaActual(int $salaId): int
    {
        $pdo = Database::obtenerConexion();
        $sala = self::obtenerPorId($salaId);
        if (!$sala) return 0;

        $pregunta = self::obtenerPreguntaPorOrden($salaId, (int) $sala['pregunta_actual_orden']);
        if (!$pregunta) return 0;

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM batallas_respuestas WHERE pregunta_id = :id");
        $stmt->execute([':id' => $pregunta['id']]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Si la sala lleva SEGUNDOS_RESULTADOS mostrando resultados, avanza sola
     * a la siguiente pregunta (o finaliza si ya no hay más). Se llama en
     * cada consulta de estado, desde cualquier cliente (host o jugador).
     * El UPDATE con "WHERE estado = 'resultados'" actúa como guarda atómica:
     * si dos peticiones concurrentes intentan avanzar al mismo tiempo, solo
     * la primera tiene efecto — la segunda actualiza 0 filas y no hace nada.
     */
    private static function avanzarSiTerminoTiempoDeResultados(int $salaId): void
    {
        try {
            $sala = self::obtenerPorId($salaId);
            if (!$sala || $sala['estado'] !== 'resultados' || empty($sala['resultados_desde'])) return;

            $inicio = new DateTime($sala['resultados_desde']);
            $ahora  = new DateTime();
            $transcurrido = $ahora->format('U.u') - $inicio->format('U.u');
            if ($transcurrido < self::SEGUNDOS_RESULTADOS) return;

            $pdo = Database::obtenerConexion();
            $siguienteOrden = (int) $sala['pregunta_actual_orden'] + 1;
            $siguientePregunta = self::obtenerPreguntaPorOrden($salaId, $siguienteOrden);

            if (!$siguientePregunta) {
                $stmt = $pdo->prepare(
                    "UPDATE batallas_salas SET estado = 'finalizado' WHERE id = :id AND estado = 'resultados'"
                );
                $stmt->execute([':id' => $salaId]);
                return;
            }

            $stmt = $pdo->prepare(
                "UPDATE batallas_salas
                 SET estado = 'pregunta', pregunta_actual_orden = :orden, pregunta_inicio_en = :inicio, resultados_desde = NULL
                 WHERE id = :id AND estado = 'resultados'"
            );
            $stmt->execute([
                ':orden'  => $siguienteOrden,
                ':inicio' => (new DateTime())->format('Y-m-d H:i:s.v'),
                ':id'     => $salaId,
            ]);
        } catch (PDOException $e) {
            // No dejamos que un problema de BD (p. ej. columna resultados_desde
            // faltante) tumbe todo el endpoint de estado; simplemente no se
            // auto-avanza hasta que se corrija.
            return;
        }
    }

    // ── Leaderboard ──────────────────────────────────────────────────────

    public static function obtenerLeaderboard(int $salaId, int $limite = 10): array
    {
        $pdo = Database::obtenerConexion();
        $stmt = $pdo->prepare(
            "SELECT id, apodo, avatar_color, puntaje_total, racha_actual
             FROM batallas_participantes
             WHERE sala_id = :sala
             ORDER BY puntaje_total DESC, id ASC
             LIMIT :limite"
        );
        $stmt->bindValue(':sala', $salaId, PDO::PARAM_INT);
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Resumen de resultados de la pregunta que se acaba de cerrar:
     * cuántos eligieron cada opción y quién acertó.
     */
    public static function obtenerResultadosPreguntaActual(int $salaId): array
    {
        $pdo = Database::obtenerConexion();
        $sala = self::obtenerPorId($salaId);
        if (!$sala) return [];

        $pregunta = self::obtenerPreguntaPorOrden($salaId, (int) $sala['pregunta_actual_orden']);
        if (!$pregunta) return [];

        $stmt = $pdo->prepare(
            "SELECT opcion_elegida, COUNT(*) AS total
             FROM batallas_respuestas WHERE pregunta_id = :id
             GROUP BY opcion_elegida"
        );
        $stmt->execute([':id' => $pregunta['id']]);
        $conteo = ['a' => 0, 'b' => 0, 'c' => 0, 'd' => 0];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $fila) {
            $conteo[$fila['opcion_elegida']] = (int) $fila['total'];
        }

        return [
            'pregunta'  => $pregunta,
            'conteo'    => $conteo,
            'correcta'  => $pregunta['correcta'],
        ];
    }

    /**
     * Snapshot completo del estado de la sala, usado por el endpoint de
     * long-polling para sincronizar a host y participantes.
     */
    public static function obtenerEstado(int $salaId): array
    {
        self::avanzarSiTerminoTiempoDeResultados($salaId);

        $sala = self::obtenerPorId($salaId);
        if (!$sala) return ['existe' => false];

        $totalPreguntas = self::contarPreguntas($salaId);
        $participantes = self::listarParticipantes($salaId);
        $estado = [
            'existe'                => true,
            'estado'                => $sala['estado'],
            'titulo'                => $sala['titulo'],
            'codigo'                => $sala['codigo'],
            'pregunta_actual_orden' => (int) $sala['pregunta_actual_orden'],
            'total_preguntas'       => $totalPreguntas,
            'total_participantes'   => count($participantes),
            'participantes'         => $participantes,
            'respuestas_recibidas'  => self::contarRespuestasPreguntaActual($salaId),
            'leaderboard'           => self::obtenerLeaderboard($salaId, 8),
        ];

        if ($sala['estado'] === 'pregunta') {
            $pregunta = self::obtenerPreguntaPorOrden($salaId, (int) $sala['pregunta_actual_orden']);
            if ($pregunta) {
                $inicio = new DateTime($sala['pregunta_inicio_en']);
                $ahora  = new DateTime();
                $transcurridoSeg = ($ahora->format('U.u') - $inicio->format('U.u'));
                $restante = max(0, (int) $pregunta['tiempo_limite_seg'] - floor($transcurridoSeg));

                $estado['pregunta'] = [
                    'id'                => (int) $pregunta['id'],
                    'orden'             => (int) $pregunta['orden'],
                    'texto'             => $pregunta['texto'],
                    'opcion_a'          => $pregunta['opcion_a'],
                    'opcion_b'          => $pregunta['opcion_b'],
                    'opcion_c'          => $pregunta['opcion_c'],
                    'opcion_d'          => $pregunta['opcion_d'],
                    'tiempo_limite_seg' => (int) $pregunta['tiempo_limite_seg'],
                    'segundos_restantes'=> $restante,
                ];
            }
        }

        if ($sala['estado'] === 'resultados') {
            $estado['resultados'] = self::obtenerResultadosPreguntaActual($salaId);

            $segundosParaSiguiente = self::SEGUNDOS_RESULTADOS;
            if ($sala['resultados_desde']) {
                $inicio = new DateTime($sala['resultados_desde']);
                $ahora  = new DateTime();
                $transcurrido = $ahora->format('U.u') - $inicio->format('U.u');
                $segundosParaSiguiente = max(0, (int) ceil(self::SEGUNDOS_RESULTADOS - $transcurrido));
            }
            $estado['segundos_para_siguiente'] = $segundosParaSiguiente;
        }

        return $estado;
    }
}