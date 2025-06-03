# Plugin Lexhoy Despachos Search

Plugin para WordPress que permite gestionar y buscar despachos de abogados integrado con Algolia.

## Características

- Búsqueda avanzada de despachos de abogados
- Integración con Algolia para búsquedas rápidas y precisas
- Panel de administración para gestionar despachos
- Gestión de áreas de práctica
- Filtros por provincia, localidad y área de práctica
- Diseño responsive
- Shortcode para insertar el buscador en cualquier página

## Requisitos

- WordPress 5.0 o superior
- PHP 7.4 o superior
- Composer para gestionar dependencias
- Cuenta en Algolia con las credenciales necesarias

## Instalación

1. Clona este repositorio en la carpeta `wp-content/plugins/` de tu instalación de WordPress:

   ```bash
   cd wp-content/plugins/
   git clone https://github.com/tu-usuario/Plugin-Abogados-Updated.git
   ```

2. Instala las dependencias con Composer:

   ```bash
   cd Plugin-Abogados-Updated
   composer install
   ```

3. Activa el plugin desde el panel de administración de WordPress.

4. Configura las credenciales de Algolia en la página de configuración del plugin.

## Uso

### Shortcode

Para mostrar el buscador de despachos en cualquier página o post, usa el siguiente shortcode:

```
[lexhoy_despachos_search]
```

### Panel de Administración

El plugin añade un nuevo menú en el panel de administración de WordPress con las siguientes secciones:

- Listado de Despachos
- Áreas de Práctica
- Configuración de Algolia
- Shortcode

## Configuración

1. Ve a "Despachos > Algolia" en el panel de administración.
2. Introduce tus credenciales de Algolia:
   - Application ID
   - Admin API Key
   - Search API Key
   - Write API Key
   - Usage API Key
   - Monitoring API Key

## Contribuir

1. Haz un Fork del repositorio
2. Crea una rama para tu feature (`git checkout -b feature/AmazingFeature`)
3. Haz commit de tus cambios (`git commit -m 'Add some AmazingFeature'`)
4. Push a la rama (`git push origin feature/AmazingFeature`)
5. Abre un Pull Request

## Licencia

Este proyecto está licenciado bajo la Licencia MIT - ver el archivo [LICENSE](LICENSE) para más detalles.

## Soporte

Si necesitas ayuda o tienes alguna pregunta, por favor abre un issue en el repositorio de GitHub.
