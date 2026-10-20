# INFORME DEL PLAN DE PRUEBAS DE SOFTWARE
## Blog de Noticias Autoadministrable "Noticias para Todos"
**Curso:** Prueba y Calidad de Software
**Responsable (QA Lead / Desarrollador):** Cristhian Amao
**Versión del documento:** 1.0   **Fecha:** 13/10/2026
**Estándares:** ISTQB Foundation Level · IEEE 829 · WCAG 2.1 (AAA) · ISO/IEC 25010

---

## 1. Factores a considerar al diseñar un plan de pruebas para una aplicación web

1. **Objetivos y alcance:** qué se prueba y qué queda fuera (funcionalidad, usabilidad, accesibilidad, rendimiento, seguridad básica).
2. **Análisis de riesgos:** priorizar por probabilidad e impacto.
3. **Estrategia:** niveles (unitaria, integración, sistema, aceptación), tipos (funcional, no funcional, regresión, confirmación), técnicas (caja negra, caja blanca, exploratoria) y enfoque manual/automatizado.
4. **Estimación:** WBS, estimación de tres puntos (PERT) y recursos disponibles.
5. **Entornos:** navegadores, dispositivos, redes lentas, tecnologías asistivas (NVDA, JAWS, VoiceOver, TalkBack) y datos de prueba controlados.
6. **Cobertura:** de requisitos (trazabilidad), de código y de criterios WCAG.
7. **Métricas:** tasa de éxito, densidad de defectos, DDE, cobertura, defectos abiertos por severidad.
8. **Criterios de entrada, salida, suspensión y reanudación.**
9. **Roles y responsabilidades.**
10. **Gestión de defectos y de la configuración.**
11. **Requisitos no funcionales y normativos:** WCAG 2.1, eficiencia en equipos limitados, protección de datos.
12. **Calendario:** ejecución, regresión y reprueba con holgura.

---

## 2. Introducción y objetivo
Verificar que el blog de noticias cumple sus requisitos funcionales y no funcionales (usabilidad, accesibilidad universal, eficiencia) antes de su entrega.

## 3. Elementos de prueba
Aplicación PHP 8.x + SQLite. Módulos: autenticación, CRUD de noticias, categorías/etiquetas, buscador en vivo, guardado para leer después, barra de accesibilidad, contenido multimedia con transcripción.

## 4. Alcance
| Tipo de prueba | Dentro del alcance | Técnica |
|---|---|---|
| Funcionales | Login, CRUD, filtros, buscador, guardados | Caja negra |
| Usabilidad | Mensajes de feedback, navegación, consistencia | Heurísticas de Nielsen |
| Accesibilidad | WCAG 2.1 A/AA/AAA, lector de pantalla, teclado, contraste | Manual + herramientas |
| Rendimiento / Eficiencia | Peso de página, tiempo de carga en 3G, lazy loading | Lighthouse, DevTools |
| Seguridad básica | SQL injection, XSS, CSRF, sesión | Exploratoria |
| Fuera de alcance | Pruebas de carga masiva, hosting, SEO avanzado | |

## 5. Entornos de prueba
| Entorno | Detalle |
|---|---|
| Servidor | PHP 8.2 (php -S localhost:8000), SQLite 3 |
| Navegadores | Chrome, Firefox, Edge, Safari (últimas 2 versiones) |
| Dispositivos | PC 1366x768, Android gama baja 360x640, tableta |
| Tecnologías asistivas | NVDA + Firefox, VoiceOver iOS, TalkBack Android |
| Herramientas | axe DevTools, WAVE, Lighthouse, Colour Contrast Analyser, Color Oracle |

## 6. Criterios
- **Entrada:** código desplegado, datos semilla cargados, casos aprobados.
- **Salida:** 100 % de casos críticos ejecutados, ≥ 95 % de éxito, 0 defectos críticos o altos abiertos, 0 errores en axe.
- **Suspensión:** falla de login o de base de datos que impida ejecutar el resto.
- **Reanudación:** corrección verificada y build nuevo.

## 7. Casos de prueba
| ID | Descripción | Pasos | Resultado esperado | Prioridad |
|---|---|---|---|---|
| CP-01 | Login válido | 1. Abrir login.php 2. Ingresar el usuario y la clave creados en setup.php 3. Enviar | Redirige a admin.php y muestra mensaje de éxito | Alta |
| CP-02 | Login inválido | Ingresar clave errónea | Mensaje de error (role="alert"), no inicia sesión | Alta |
| CP-03 | Acceso sin sesión a admin.php | Abrir admin.php sin login | Redirige a login.php | Alta |
| CP-04 | Crear noticia | Panel → Nueva → llenar campos → Guardar | Noticia visible en index.php, mensaje de éxito | Alta |
| CP-05 | Validación de campos | Guardar con título vacío | Mensaje de error asociado al campo, no guarda | Alta |
| CP-06 | Editar noticia | Editar título → Guardar | Cambio reflejado en portada | Alta |
| CP-07 | Eliminar noticia | Eliminar → Confirmar | Noticia desaparece, mensaje de éxito | Alta |
| CP-08 | CSRF | Enviar formulario con token alterado | Rechazo con error 400 | Alta |
| CP-09 | Inyección SQL en buscador | Buscar `' OR 1=1 --` | Sin error ni fuga, resultados vacíos | Alta |
| CP-10 | XSS en título | Crear noticia con `<script>alert(1)</script>` | Se muestra como texto, no se ejecuta | Alta |
| CP-11 | Buscador en tiempo real | Escribir "salud" en el buscador | La lista se filtra sin recargar y se anuncia el conteo por aria-live | Alta |
| CP-12 | Filtro por categoría | Elegir categoría "Ciencia" | Solo noticias de esa categoría | Media |
| CP-13 | Guardar para leer después | Pulsar "Guardar" en una noticia → saved.php | La noticia aparece en Guardados y persiste al recargar | Media |
| CP-14 | Lector de pantalla: estructura | NVDA: navegar por encabezados (H) y regiones (D) | Anuncia main, nav, search, h1 único y jerarquía h2/h3 sin saltos | Alta |
| CP-15 | Lector de pantalla: imágenes | Recorrer imágenes con NVDA | Cada imagen lee su texto alternativo descriptivo | Alta |
| CP-16 | Lector de pantalla: avisos dinámicos | Cambiar tema, tamaño de letra y buscar | Se anuncia el cambio mediante aria-live | Alta |
| CP-17 | Navegación solo por teclado | Recorrer toda la página con Tab / Shift+Tab / Enter / Esc | Orden lógico, sin trampas de foco, todo operable | Alta |
| CP-18 | Enlace "Saltar al contenido" | Primer Tab en la página | Aparece visible y lleva al main | Alta |
| CP-19 | Atajos de teclado | Alt+A, Alt+B, Alt+C, Alt+Plus, Alt+Minus | Ejecutan abrir panel, buscar, contraste, aumentar y reducir fuente | Media |
| CP-20 | Foco visible | Tab sobre enlaces, botones y campos | Contorno ≥ 3 px con contraste ≥ 3:1 | Alta |
| CP-21 | Contraste AAA | Medir texto normal en cada tema | Relación ≥ 7:1 (≥ 4.5:1 en texto grande) | Alta |
| CP-22 | Modo alto contraste | Activar tema "Alto contraste" | Todo el contenido legible y bordes visibles | Alta |
| CP-23 | Modos daltónicos | Activar protanopia, deuteranopia y tritanopia | Información no depende solo del color; mensajes con texto e ícono | Alta |
| CP-24 | Tamaño de fuente | Pulsar A+ hasta el máximo (200 %) | Sin pérdida de contenido ni scroll horizontal | Alta |
| CP-25 | Fuente para dislexia | Activar opción de dislexia | La tipografía cambia, se persiste al recargar | Media |
| CP-26 | Subtítulos / transcripción | Abrir noticia con audio | Transcripción completa visible y reproductor con controles | Alta |
| CP-27 | Lenguaje claro | Revisar resumen de cada noticia | Resumen en frases cortas, sin jerga | Baja |
| CP-28 | Responsive móvil | Ver en 360 px | Una columna, sin scroll horizontal, objetivos táctiles ≥ 44 px | Alta |
| CP-29 | Eficiencia: peso | Medir carga inicial con DevTools | ≤ 150 KB sin imágenes, sin librerías externas | Media |
| CP-30 | Eficiencia: 3G | Lighthouse con throttling 3G lento | LCP ≤ 2.5 s, imágenes con lazy loading | Media |
| CP-31 | Reducción de movimiento | Activar prefers-reduced-motion | Sin animaciones ni transiciones | Baja |
| CP-32 | Uso sin conexión en celular | Visitar una noticia, activar modo avión y recargar | Se muestra la noticia visitada; páginas nuevas muestran aviso "Sin conexión" | Media |
| CP-33 | Modo lectura simple | Activar "Modo lectura simple" en el panel | Se ocultan imágenes y decoración, el texto permanece, el ajuste se conserva al recargar | Media |
| CP-35 | Primer uso (setup) | Con base vacía abrir login.php; crear admin con clave de 9 caracteres, luego de 10 | Redirige a setup.php; la clave corta muestra error, la válida crea el admin y vuelve al login; setup.php deja de ser accesible | Alta |
| CP-34 | Instalación en celular | Abrir en Chrome Android → "Agregar a pantalla de inicio" | Se instala con nombre e ícono y abre en modo independiente | Baja |
| CP-36 | Contraseña fija retirada | Abrir una base antigua con usuario `admin` / clave publicada | El usuario se elimina y se pide crear uno nuevo | Alta |
| CP-37 | Límite de intentos | Fallar el login 6 veces seguidas | Del 6.º intento responde 429 con aviso; tras 15 min se libera | Alta |
| CP-38 | CSRF | Enviar POST a admin.php/logout.php sin token o con token falso | Respuesta 400 y no se ejecuta la acción | Alta |
| CP-39 | Cerrar sesión por POST | Abrir `logout.php` desde la barra de direcciones | No cierra sesión; solo el botón del menú (POST) la cierra | Media |
| CP-40 | Inyección SQL | Buscar `%' OR 1=1 --`, `%` y `_` | 0 resultados, sin errores; los comodines se tratan como texto | Alta |
| CP-41 | XSS almacenado | Guardar `"><script>alert(1)</script>` en todos los campos | Se muestra como texto en portada, artículo y panel; no se ejecuta | Alta |
| CP-42 | URLs peligrosas | Poner `javascript:alert(1)` o `http://` como imagen/audio | El formulario lo rechaza con mensaje | Alta |
| CP-43 | Archivos internos | Pedir `data/blog.sqlite`, `includes/db.php`, `tests/run.php` | 404 en todos | Alta |
| CP-44 | Cabeceras de seguridad | Revisar respuesta HTTP (DevTools → Red) | CSP sin `unsafe-inline` en scripts, nosniff, X-Frame-Options, cookie HttpOnly + SameSite | Alta |
| CP-45 | Caducidad de sesión | Dejar el panel 30 min sin actividad y actuar | Pide iniciar sesión de nuevo | Media |
| CP-46 | Setup remoto | Abrir `setup.php` desde otro equipo con base vacía | 403, no permite crear el administrador | Alta |
| CP-47 | Pruebas automáticas | `php tests/run.php` y `npm run test:js` | 51 y 9 pruebas correctas | Alta |

Columnas para completar al ejecutar: **Resultado obtenido** (Aprobado / Fallido) y **Evidencia**.

## 8. Estimación y métricas de calidad

### Estimación de esfuerzo (PERT: E = (O + 4M + P) / 6)
| Actividad | O (h) | M (h) | P (h) | E (h) |
|---|---|---|---|---|
| Diseño de casos | 3 | 4 | 6 | 4.2 |
| Preparación de entorno y datos | 1 | 2 | 3 | 2.0 |
| Ejecución funcional | 3 | 4 | 6 | 4.2 |
| Ejecución de accesibilidad (lector, teclado, contraste) | 4 | 6 | 9 | 6.2 |
| Rendimiento y eficiencia | 1 | 2 | 3 | 2.0 |
| Gestión de defectos y regresión | 2 | 3 | 5 | 3.2 |
| Informe final | 1 | 2 | 3 | 2.0 |
| **Total** | | | | **23.8 h ≈ 3 días** |

### Métricas
| Métrica | Fórmula | Meta | Resultado obtenido |
|---|---|---|---|
| Cobertura de requisitos | Requisitos con ≥ 1 caso / total de requisitos × 100 | 100 % | |
| Cobertura de ejecución | Casos ejecutados / casos planificados × 100 | 100 % | |
| Tasa de éxito | Casos aprobados / casos ejecutados × 100 | ≥ 95 % | |
| Densidad de defectos | Defectos encontrados / KLOC (o por módulo) | ≤ 5 por KLOC | |
| Eficacia de detección (DDE) | Defectos hallados en pruebas / (hallados + posteriores) × 100 | ≥ 90 % | |
| Cumplimiento WCAG | Criterios A/AA/AAA cumplidos / criterios evaluados × 100 | 100 % en A y AA, ≥ 90 % en AAA | |
| Defectos críticos abiertos | Conteo | 0 | |

## 9. Gestión de defectos
Severidad: Crítica (bloquea), Alta, Media, Baja. Ciclo: Nuevo → Asignado → Corregido → Reprobado/Verificado → Cerrado. Cada defecto lleva ID, caso relacionado, pasos, evidencia y entorno.

## 10. Riesgos
| Riesgo | Probabilidad | Impacto | Mitigación |
|---|---|---|---|
| Falta de lector de pantalla instalado | Media | Alto | Usar NVDA (gratuito) o VoiceOver nativo |
| Falla de contraste en un tema | Media | Alto | Medir cada tema con Colour Contrast Analyser |
| Tiempo insuficiente | Media | Medio | Priorizar casos de prioridad Alta |

## 11. Trazabilidad con la rúbrica (nivel "Sobresaliente", 5 puntos)
| Categoría | Criterio Sobresaliente | Evidencia en el producto | Casos de prueba |
|---|---|---|---|
| Funcionalidad | Cumple todos los requisitos funcionales y ofrece funcionalidades adicionales | Login con sesión, CRUD de noticias con categorías y etiquetas (`admin.php`); guardado para leer después (`saved.php`); adicionales: buscador en vivo, filtro por categoría, 7 temas, tamaño y tipo de letra, lectura en voz alta, modo lectura simple, uso sin conexión | CP-01 a CP-13, CP-25, CP-32, CP-33 |
| Usabilidad | Fácil de usar y comprender para todos, incluso personas con discapacidades | Mensajes de éxito, error y carga con texto (no solo color); formularios con etiquetas y errores asociados; confirmación antes de borrar; lenguaje claro; atajos de teclado documentados | CP-02, CP-05, CP-07, CP-11, CP-19, CP-27 |
| Accesibilidad | Accesible para discapacidades visuales, auditivas, motoras, cognitivas y otros tipos | Visual: HTML semántico, ARIA, aria-live, alt obligatorio, alto contraste y modos daltónicos. Auditiva: transcripción obligatoria con audio. Motora: teclado completo, enlace de salto, foco ampliado, objetivos de 44 px. Cognitiva: tamaño de letra, dislexia, espaciado, modo lectura simple. Otros: reducción de movimiento, lectura en voz alta, forced-colors | CP-14 a CP-26, CP-31, CP-33 |
| Estilo | Estilo definido, coherente y atractivo | CSS con variables y tokens por tema, componentes consistentes (botones, tarjetas, formularios, mensajes), portada con bloque de bienvenida, diseño responsive | CP-22, CP-28 |
| Eficiencia | Eficiente en recursos y aprovecha las capacidades del dispositivo, incluso con recursos limitados; visible desde celulares | Sin librerías externas, CSS/JS livianos, imágenes con lazy loading y dimensiones, diseño mobile-first, service worker y manifest (uso sin conexión e instalable), búsqueda con cancelación de peticiones | CP-28 a CP-30, CP-32, CP-34 |

## 12. Conclusiones
(Completar tras la ejecución: resultados, defectos hallados, decisión de liberación.)
