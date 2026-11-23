# GDW_AsyncIndex Free para Magento 2
[![Latest Stable Version](https://img.shields.io/packagist/v/gdw/asyncindex?style=for-the-badge)](https://packagist.org/packages/gdw/asyncindex) [![PHP Version Require](https://img.shields.io/packagist/dependency-v/gdw/asyncindex/php?style=for-the-badge)](https://packagist.org/packages/gdw/asyncindex) [![Magento Framework Require](https://img.shields.io/packagist/dependency-v/gdw/asyncindex/magento%2Fframework?style=for-the-badge)](https://packagist.org/packages/gdw/asyncindex)

GDW_AsyncIndex permite gestionar tareas de indexación de Magento de forma
asíncrona. Las solicitudes se agregan a una cola y el cron de Magento procesa
los trabajos, evitando ejecutar operaciones largas dentro de la solicitud del
administrador.

## Compatibilidad

- Rama 4.4.x: Magento 2.4.4+ con PHP 8.1+
- Rama 4.x: Magento 2.4.0 a 2.4.3 con PHP 7.4
- Rama 3.x: Magento 2.3.7 con PHP 7.4

## Funciones destacadas

- Agrega trabajos para reindexar indexadores seleccionados.
- Procesa las actualizaciones pendientes de los indexadores seleccionados.
- Permite invalidar indexadores seleccionados.
- Permite solicitar una reindexación completa.
- Muestra el estado de los indexadores en una cuadrícula nativa de Magento.
- Muestra los 10 trabajos más recientes.
- Ejecuta los trabajos mediante el grupo cron `gdw`.
- Conserva por 10 días el historial de trabajos y los logs operativos.
- Rechaza trabajos duplicados y limita la cola a 10 trabajos pendientes.

Procesa un trabajo por ejecución de cron y asigna prioridad
baja a los trabajos. 

## Dependencias

- `GDW_Core` (`gdw/core:^4.4`)
- Magento Indexer
- Magento Backend
- Magento Cron

> [!TIP]
> **¿Necesitas ampliar las capacidades de indexación?** La edición Free resuelve
> lo esencial: permite reindexar desde el administrador de Magento, además de
> procesar pendientes e invalidar indexadores. La edición Pro potencia estas
> funciones con más control y herramientas operativas, como seguimiento del
> progreso, historial completo, reintentos, programaciones y comandos CLI.
> [Conoce GDW_AsyncIndexPro](https://github.com/josecruzchavez/GDW_AsyncIndexPro#readme).

## Comparativa Free vs Pro

| Función o ajuste | Free | Pro |
| --- | --- | --- |
| Encolar y procesar trabajos de indexación | Incluido | Incluido |
| Reindexar, procesar pendientes e invalidar indexadores | Incluido | Incluido; se puede habilitar o deshabilitar cada acción |
| Trabajos procesados por ejecución de cron | Fijo: 1 | Configurable (1 por defecto) |
| Máximo de trabajos pendientes | Fijo: 10 | Configurable (10 por defecto) |
| Política para trabajos duplicados | Fija: bloquea trabajos idénticos | Configurable: bloquear idénticos, bloquear indexadores coincidentes o permitir duplicados |
| Prioridad de trabajos | Fija: baja | Seleccionable por trabajo: baja, normal o alta |
| Reintentos automáticos | Fijos: deshabilitados | Configurables; deshabilitados por defecto y con máximo de 3 reintentos |
| Tiempo para marcar un trabajo detenido | Fijo: 2 horas | Configurable (2 horas por defecto) |
| Retención del historial | Fija: 10 días | Configurable (30 días por defecto) |
| Logging operativo y retención de logs | Fijo: habilitado, 10 días | Configurables; habilitado y 30 días por defecto |
| Estado de indexadores y resumen de trabajos recientes | Incluido; muestra los 10 más recientes | Incluido; resumen de 10 y acceso al historial completo |
| Panel visual de progreso | No incluido | Incluido |
| Historial completo paginado, filtros y ordenamiento | No incluido | Incluido; tamaño de página configurable |
| Detalle, reintento manual y cancelación de trabajos | No incluido | Incluidos; se pueden habilitar o deshabilitar |
| Exportación CSV | No incluida | Incluida; se puede habilitar o deshabilitar |
| Programaciones recurrentes desde Admin | No incluidas | Incluidas |
| Comandos CLI administrativos | No incluidos | Incluidos |

En Free, los límites y políticas indicados son fijos. Pro permite modificar
los ajustes señalados desde la configuración de Magento; los valores entre
paréntesis son sus valores iniciales.

## Otros enlaces

- [Sitio web](https://gdw.mx/?utm_source=github&utm_medium=gdw&utm_campaign=asyncindex&utm_id=link)
- [Listado de módulos](https://gdw.mx/modulos/)
- [Facebook](https://www.facebook.com/GestionDigitalWeb)
- [YouTube](https://www.youtube.com/c/Gestiondigitalweb)

## Documentación

- [Documentación del módulo](https://docs.gdw.mx/modulos/gdw_asyncindex)
- [Changelog](https://docs.gdw.mx/modulos/gdw_asyncindex/changelog)

