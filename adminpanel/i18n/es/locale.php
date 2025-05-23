<?php 
/**
 * Spanish (es) localization for MV framework.
 */
$regionalData = [

	'caption' => 'Español',

	'date_format' => 'dd/mm/yyyy',

	'plural_rules' => [
		'one' => '/^1$/'
	],
	
	'decimal_mark' => ',',
	
	'month' => ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio',
				'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'],
	
	'month_case' => ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio',
				     'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'],
		
	'week_days' => ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'],

   
	'translation' => [

		/* Welcome */
		'mv' => 'MV framework',
		'admin-panel' => 'Panel de administración',
		'welcome' => 'Bienvenido al panel de administración de MV',
		'index-users-icon' => 'Gestión de acceso al panel de administración',
		'index-garbage-icon' => 'Recuperación de datos eliminados',
		'index-history-icon' => 'Historial de operaciones de administración',
		'index-file-manager-icon' => 'Gestión de archivos y carpetas',
		'admin-panel-skin' => 'Personalización de tema',
		
		/* Helpers */
		'yes' => 'Sí',
		'no' => 'No',
		'date' => 'Fecha',
		'day' => 'Día',
		'month' => 'Mes',
		'year' => 'Año',
		'hours' => 'Horas',
		'minutes' => 'Minutos',
		'seconds' => 'Segundos',
		'page' => 'Página',
		'date-from' => 'Desde',
		'date-to' => 'Hasta',
		'number-from' => 'Desde',
		'number-to' => 'Hasta',
		'size-kb' => 'KB',
		'size-mb' => 'MB',
		'not-defined' => 'No definido',
		'has-value' => 'Tiene valor',
		'has-no-value' => 'Sin valor',
		'size' => 'Tamaño',
		'width' => 'Ancho',
		'height' => 'Altura',
		'selected' => 'Seleccionado',
		'not-selected' => 'No seleccionado',
		'see-all' => 'Ver todo',
		'comma' => 'Coma',
		'semicolon' => 'Punto y coma',
		'tabulation' => 'Tabulación',
		'file' => 'Archivo',
		'files' => 'Archivos',
		'directory' => 'Directorio',
		'directories' => 'Directorios',

		/* Buttons */
		'login-action' => 'Iniciar sesión',
		'create' => 'Crear',
		'update' => 'Actualizar',
		'delete' => 'Eliminar',
		'add' => 'Añadir',
		'edit' => 'Editar',
		'apply-filters' => 'Aplicar filtros',
		'reset' => 'Restablecer',
		'restore' => 'Restaurar',
		'cancel' => 'Cancelar',
		'read' => 'Leer',
		'apply' => 'Aplicar',
		'copy' => 'Copiar',
		'paste' => 'Pegar',
		'rename' => 'Renombrar',
		'cut' => 'Cortar',
		'rollback' => 'Revertir',
		'switch-on' => 'Encender',
		'switch-off' => 'Apagar',
		'find' => 'Buscar',
		'search' => 'Buscar',
		'show' => 'Mostrar',
		'hide' => 'Ocultar',
		'back' => 'Atrás',
		'back-to-module' => 'Volver al módulo',
		'create-and-continue' => 'Guardar y crear nueva entrada',
		'update-and-continue' => 'Guardar y continuar editando',
		'create-and-edit' => 'Guardar y comenzar a editar',
		'save' => 'Guardar',
		'quick-edit' => 'Edición rápida',
		'empty-recylce-bin' => 'Vaciar papelera de reciclaje',
		'export-csv' => 'Exportar CSV',
		'import-csv' => 'Importar CSV',
		'upload-file' => 'Subir archivo',
		'upload-image' => 'Subir imagen',
		'multiple-upload' => 'Subida múltiple',
		'upload-many-images' => 'Subir múltiples imágenes',
		'stop-upload' => 'Detener subida',
		'create-folder' => 'Crear carpeta',
		'upload' => 'Subir',
		'upper' => 'Superior',
		'add-edit-comment' => 'Añadir / editar comentario',
		'move-left' => 'Mover a la izquierda',
		'move-right' => 'Mover a la derecha',
		'move-first' => 'Mover al primero',
		'move-last' => 'Mover al último',
		'move-up' => 'Mover hacia arriba',
		'move-down' => 'Mover hacia abajo',
		'move-selected' => 'Seleccionar',
		'select-value' => 'Seleccionar valor',
		'move-not-selected' => 'Deseleccionar',
		'view-download' => 'Descargar / ver',
		'delete-checked' => 'Eliminar seleccionados',
		'display-fields' => 'Mostrar campos',
		'with-selected' => 'Con seleccionados',
		'change-param' => 'Cambiar parámetro',
		'add-param' => 'Añadir parámetro',
		'remove-param' => 'Eliminar parámetro',
		'download-file' => 'Descargar archivo',

		/* Interface */
		'language' => 'Idioma',
		'fast-search' => 'Búsqueda rápida',
		'search-in-all-modules' => 'Buscar en todos los módulos',
		'search-by-name' => 'Buscar por nombre',
		'versions-history' => 'Historial de versiones',
		'versions-limit' => 'Límite de versiones almacenadas',
		'versions-disabled' => 'El historial de versiones está deshabilitado.',
		'versions-history-new' => 'Una vez creada una nueva entrada, el historial de versiones se mostrará aquí.',
		'choose-skin' => 'Elige el tema del panel de administración.',
		'simple-module' => 'Módulo simple',
		'root-catalog' => 'Sección raíz',
		'empty-list' => 'La lista está vacía',
		'create-record' => 'Crear nuevo registro',
		'update-record' => 'Editar registro',
		'filters' => 'Filtros',
		'manage-filters' => 'Gestionar filtros',
		'file-manager' => 'Administrador de archivos',
		'files-top-menu' => 'Archivos',
		'file-params' => 'Parámetros del archivo',
		'last-change' => 'Último cambio',
		'pager-limit' => 'Registros por página',
		'no-images' => 'No hay imágenes disponibles',
		'no-image' => 'No hay imagen disponible',
		'version-set-back' => 'Establecer esta versión como actual',
		'versions-no-yet' => 'No hay versiones anteriores de este registro en este momento.',
		'all-catalogs' => 'Todas las secciones',
		'in-all-catalogs' => 'En todas las secciones',
		'name' => 'Nombre',
		'garbage' => 'Papelera de reciclaje',
		'module' => 'Módulo',
		'creating' => 'Creando',
		'editing' => 'Editando',
		'deleting' => 'Eliminando',
		'restoring' => 'Restaurando',
		'record' => 'Registro',
		'user' => 'Administrador',
		'operation' => 'Operación',
		'operations' => 'Operaciones',
		'users-operations' => 'Historial de operaciones del administrador',
		'other-users-actions' => 'Acciones de otros administradores',
		'send-user-info' => 'Enviar información de la cuenta al administrador',
		'user-data' => 'Datos del administrador',
		'no-changes' => 'Sin cambios',
		'results-found' => 'Resultados encontrados',
		'logs' => 'Historial',
		'modules' => 'Módulos',
		'my-settings' => 'Mis ajustes',
		'to-site' => 'Al sitio',
		'exit' => 'Cerrar sesión',
		'filtration-applied' => 'Filtración aplicada',
		'version-loaded' => 'Versión cargada',
		'active' => 'Activo',
		'users' => 'Administradores',
		'name-person' => 'Nombre',
		'child-records' => 'Subsecciones',
		'email' => 'Correo electrónico',
		'date-registered' => 'Fecha de registro',
		'date-last-visit' => 'Fecha de última visita',
		'login' => 'Inicio de sesión',
		'password' => 'Contraseña',
		'password-repeat' => 'Repetir contraseña',
		'new-password' => 'Nueva contraseña',
		'users-rights' => 'Derechos del administrador',
		'authorization' => 'Autorización',
		'forgot-password' => '¿Olvidaste tu contraseña?',
		'password-restore' => 'Recuperación de contraseña',
		'remember-me' => 'Recuérdame en este equipo',
		'caution' => 'Advertencia',
		'to-authorization-page' => 'A la página de autorización',
		'get-ready' => 'Preparándose',
		'captcha' => 'Código de seguridad',
		'choose-fields-import-csv' => 'Selecciona los campos para importar en el orden en que aparecen en el archivo CSV.',
		'choose-fields-export-csv' => 'Selecciona los campos para exportar y ordénalos en el orden deseado.',
		'column-separator' => 'Separador de columnas',
		'first-line-headers' => 'Encabezados en la primera línea',
		'file-csv' => 'Archivo CSV',
		'file-encoding' => 'Codificación de archivo',
		'update-order' => 'Actualizar orden',
		'update-and-create' => 'Actualizar y crear registros',
		'update-only' => 'Actualizar solo registros',
		'create-only' => 'Crear solo registros',

		/* Errors */
		'error-failed' => "La operación no se realizó.",
		'error-occurred' => 'Ocurrió un error. Operación detenida.',
		'error-wrong-token' => "Clave de verificación no válida. Inténtalo de nuevo.",
		'error-wrong-ajax-token' => "Clave de verificación no válida. Recarga la página.",
		'error-no-rights' => 'No tienes suficientes derechos para realizar esta operación o acceder a este módulo.',
		'error-params-needed' => 'Parámetros insuficientes para mostrar la página.',
		'error-wrong-record' => 'El registro solicitado no fue encontrado.',
		'error-page-not-found' => 'La página solicitada no fue encontrada.',
		'error-undefined-value' => "El campo '{field}' contiene un valor no válido.",
		'error-must-match' => "Los valores de los campos '{field_1}' y '{field_2}' deben coincidir.",
		'error-required' => "El campo '{field}' es obligatorio.",
		'error-required-bool' => "El campo '{field}' debe estar marcado.",
		'error-required-enum' => "Debes seleccionar un valor para el campo '{field}'.",
		'error-required-file' => "Debe seleccionarse un archivo para el campo '{field}'.",
		'error-required-image' => "Debe seleccionarse una imagen para el campo '{field}'.",
		'error-required-multi-images' => "Debe seleccionarse una o más imágenes para el campo '{field}'.",
		'error-email-format' => "Formato no válido para el campo '{field}'. Ingresa un correo en el formato nombre@dominio.zona.",
		'error-not-int' => "El campo '{field}' debe contener un número entero.",
		'error-not-float' => "El campo '{field}' debe contener un número decimal.",
		'error-date-format' => "La fecha en el campo '{field}' debe estar en el formato '{date_format}'.",
		'error-date-values' => "Deben seleccionarse el día, mes y año para el campo '{field}'.",
		'error-date-time-format' => "La fecha y hora en el campo '{field}' deben estar en el formato '{date_time_format}'.",
		'error-date-time-values' => "Deben seleccionarse el día, mes, año, horas y minutos para el campo '{field}'.",
		'error-short-password' => "El campo '{field}' debe contener al menos {min_length} [símbolo].",
		'error-regexp' => "El campo '{field}' no coincide con el formato requerido.",
		'error-length' => "El campo '{field}' debe tener una longitud de {length} [símbolo].",
		'error-min-length' => "La longitud mínima del campo '{field}' debe ser {min_length} [símbolo].",
		'error-max-length' => "La longitud máxima del campo '{field}' debe ser {max_length} [símbolo].",
		'error-unique-value' => "El valor del campo '{field}' ya está en uso y debe ser único.",
		'error-unique-restore' => "El valor del campo '{field}' en el registro restaurado ya está en uso y debe ser único.",
		'error-zero-forbidden' => "El valor del campo '{field}' no puede ser cero.",
		'error-not-positive' => "El valor del campo '{field}' debe ser un número positivo.",
		'error-letters-required' => "El campo '{field}' debe contener letras.",
		'error-digits-required' => "El campo '{field}' debe contener dígitos.",
		'error-phone-format' => "El campo '{field}' solo puede contener dígitos y los símbolos (,), -, y +.",
		'error-redirect-format' => "El campo '{field}' debe contener una URL en el formato 'http://ejemplo.com'.",
		'error-url-format' => "El campo '{field}' solo puede contener letras latinas, dígitos y el símbolo '-'. Deben estar presentes letras.",
		'no-delete-parent' => 'No se realizó la eliminación. Este registro tiene registros secundarios que deben eliminarse o moverse primero.',
		'no-delete-model' => "No se realizó la eliminación. Este registro tiene registros secundarios en el módulo '{module}' que deben eliminarse o moverse primero.",
		'no-delete-root' => "El administrador principal no puede ser eliminado.",
		'upload-file-error' => 'El archivo no se subió. Error de transferencia de datos o el tamaño del archivo es demasiado grande.',
		'wrong-captcha' => 'Código de seguridad incorrecto',
		'login-failed' => 'Inicio de sesión o contraseña no válidos',
		'not-user-email' => 'No se encontró un administrador con esta dirección de correo electrónico',
		'password-not-confirmed' => 'La contraseña no fue confirmada debido a un tiempo de espera prolongado o ya ha sido confirmada.',
		'bad-extension' => 'Operación no realizada. Extensión de archivo no válida.',
		'forbidden-directory' => 'Este directorio está prohibido para su visualización.',
		'error-data-transfer' => "Error en la transferencia de datos.",
		'no-rights' => "No tienes suficientes derechos para realizar esta operación.",
		'folder-exists' => 'Ya existe una carpeta con este nombre.',
		'file-exists' => 'Ya existe un archivo con este nombre.',
		'folder-not-created' => 'La carpeta no fue creada. El nombre de la carpeta puede contener caracteres no válidos.',
		'not-deleted' => 'No se realizó la eliminación.',
		'bad-folder-name' => "El nombre de la carpeta contiene caracteres no válidos. Solo se permiten letras latinas, dígitos y los símbolos '-' y '_'.",
		'bad-file-name' => "El nombre del archivo contiene caracteres no válidos. Solo se permiten letras latinas, dígitos y los símbolos '.', '-', y '_'.",
		'passwords-must-match' => 'Las contraseñas deben coincidir.',
		'wrong-images-type' => 'Está prohibido subir imágenes de este formato. Formatos permitidos: {formats}.',
		'wrong-filemanager-type' => "Está prohibido subir archivos de este tipo.",
		'wrong-files-type' => "Está prohibido subir archivos de este tipo para el campo '{field}'.",
		'wrong-file-type' => "Está prohibido subir archivos de este tipo para el campo '{field}'. Formatos permitidos: {formats}.",
		'too-heavy-file' => 'El archivo subido es demasiado grande. El tamaño máximo es {weight}.',
		'too-heavy-image' => "La imagen subida en el campo '{field}' es demasiado grande. El tamaño máximo es {weight}.",
		'too-large-image' => "La imagen subida en el campo '{field}' es demasiado grande. Las dimensiones máximas son {size} píxeles.",
		'too-heavy-image-editor' => "La imagen subida es demasiado grande. El tamaño máximo es {weight}.",
		'too-large-image-editor' => "La imagen subida es demasiado grande. Las dimensiones máximas son {size} píxeles.",
		'error-not-all-params' => 'Parámetros insuficientes para la actualización.',
		'error-wrong-csv-file' => 'Se subió un archivo incorrecto para la actualización.',
		'update-was-failed' => 'No se realizó la actualización.',
		'warning-development-mode' => 'El sitio está funcionando en modo de depuración. Necesitas establecer el valor "production" para la opción "APP_ENV" en el archivo .env.',
		'warning-root-password' => 'Necesitas cambiar la contraseña predeterminada de root.',
		'warning-logs-folder' => 'Necesitas establecer permisos de escritura para la carpeta de registros.',
		'warning-userfiles-folder' => 'Necesitas establecer permisos de escritura para la carpeta de archivos de usuario y todos los directorios anidados.',
		'warning-dangerous-code' => 'Código potencialmente dañino en el archivo:',
		
		/* Messages */
		'done-create' => 'El nuevo registro se agregó con éxito.',
		'done-update' => 'Los cambios se guardaron con éxito.',
		'done-delete' => 'La eliminación se realizó con éxito.',
		'done-restore' => 'La restauración se realizó con éxito.',
		'done-operation' => 'La operación se realizó con éxito.',
		'created-now-edit' => 'El nuevo registro ha sido creado y está listo para ser editado.',
		'complete-login' => 'Ingrese el inicio de sesión',
		'complete-password' => 'Ingrese la contraseña',
		'complete-captcha' => 'Ingrese el código de seguridad',
		'complete-email' => 'Ingrese la dirección de correo electrónico',
		'password-confirmed' => 'La nueva contraseña se confirmó con éxito',
		'folder-created' => 'La nueva carpeta se creó con éxito.',
		'file-uploaded' => 'El archivo se cargó con éxito.',
		'user-account-created' => 'Se ha creado una cuenta para usted en el panel de administración.',
		'user-account-updated' => 'Su cuenta en el panel de administración ha sido actualizada.',
		'change-password' => 'Ha solicitado un restablecimiento de contraseña para el acceso al panel de administración.',
		'change-password-sent' => 'Las instrucciones para restablecer la contraseña se han enviado a la dirección de correo proporcionada.',
		'change-password-ok' => 'La nueva contraseña se envió a la dirección de correo proporcionada y debe confirmarse dentro de {number} [in-hour].',
		'confirm-time' => 'La nueva contraseña debe confirmarse dentro de {number} [in-hour]. Por favor, siga el enlace a continuación.',
		'maximum-files-one-time' => 'Máximo {number} archivos a la vez',
		'delete-one' => "¿Eliminar el registro '{name}'?",
		'delete-many' => "¿Eliminar {number_records}?",
		'delete-one-finally' => "¿Eliminar el registro '{name}' permanentemente?",
		'delete-many-finally' => "¿Eliminar {number_records} permanentemente?",
		'restore-one' => "¿Restaurar el registro '{name}'?",
		'restore-many' => "¿Restaurar {number_records}?",
		'update-many-bool' => "¿Establecer el parámetro '{caption}' a '{value}' para {number_records}?",
		'update-many-enum' => "¿Cambiar el parámetro '{caption}' para {number_records}?",
		'update-many-m2m-add' => "¿Agregar el parámetro '{caption}' para {number_records}?",
		'update-many-m2m-remove' => "¿Eliminar el parámetro '{caption}' para {number_records}?",
		'sort-by-column' => "Para cambiar el orden de los elementos, la tabla debe estar ordenada por este campo.",
		'all-parents-filter' => "Para cambiar el orden de los elementos, debe eliminar el filtro en el campo '{field}'.",
		'parent-filter-needed' => "Para cambiar el orden de los elementos, debe establecer un filtro en el campo '{field}'.",
		'delete-files' => "¿Eliminar {number_files}?",
		'delete-file' => "¿Eliminar el archivo '{name}'?",
		'delete-folder' => "¿Eliminar la carpeta '{name}'?",
		'rename-file' => "¿Renombrar el archivo '{name}?'",
		'rename-folder' => "¿Renombrar la carpeta '{name}?'",
		'add-image-comment' => "Agregar/editar un comentario a la imagen",
		'not-uploaded-files' => "Archivos no cargados",
		'select-fields' => "Debe seleccionar los campos requeridos.",
		'select-csv-file' => "Debe seleccionar un archivo csv.",
		'quick-edit-limit' => "Con la configuración actual de columnas de la tabla, el límite de registros por página no debe superar {number}.",
		'update-was-successful' => 'La carga fue exitosa.',
		'created-records' => 'Registros creados',
		'updated-records' => 'Registros actualizados',
		'declined-strings' => 'Números de cadenas rechazadas',
		'declined-ids' => 'Números de ID rechazados',
		'root-rights' => 'Este administrador tiene todos los derechos sobre todos los módulos.',

		/* Plural rules */
		'number-records' => '{number} [records]',
		'number-for-records' => '{number} [for-record]',
		'found-records' => '[found] {number} [records]',
		'no-records-found' => 'No se encontraron registros',
		'affected-records' => '{ids_number} [records]',
		'number-files' => '{number} [files]',
		'symbol' => ['one' => 'símbolo', 'few' => 'símbolos', 'many' => 'símbolos', 'other' => 'símbolos'],
		'field-signs' => 'El campo debe contener {number} [sign]',
		'sign' => ['one' => 'signo', 'few' => 'signos', 'many' => 'signos', 'other' => 'signos'],
		'for-record' => ['one' => 'registro', 'other' => 'registros'],
		'records' => ['one' => 'registro', 'few' => 'registros', 'many' => 'registros', 'other' => 'registros'],
		'in-hour' => ['one' => 'hora', 'other' => 'horas'],
		'files' => ['one' => 'archivo', 'few' => 'archivos', 'other' => 'archivos'],
		'found' => ['one' => 'encontrado', 'few' => 'encontrados', 'other' => 'encontrados'],
		'comments' => ['one' => 'comentario', 'few' => 'comentarios', 'many' => 'comentarios', 'other' => 'comentarios']     
   ]
];