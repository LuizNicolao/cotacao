FROM php:8.2-apache

# Instalar extensões PHP necessárias
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    zip \
    unzip \
    git \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd pdo pdo_mysql

# Habilitar mod_rewrite para Apache
RUN a2enmod rewrite

# Configurar o DocumentRoot do Apache
ENV APACHE_DOCUMENT_ROOT /var/www/html

# Atualizar a configuração do Apache
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Definir o diretório de trabalho
WORKDIR /var/www/html

# Copiar os arquivos da aplicação
COPY . /var/www/html/

# Criar e configurar diretório de uploads
RUN mkdir -p /var/www/html/uploads
RUN chown -R www-data:www-data /var/www/html/uploads \
    && chmod -R 777 /var/www/html/uploads

# Definir permissões gerais
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Adicionar script de verificação de permissões
RUN echo '#!/bin/bash\n\
if [ ! -d "/var/www/html/uploads" ]; then\n\
    mkdir -p /var/www/html/uploads\n\
    chown -R www-data:www-data /var/www/html/uploads\n\
    chmod -R 777 /var/www/html/uploads\n\
fi' > /usr/local/bin/check-permissions.sh \
    && chmod +x /usr/local/bin/check-permissions.sh

# Expor a porta 80
EXPOSE 80

# Adicionar comando para verificar permissões ao iniciar o container
CMD ["/bin/bash", "-c", "/usr/local/bin/check-permissions.sh && apache2-foreground"]
