# CampusVirtual UNITRU

Plataforma SaaS multi-tenant con Hub Social para la Universidad Nacional de Trujillo.
Cada facultad aísla sus datos lógicamente (columna `facultad_id`), pero todos los
estudiantes pueden encontrarse y chatear en un Hub 2D compartido.

Construido 100% en PHP puro orientado a objetos + PDO, HTML/CSS/JS vanilla y MySQL.
Sin Docker, sin Node.js, sin WebSockets — listo para hosting compartido tipo InfinityFree.

---

## 1. Estructura del proyecto

```
htdocs/
├── index.php              Hub Virtual (mapa 2D de facultades)
├── login.php               Inicio de sesión
├── registro.php             Registro (solo @unitru.edu.pe)
├── logout.php                Cierre de sesión
├── networking.php             Buscador de Talentos
├── chat.php                    Ventana de chat
│
├── config/
│   ├── database.php          <- AQUÍ van tus credenciales de phpMyAdmin
│   └── constantes.php
│
├── clases/                     Backend POO
│   ├── Database.php            Conexión PDO (Singleton)
│   ├── Usuario.php              Registro, login, validaciones, búsqueda
│   ├── Facultad.php
│   ├── Perfil.php
│   └── Chat.php
│
├── api/                          Endpoints consumidos por fetch()
│   ├── networking_api.php
│   ├── chat_api.php
│   └── perfil_api.php
│
├── assets/
│   ├── css/estilo.css
│   └── js/{hub.js, networking.js, chat.js}
│
├── vistas/topbar.php
└── sql/schema.sql                <- Importar en phpMyAdmin
```

---

## 2. Despliegue paso a paso en InfinityFree

### Paso A — Crear la base de datos

1. Entra a tu panel de control de InfinityFree (client area).
2. Ve a tu cuenta de hosting → **"MySQL Databases"**.
3. Crea una base de datos. InfinityFree te asignará automáticamente:
   - Un nombre como `if0_XXXXXXXX_campusvirtual`
   - Un usuario (normalmente el mismo: `if0_XXXXXXXX`)
   - Te pedirá que pongas una contraseña
4. **Anota el host de MySQL** que te muestra el panel (ej. `sql200.infinityfree.com`).
   ⚠️ InfinityFree casi nunca usa `localhost`, así que no asumas ese valor.

### Paso B — Importar el schema

1. Desde el mismo panel, abre **phpMyAdmin** (botón junto a tu base de datos).
2. Selecciona tu base de datos en el panel izquierdo.
3. Ve a la pestaña **"SQL"**.
4. Abre el archivo `sql/schema.sql` de este proyecto, copia todo su contenido,
   pégalo en el cuadro de texto y pulsa **"Continuar"**.
5. Verifica que se crearon 4 tablas: `facultades`, `usuarios`, `perfiles_habilidades`,
   `mensajes_chat`, y que `facultades` ya tiene 12 filas (las facultades de la UNITRU).

### Paso C — Configurar las credenciales en el código

Abre `config/database.php` y edita estas 4 líneas con tus datos reales:

```php
define('DB_HOST', 'sql200.infinityfree.com');     // tu host real
define('DB_NAME', 'if0_XXXXXXXX_campusvirtual');  // tu nombre de BD real
define('DB_USER', 'if0_XXXXXXXX');                // tu usuario real
define('DB_PASS', 'tu_password_real');
```

Estos 4 valores los encuentras exactamente en la misma pantalla de "MySQL Databases"
donde creaste la base de datos.

### Paso D — Subir los archivos por FTP

1. En tu panel de InfinityFree, ve a **"Cuentas FTP"** y consigue tus credenciales
   (host FTP, usuario, contraseña), o usa el **"Administrador de archivos web"**.
2. Conéctate con un cliente FTP (FileZilla, WinSCP, etc.) a tu host FTP.
3. Entra a la carpeta `htdocs/` de tu cuenta.
4. Sube **todo el contenido** de este proyecto (todas las carpetas y archivos)
   directamente dentro de `htdocs/` — no dentro de una subcarpeta, a menos que tu
   dominio esté configurado para apuntar a una subcarpeta específica.
5. Espera a que termine la subida de todos los archivos (puede tardar unos minutos
   por la cantidad de archivos pequeños).

### Paso E — Probar

1. Visita `https://tudominio.com/registro.php`.
2. Regístrate con un correo `@unitru.edu.pe` de prueba.
3. Inicia sesión y verifica que el Hub cargue con el mapa de facultades.
4. Prueba el buscador de talentos y el chat con una segunda cuenta de prueba.

---

## 3. Notas técnicas importantes

- **Extensiones PHP requeridas**: `pdo_mysql` y `mbstring`. Ambas vienen habilitadas
  por defecto en InfinityFree y en la gran mayoría de hostings PHP compartidos, así
  que normalmente no necesitas hacer nada extra.
- **Modo debug**: en `config/database.php`, la constante `MODO_DEBUG` está en `false`
  por defecto (recomendado en producción). Si necesitas depurar un error mientras
  configuras el hosting, cámbiala temporalmente a `true` para ver mensajes de error
  detallados — pero vuelve a ponerla en `false` antes de dejarlo en producción final,
  para no exponer detalles internos a los visitantes.
- **Sesiones**: se usan sesiones nativas de PHP (`session_start()`), sin librerías
  externas. InfinityFree las soporta sin configuración adicional.
- **Chat sin WebSockets**: el frontend (`assets/js/chat.js`) consulta
  `api/chat_api.php` cada 4.5 segundos mientras la pestaña está visible, y se
  pausa automáticamente si el usuario cambia de pestaña, para minimizar el
  consumo de CPU en el plan gratuito.
- **Aislamiento multi-tenant**: los datos académicos de cada facultad se separan
  lógicamente por la columna `facultad_id` en la misma base de datos compartida.
  El Hub Social y el Chat son las únicas piezas que cruzan ese límite a propósito,
  ya que su objetivo es interconectar estudiantes de toda la universidad.
- **Seguridad de contraseñas**: se usa `password_hash()` / `password_verify()`
  (algoritmo bcrypt por defecto). Nunca se guarda la contraseña en texto plano.
- **Prevención de inyección SQL**: todas las consultas usan PDO con prepared
  statements (parámetros nombrados), nunca concatenación directa de strings.

---

## 4. Cómo extender el proyecto

- **Más facultades o cambiar posiciones del mapa**: edita la tabla `facultades`
  desde phpMyAdmin (columnas `pos_x`, `pos_y` son porcentajes 0-100 dentro del
  mapa 2D, `color_tema` es el color hexadecimal del nodo).
- **Perfil de habilidades**: ya existe `api/perfil_api.php` y la clase `Perfil.php`
  listos para conectar a un formulario de edición de perfil (no incluido en el
  HTML aún, pero la API funciona — puedes agregar un modal en `index.php` que
  llame a `POST api/perfil_api.php` con `{descripcion, habilidades_tags}`).
- **Notificaciones de mensajes nuevos**: la clase `Chat.php` ya tiene
  `obtenerConversacionesRecientes()`, que puedes usar para mostrar un contador
  de mensajes no leídos en el topbar.
