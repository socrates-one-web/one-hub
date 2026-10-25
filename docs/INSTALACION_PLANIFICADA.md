# Instalación planificada — ONE HUB Community

> Guía preliminar basada en el instalador CLEAN/DEMO usado durante las pruebas. La documentación definitiva deberá verificarse de nuevo con los ZIP publicados.

## Requisitos

- Servidor web con PHP **8.1 o superior**
- Extensión PDO MySQL
- OpenSSL, Fileinfo y mbstring
- Base de datos MySQL y usuario con los permisos necesarios
- HTTPS recomendado para la aplicación pública

## CLEAN

1. Crear una base MySQL **vacía** y un usuario dedicado.
2. Subir el paquete CLEAN al directorio del sitio.
3. Abrir `install.php` y configurar URL, conexión MySQL y primer administrador.
4. Mantener desactivada la generación de datos DEMO.
5. **No activar la opción de restablecer/eliminar tablas** salvo en una base de pruebas desechable.
6. Completar la instalación y comprobar inicio de sesión, permisos y rutas.

## DEMO

1. Crear **otra** base MySQL vacía, diferente de CLEAN.
2. Instalar la edición DEMO en una ruta separada.
3. Activar la generación de datos ficticios.
4. Bloquear envíos de email/WhatsApp desde todos los módulos.
5. Restringir permisos de usuarios públicos y programar un restablecimiento de datos ficticios si se habilita acceso abierto.

## EMAILSEND

EMAILSEND utiliza una base MySQL independiente. Crear una base vacía adicional **por instancia** de ONE HUB; importar el esquema suministrado y copiar `emailsend/config.example.php` a `emailsend/config.php`.

Configurar la URL, la conexión a MySQL y una `app_key` única y aleatoria. No copiar configuraciones reales ni contactos desde otros sistemas. La carpeta `emailsend/install/` está protegida contra navegación web; utilizar phpMyAdmin para importar el esquema SQL.

La DEMO debe conservar el envío real **deshabilitado**. En CLEAN se deberá configurar SMTP solamente después de verificar la identidad del remitente y las condiciones legales aplicables.

## Seguridad

No subir `config.php`, bases de clientes, claves SMTP, claves API ni información real al repositorio público. Los archivos de ejemplo utilizan exclusivamente valores ficticios.

Documentación de pre-lanzamiento — [sitio oficial](https://os.socrates.la/).
