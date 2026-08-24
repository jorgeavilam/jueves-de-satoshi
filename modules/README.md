# Módulos

Punto de extensión. Un módulo es una carpeta con un `module.php` que puede
declarar entradas en el menú del panel (`jds_<nombre>_admin_menu()`) y textos
propios de idioma (`i18n_add()`).

No hay bandera de configuración que active nada: `module_loaded('x')` es la
presencia física de `modules/x/module.php`. Un módulo existe o no existe en el
servidor.

El módulo `hub` —el directorio público de la red, el punto de registro de nodos
y la cola de aprobación— vive en un repositorio privado y por eso no viene en
esta distribución. Es lo que distingue al sitio maestro de un nodo, y la
distinción es de datos: aunque alguien reescribiera el módulo, nacería con un
directorio vacío.
