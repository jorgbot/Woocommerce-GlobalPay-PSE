# Descripcion.

Plugin basado en https://github.com/globalpayredeban/gp-woocommerce-plugin, agregado
soporte para pagos con PSE.

# Instalación en Wordpress 

Comprima el contendio de la carpeta `gp-woocommerce-plugin-master` en .zip e instale
en su wordpress.

# Desarrollo

Renombrar el archivo `get-api.php.example` a `get-api.php` y agregar los datos del comercio.

Para agilizar el desarrollo se crearon las clases:

-  `WC_DM_GlobalPay_GetToken` que genera el 
token de acceso con su funcion `->generate()`.

-  `WP_DM_GlobalPay_HTTP` arma una consulta CURL valida para
el endpoint en GlobalPay ya sea `::post()` o `::get()`.

# Pruebas

Para realiza pruebas agrega el `debbug` a la consulta ejemplo:
- `/get_branks?debbug`
- `/get_order?debbug`
