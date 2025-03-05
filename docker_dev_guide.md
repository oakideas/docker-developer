# How to Use Docker for Local Development

Docker is a powerful tool that allows you to create isolated environments for development, ensuring that all dependencies are configured correctly and function consistently, regardless of the operating system, because Docker abstracts away environment differences.

Let's explore how to set up and run local projects using **Docker** with practical examples. The first example is the simplest; the last example is the most complete, resembling a real development environment.

- 1 - **PHP + MySQL**
- 2 - **PHP-FPM + Nginx + MySQL**
- 3 - **Laravel + Nginx + MySQL**
  - 3.1 - **Laravel + Nginx + MySQL + SSL**

## Prerequisites

Before you begin, you need to have **Docker** and **Docker Compose** installed on your system. If you haven't installed them yet, visit the [official documentation](https://docs.docker.com/get-docker/) and follow the instructions for your operating system.

Pay close attention to the command 'docker compose', this is composer v2, for simplicity I am using it in this article, but if you are using composer v1, you can use the command 'docker-compose' instead of 'docker compose'.

## Before You Start

Some of the examples may not work correctly due to conflicts with ports of other services you have running on your system. When studying the examples, before moving on to the next example, ensure that you have stopped the execution of the containers from the previous example to avoid conflicts in port mappings.

To stop the containers, use the command:

```sh
docker compose down
```

Note that the above command will stop all containers and remove all volumes created by docker-compose.yml (the volumes are not removed, you will have to remove them later manually). If you only want to stop the containers without removing them, you can use the command:

```sh
docker compose stop
```

And to restart the containers stopped with the above command, you can use:

```sh
docker compose start
```

Let's jump straight to the first example.

---

## 1. Setting up a **PHP + MySQL** environment with Docker

Let's start with the simplest possible example using Apache as the web server.

### Project Structure:

```
php-mysql-docker/
│── docker-compose.yml
│── php/
│   └── Dockerfile
│── src/
│   └── index.php
```

### Creating the `docker-compose.yml` file

```yaml
services:
  app:
    build: ./php
    container_name: php_app
    volumes:
      - ./src:/var/www/html
    ports:
      - "8080:80"
    depends_on:
      - db

  db:
    image: mysql:8
    container_name: mysql_db
    restart: always
    environment:
      MYSQL_ROOT_PASSWORD: root
      MYSQL_DATABASE: mydb
      MYSQL_USER: user
      MYSQL_PASSWORD: password
    ports:
      - "3306:3306"
    volumes:
      - db_data:/var/lib/mysql

volumes:
  db_data:
```

### Creating the `Dockerfile` for PHP

```dockerfile
FROM php:8.2-apache
RUN docker-php-ext-install mysqli pdo pdo_mysql
```

### Creating a `src/index.php` file

```php
<?php
$dsn = 'mysql:host=db;dbname=mydb;charset=utf8';
$username = 'user';
$password = 'password';

try {
    $pdo = new PDO($dsn, $username, $password);
    echo "MySQL connection successful!";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}

```

### Running the project

```sh
docker compose up -d
```

Access [http://localhost:8080](http://localhost:8080) to verify that the connection has been established.

---

## 2. Setting up a **PHP-FPM + Nginx + MySQL** Environment

I personally haven't used Apache in production for at least a decade, so it's only natural to have a development environment that reflects my production environment. So let's move on to another example.

### Project Structure:

```
php-nginx-mysql-docker/
│── docker-compose.yml
│── php/
│   └── Dockerfile
│── nginx/
│   └── default.conf
│── src/
│   └── index.php
```

### Creating the `docker-compose.yml`

```yaml
services:
  app:
    build: ./php
    container_name: php_fpm
    volumes:
      - ./src:/var/www/html
    depends_on:
      - db

  nginx:
    image: nginx:latest
    container_name: nginx_server
    ports:
      - "8080:80"
    volumes:
      - ./src:/var/www/html
      - ./nginx/default.conf:/etc/nginx/conf.d/default.conf
    depends_on:
      - app

  db:
    image: mysql:8
    container_name: mysql_db
    restart: always
    environment:
      MYSQL_ROOT_PASSWORD: root
      MYSQL_DATABASE: mydb
      MYSQL_USER: user
      MYSQL_PASSWORD: password
    ports:
      - "3306:3306"
    volumes:
      - db_data:/var/lib/mysql

volumes:
  db_data:
```

### Creating the `Dockerfile` for PHP-FPM

```dockerfile
FROM php:8.2-fpm
RUN docker-php-ext-install mysqli pdo pdo_mysql
CMD ["php-fpm"]
```

### Creating the Nginx configuration (`nginx/default.conf`)

```nginx
server {
    listen 80;
    server_name localhost;
    root /var/www/html;
    index index.php index.html;

    location / {
        try_files $uri $uri/ =404;
    }

    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_pass app:9000;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }
}
```

### Creating a `src/index.php` file

```php
<?php
$dsn = 'mysql:host=db;dbname=mydb;charset=utf8';
$username = 'user';
$password = 'password';

try {
    $pdo = new PDO($dsn, $username, $password);
    echo "Connection to MySQL via PHP-FPM and Nginx successful!";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}

```

### Running the project

```sh
docker compose up -d
```

Now, access [http://localhost:8080](http://localhost:8080) to verify the execution with **PHP-FPM + Nginx**.

---

## 3. Setting up a **Laravel + Nginx + MySQL** Environment

Now you might be thinking, these previous examples are kind of useless; nobody writes PHP applications without a framework anymore (right?).
Let's set up a complete environment for developing Laravel applications using **PHP-FPM, Nginx, and MySQL** to have something more useful for our next project.

### Project Structure:

```
laravel-nginx-mysql-docker/
│── docker-compose.yml
│── php/
│   └── Dockerfile
│── nginx/
│   └── default.conf
│── src/ (Laravel Application)
```

### Creating the `docker-compose.yml`

```yaml
services:
  app:
    build: ./php
    container_name: laravel_app
    volumes:
      - ./src:/var/www/html
    depends_on:
      - db

  nginx:
    image: nginx:latest
    container_name: nginx_server
    ports:
      - "8080:80"
    volumes:
      - ./src:/var/www/html
      - ./nginx/default.conf:/etc/nginx/conf.d/default.conf
    depends_on:
      - app

  db:
    image: mysql:8
    container_name: mysql_db
    restart: always
    environment:
      MYSQL_ROOT_PASSWORD: root
      MYSQL_DATABASE: laravel
      MYSQL_USER: user
      MYSQL_PASSWORD: password
    ports:
      - "3306:3306"
    volumes:
      - db_data:/var/lib/mysql

volumes:
  db_data:
```

### Creating the `Dockerfile` for PHP-FPM

```dockerfile
FROM php:8.2-fpm
WORKDIR /var/www/html
RUN apt-get update && apt-get install -y \
    libpng-dev \
    zip \
    unzip \
    git \
    curl \
    && docker-php-ext-install pdo pdo_mysql
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
CMD ["php-fpm"]
```

### Creating the Nginx configuration (`nginx/default.conf`)

```nginx
server {
    listen 80;
    server_name localhost;
    root /var/www/html/public;
    index index.php index.html;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_pass app:9000;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }
}
```

Note that there's already a small difference here compared to example 2.  The root folder here is `/var/www/html/public`.  This is important for Laravel to function correctly. This type of configuration will vary from framework to framework, but the concept is the same.

### Creating the Laravel Project

Well, up to this point, it hasn't been much different from the previous example, but where's Laravel?

Now, let's do the following: we'll use the PHP-FPM container to create the Laravel project. I'm doing it this way to show you how you can use the PHP-FPM container to create the Laravel project and execute the commands you need without having to install PHP on your system.

```sh
docker compose run --rm app composer create-project --prefer-dist laravel/laravel .
```

This command will create the entire set of containers for the application and run the command `composer create-project --prefer-dist laravel/laravel .` in the `app` service (see docker-compose.yml above). Since the `workdir` is `/var/www/html` (See php/Dockerfile), Laravel will be installed in the `src/` folder.

### Configuring Laravel's `.env`

Edit the `.env` file inside the `src/` directory to define the connection to MySQL:

```ini
DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=laravel
DB_USERNAME=user
DB_PASSWORD=password
```

### Running the Project

```sh
docker compose up -d
```

Now, access [http://localhost:8080](http://localhost:8080) to verify the Laravel application running with **PHP-FPM + Nginx + MySQL**. But wait, there's an error!

What happened here is that during the Laravel setup, it created a SQLite database and ran the migrations there. Since we changed the `.env` to use MySQL, we need to run the migrations again.

To solve this problem, just run the migrations again. And this is also the key for you to run any other command with Laravel's artisan.

```sh
docker compose exec app php artisan migrate
```

Now, access [http://localhost:8080](http://localhost:8080) to verify the Laravel application running with **PHP-FPM + Nginx + MySQL**.

### But what if I want to create a Docker for an existing Laravel project?

Place your project files in the `src/` folder, adjust Laravel's `.env` to point to the correct database, for example, and install Laravel's dependencies with Composer.

```sh
docker compose exec app composer install
```

Note that you don't need Composer or PHP installed on your machine; you'll run the above command inside the PHP-FPM container, whose `workdir` is `/var/www/html` and points to the `src/` folder of your project.

That's it; your legacy Laravel project will be running.

NOTE: To avoid complicating the example, I suggested you put the files in `/src`, but I know you won't want to move your legacy project that's in the root to the `src/` folder. To make it work in this case, just change the docker compose to map the `.` folder instead of the `src/` folder. :)

Example, change:
```
volumes:
  - ./src:/var/www/html
```
to
```
volumes:
  - .:/var/www/html
```

## 3.1 Setting up a **Laravel + Nginx + MySQL + SSL** Environment

Sometimes you need to test the environment with SSL. Since we're in a development environment, we'll use the simplest solution: we'll generate a self-signed certificate and configure Nginx to use it. This step-by-step is a continuation of example 3.

### Generating the SSL Certificate

Before configuring Nginx, we need to create a self-signed SSL certificate for our application.

Considering the file and folder tree from example 3, go to the root of this project and execute the commands below to generate the certificate and the private key:

```sh
mkdir -p nginx/certs
openssl req -x509 -newkey rsa:2048 -keyout nginx/certs/server.key -out nginx/certs/server.crt -days 365 -nodes -subj "/CN=localhost"
```

But... what if I'm on Windows, or if I don't have OpenSSL installed?

No problem, we can easily solve this using Docker.

```sh
docker run --rm -v $(pwd)/nginx:/nginx debian:latest bash -c "apt-get update && apt-get install -y openssl && mkdir -p /nginx/certs && openssl req -x509 -newkey rsa:2048 -keyout /nginx/certs/server.key -out /nginx/certs/server.crt -days 365 -nodes -subj '/CN=localhost'"
```

This will create the files `server.crt` (certificate) and `server.key` (private key) inside the `nginx/certs` directory.

### Updating the Nginx Configuration

Now, let's modify the Nginx configuration to use HTTPS.  Edit the `nginx/default.conf` file and add the following configurations:

```nginx
server {
    listen 80;
    server_name localhost;
    return 301 https://$host$request_uri:8443;
}

server {
    listen 443 ssl;
    server_name localhost;
    root /var/www/html/public;
    index index.php index.html;

    ssl_certificate /etc/nginx/certs/server.crt;
    ssl_certificate_key /etc/nginx/certs/server.key;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_pass app:9000;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }
}
```

Here, we configured Nginx to:

-   Automatically redirect HTTP requests to HTTPS.
-   Enable SSL with the `server.crt` and `server.key` files.
-   Continue serving the Laravel application from the `/var/www/html/public` folder.

### Updating `docker-compose.yml`

We need to mount the SSL certificate volume in the Nginx container. Edit the `docker-compose.yml` file and update the `nginx` service section:

```yaml
  nginx:
    image: nginx:latest
    container_name: nginx_server
    ports:
      - "8080:80"
      - "8443:443"
    volumes:
      - ./src:/var/www/html
      - ./nginx/default.conf:/etc/nginx/conf.d/default.conf
      - ./nginx/certs:/etc/nginx/certs
    depends_on:
      - app
```

Here, we added the `8443:443` port to access the Laravel environment via HTTPS and mounted the `nginx/certs` directory in the container.

### Restarting the Containers

Now, let's restart the containers to apply the changes:

```sh
docker compose down
docker compose up -d
```

Now, access [https://localhost:8443](https://localhost:8443) (note the **https** and the port **8443**) to verify the Laravel application running with **PHP-FPM + Nginx + MySQL + SSL**.  You will likely see a browser warning because the certificate is self-signed.  This is expected in a development environment.

## Wrapping Up

Now you have the basics to create Docker development environments for your PHP projects. Explore, adapt, and customize the configurations to meet your needs!

I hope you enjoyed the article. If you have any questions or would like to contribute improvements (or corrections), feel free to [contact me](https://www.linkedin.com/in/fcsil/).
