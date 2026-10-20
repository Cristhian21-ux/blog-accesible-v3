# Noticias para Todos – Blog de noticias autoadministrable (PHP + SQLite)

## Requisitos
- PHP 8.1 o superior con la extensión `pdo_sqlite`.

## Cómo ejecutarlo
```
cd blog-accesible
php -S localhost:8000 router.php
```
Con `router.php` el servidor de PHP también bloquea `data/`, `includes/`, `tests/` y los archivos de configuración
(igual que lo hace `.htaccess` en Apache/XAMPP). Abre http://localhost:8000. La base de datos se crea sola en `data/blog.sqlite` con datos de ejemplo.

## Acceso de administrador
No hay contraseñas fijas en el código. La primera vez que abras el sitio y pulses "Administrar",
se abre `setup.php`: crea ahí tu usuario y una contraseña de al menos 10 caracteres.
`setup.php` solo funciona desde el mismo equipo (localhost). En un servidor usa la terminal:
`php bin/create-admin.php TU_USUARIO` (pide la contraseña sin mostrarla).
Si tu base viene de una versión anterior y aún tiene el usuario `admin` con la contraseña publicada antes,
se elimina automáticamente una sola vez y se te pide crear un administrador nuevo.

## Contenido
- Portada con buscador en vivo y filtro por categorías.
- Guardado de noticias para leer después (se guarda en el navegador).
- Panel de administración con CRUD de noticias, categorías y etiquetas.
- Barra de accesibilidad: 7 temas (alto contraste, daltonismo), tamaño de letra, tipografía para dislexia, espaciado, lectura en voz alta, atajos de teclado.
- Tipografía OpenDyslexic: opcional. Coloca `OpenDyslexic-Regular.woff2` en `assets/fonts/` y agrega un `@font-face` en `styles.css`; si no está, usa una alternativa clara.
- Audio con transcripción: agrega archivos .mp3 en `assets/media/` y escribe la ruta `assets/media/archivo.mp3` en el panel.

- Eficiencia en celulares: sin librerías externas, lazy loading, mobile-first, `manifest.json` y `sw.js` (uso sin conexión e instalable; el service worker requiere `localhost` o HTTPS).
- Modo lectura simple (oculta imágenes y decoración) en el panel de accesibilidad.

## Seguridad
- Sin contraseñas en el código; contraseñas con `password_hash` (se re-hashean si el algoritmo mejora), mínimo 10 caracteres.
- Consultas preparadas (PDO) y `LIKE ... ESCAPE`: sin inyección SQL; salida siempre escapada: sin XSS.
- Token CSRF en todos los formularios (incluido cerrar sesión, que es POST).
- Sesión: cookie `HttpOnly`, `SameSite=Lax`, `Secure` con HTTPS, modo estricto, id nuevo al entrar, cierre a los 30 min sin actividad y a las 8 h.
- Límite de intentos de acceso: 5 fallos por usuario+IP o 20 por IP en 15 minutos (respuesta 429); respuesta igual para usuario inexistente y clave errónea.
- Cabeceras: Content-Security-Policy sin scripts en línea, `nosniff`, `X-Frame-Options: DENY`, Referrer-Policy, Permissions-Policy, COOP/CORP y HSTS en HTTPS.
- Las páginas privadas usan `Cache-Control: no-store`; el service worker nunca las guarda.
- Imágenes y audio: solo `https://` o `assets/` (se rechazan `javascript:`, `data:`, `http:`, `..`).
- La base queda con permisos 0600 y no es accesible por la web. Errores detallados solo con `BLOG_DEBUG=1`.
- Variables opcionales: `BLOG_DB_PATH`, `BLOG_FORCE_HTTPS=1` (detrás de un proxy HTTPS), `BLOG_ALLOW_REMOTE_SETUP=1`, `BLOG_DEBUG=1`.

## Pruebas automáticas
```
php tests/run.php          # 51 pruebas de PHP (sin instalar nada)
npm run test:js            # 9 pruebas de JavaScript (Node 20+)
composer init --no-interaction --name=estudiante/blog && composer require --dev phpunit/phpunit:^11.5 && vendor/bin/phpunit --coverage-clover build/logs/clover.xml --log-junit build/logs/junit.xml
npm run coverage:js        # genera build/lcov.info
```
Las pruebas de PHP recorren páginas completas (login, setup, CRUD, API, subtítulos, límite de intentos, CSRF, XSS).

## SonarQube Cloud
`sonar-project.properties` analiza todo el código y lee la cobertura (`build/logs/clover.xml`, `build/lcov.info`).
`.github/workflows/sonar.yml` (sin acciones de terceros, instala PHPUnit y pcov en cada ejecución) ejecuta las pruebas con cobertura y envía el análisis. Configuración única:
1. En SonarQube Cloud desactiva *Automatic Analysis* del proyecto (si no, la cobertura no se acepta).
2. En GitHub → Settings → Secrets and variables: secreto `SONAR_TOKEN`, variables `SONAR_PROJECT_KEY` y `SONAR_ORGANIZATION`.
3. Haz push a `main`.
Nota: `accessibility.js`, `app.js` y `sw.js` dependen del navegador y no tienen pruebas automáticas (se prueban a mano, ver el plan).
Los subtítulos (`captions.php`) son un único bloque con la transcripción, no están sincronizados con el audio.

El informe del Plan de Pruebas está en `PLAN_DE_PRUEBAS.md` (incluye la trazabilidad con la rúbrica, sección 11).
