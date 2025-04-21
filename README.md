# api-creator

API Creator es un generador de APIs simple. Permite crear de forma sencilla una o más APIs personalizadas, con capacidad de definir tipos MIME, gestionar parámetros de entrada y salida, y acceder a bases de datos externas mediante scripts PHP. Internamente usa SQLite para almacenar los datos de las APIs creadas, permitiendo modificar o eliminar las que se quieran.

## Características principales

- ✅ **Creación rápida de APIs** indicando MIME de entrada y salida.
- 🔗 **Obtención de parámetros** tanto *in-line* como en la URL.
- 📦 **Conteo de llamadas a los distintos Endpoints** para tener una referencia del número de llamadas reales.
- 🛢️ **Acceso a bases de datos externas** mediante scripts PHP subidos al servidor.
- 📝 **Respuesta directa configurable** sin necesidad de escribir scripts PHP (aunque también se permite).
- 📦 **Incluye varios ejemplos de uso** listos para adaptar.

## Requisitos

- Servidor web con soporte PHP (opcional para funciones avanzadas).
- Editor de texto para modificar archivos de configuración o scripts, si se desea.

## Uso

1. Define los endpoints que deseas. Clave de acceso "admin" - "toor" por defecto.
2. Selecciona los tipos MIME de entrada y salida de datos.
3. Decide si usarás scripts PHP para conectarte a una base de datos externa.
4. (Opcional) Define la lógica de respuesta directamente sin Scripts PHP, si es una API simple.

## Licencia

Este software se distribuye bajo la **Licencia Pública General GNU v3.0 (GPLv3)**.  
Puedes consultarla en: https://www.gnu.org/licenses/gpl-3.0.html

> El autor distribuye este código en beneficio de cualquiera que quiera usarlo, aprender de él o mejorarlo.  
> **Sin embargo, no ofrece ninguna garantía ni asume responsabilidad alguna por el uso que se haga del mismo.**

## Autor

**Francisco José Serrano Rey, 2025**
📧 Contacto: fjsrey@gmail.com
