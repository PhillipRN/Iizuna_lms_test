FROM php:8.2-apache

# 日本語ロケール設定
ENV LANG ja_JP.UTF-8
ENV LANGUAGE ja_JP:ja
ENV LC_ALL ja_JP.UTF-8

# 必要なパッケージのインストール
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libicu-dev \
    libonig-dev \
    default-mysql-client \
    curl \
    vim \
    locales \
    && echo "ja_JP.UTF-8 UTF-8" > /etc/locale.gen \
    && locale-gen \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
    pdo \
    pdo_mysql \
    mysqli \
    zip \
    gd \
    intl \
    mbstring \
    opcache \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Composer インストール
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# AWS CLI インストール（DynamoDB Local操作用）
RUN curl "https://awscli.amazonaws.com/awscli-exe-linux-x86_64.zip" -o "awscliv2.zip" \
    && unzip awscliv2.zip \
    && ./aws/install \
    && rm -rf awscliv2.zip aws

# Apache設定
RUN a2enmod rewrite
COPY docker/apache-config.conf /etc/apache2/sites-available/000-default.conf

# PHP設定
RUN { \
    echo 'display_errors = On'; \
    echo 'error_reporting = E_ALL'; \
    echo 'date.timezone = Asia/Tokyo'; \
    echo 'mbstring.language = Japanese'; \
    echo 'memory_limit = 256M'; \
    echo 'upload_max_filesize = 20M'; \
    echo 'post_max_size = 20M'; \
} > /usr/local/etc/php/conf.d/custom.ini

# 作業ディレクトリ設定
WORKDIR /var/www/html

# composer.jsonとcomposer.lockをコピー
COPY composer.json composer.lock ./

# 依存関係のインストール
RUN composer install --no-scripts --no-autoloader --no-dev

# アプリケーションファイルのコピー
COPY . .

# Composer autoload最適化
RUN composer dump-autoload --optimize

# 必要なディレクトリを作成
RUN mkdir -p app/smarty_template_c app/Temps app/Assets/Sounds \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 777 app/smarty_template_c app/Temps

EXPOSE 80

CMD ["apache2-foreground"]
