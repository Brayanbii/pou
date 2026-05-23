# Usar la imagen oficial de PHP con Apache
FROM php:8.2-apache

# Habilitar mod_rewrite de Apache (útil si después añades URLs amigables)
RUN a2enmod rewrite

# Copiar el código fuente de la carpeta src al directorio público del servidor
COPY src/ /var/www/html/

# Exponer el puerto 80 para acceder al contenedor
EXPOSE 80
