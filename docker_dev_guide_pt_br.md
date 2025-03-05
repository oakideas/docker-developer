# Como Usar o Docker para Desenvolver Localmente

O Docker é uma ferramenta poderosa que permite criar ambientes isolados para desenvolvimento, garantindo que todas as dependências estejam configuradas corretamente e funcionando de forma consistente, independentemente do sistema operacional, pois o Docker abstrai as diferenças de ambiente..

Vamos explorar como configurar e rodar projetos locais usando **Docker** com exemplos práticos. Os exemplos primeiro exempplo é o mais simples, o último exemplo é o mais completo, mais parecido com um ambiente real de desenvolvimento.

- 1 - **PHP + MySQL**
- 2 - **PHP-FPM + Nginx + MySQL**
- 3 - **Laravel + Nginx + MySQL**
  - 3.1 - **Laravel + Nginx + MySQL + SSL**

## Pré-requisitos

Antes de começar, você precisa ter o **Docker** e o **Docker Compose** instalados no seu sistema. Se ainda não instalou, acesse a [documentação oficial](https://docs.docker.com/get-docker/) e siga as instruções para o seu sistema operacional.

fique atento ao comando 'docker compose', este é o composer v2, para simplicidade estou utilizando ele neste artigo, mas se você estiver usando o composer v1, você pode usar o comando 'docker-compose' no lugar de 'docker compose'. 

## Antes de iniciar

Alguns dos exemplos podem não funcionar corretamente por conta de conflito com portas de algum outro serviço que você tenha rodando no seu sistema. Quando estiver estudando os exemplos, antes de mudar de exemplo, tenha certeza que você parou a execução dos containers do exemplo anterior para não ter conflito nos mapeamentos de portas. 

Para parar os containers use o comando:
```sh
docker compose down
```

Note que o comando acima vai parar todos os containers e remover todos os volumes criados pelo docker-compose.yml (os volumes não são removidos, você terá que remover depois manualmente). Se você quiser apenas parar os containers sem remove-los, você pode usar o comando:

```sh
docker compose stop
```

E para executar novamente os containers parados com o comando acima, você pode usar o comando:

```sh
docker compose start
```

Você pode encontrar os exemplos de código neste [repositório: https://github.com/oakideas/docker-developer](https://github.com/oakideas/docker-developer). Sinta-se à vontade para clonar o repositório e usar os exemplos conforme necessário.

Sem perder mais tempo, vamos para o primeiro exemplo.

---

## 1. Configurando um ambiente **PHP + MySQL** com Docker

Vamos começar com o exemplo mais simples possível usando o Apache como servidor web.

### Estrutura do projeto:

```
php-mysql-docker/
│── docker-compose.yml
│── php/
│   └── Dockerfile
│── src/
│   └── index.php
```

### Criando o arquivo `docker-compose.yml`

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

### Criando o `Dockerfile` para o PHP

```dockerfile
FROM php:8.2-apache
RUN docker-php-ext-install mysqli pdo pdo_mysql
```

### Criando um arquivo `src/index.php`

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

### Rodando o projeto

```sh
docker compose up -d
```

Acesse [http://localhost:8080](http://localhost:8080) para verificar se a conexão foi estabelecida.

---

## 2. Configurando um ambiente **PHP-FPM + Nginx + MySQL**

Eu particularmente não uso o Apache em produção há pelo menos 1 década, então nada mais natural que ter um ambiente de desenvolvimento que reflita meu ambiente de produção, então vamos a mais um exemplo.

### Estrutura do projeto:

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

### Criando o `docker-compose.yml`

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

### Criando o `Dockerfile` para o PHP-FPM

```dockerfile
FROM php:8.2-fpm
RUN docker-php-ext-install mysqli pdo pdo_mysql
CMD ["php-fpm"]
```

### Criando a configuração do Nginx (`nginx/default.conf`)

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

### Criando um arquivo `src/index.php`

```php
<?php
$dsn = 'mysql:host=db;dbname=mydb;charset=utf8';
$username = 'user';
$password = 'password';

try {
    $pdo = new PDO($dsn, $username, $password);
    echo "Conexão com MySQL via PHP-FPM e Nginx bem-sucedida!";
} catch (PDOException $e) {
    echo "Erro: " . $e->getMessage();
}

```

### Rodando o projeto

```sh
docker compose up -d
```

Agora, acesse [http://localhost:8080](http://localhost:8080) para verificar a execução com **PHP-FPM + Nginx**.

---

## 3. Configurando um ambiente **Laravel + Nginx + MySQL**

Agora você deve estar pensando, estes exemplos anteriores são meio que inúteis, ninguém mais escreve aplicação php sem framework (certo?). Vamos configurar um ambiente completo para desenvolvimento de aplicações Laravel utilizando **PHP-FPM, Nginx e MySQL** para ter algo mais útil para nosso próximo projeto.

## Estrutura do projeto:

```
laravel-nginx-mysql-docker/
│── docker-compose.yml
│── php/
│   └── Dockerfile
│── nginx/
│   └── default.conf
│── src/ (Laravel Application)
```

## Criando o `docker-compose.yml`

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

## Criando o `Dockerfile` para o PHP-FPM

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

## Criando a configuração do Nginx (`nginx/default.conf`)

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

Note que já uma pequena diferença aqui em relação ao exemplo 2, a pasta root aqui é /var/www/html/public. Isso é importante para o laravel funcionar corretamente. Este tipo de configuração vai variar de framework para framework, mas o conceito é o mesmo.


## Criando o projeto Laravel

Bom, até aqui não foi muito diferente do exemplo anterior, mas onde está o laravel?

Vamos agora fazer o seguinte, vamos usar o container do php-fpm para criar o projeto Laravel. Estou fazendo assim para mostrar como você pode usar o container do php-fpm para criar o projeto Laravel e executar os comandos que precisa sem ter que instalar o PHP no seu sistema.

```sh
docker compose run --rm app composer create-project --prefer-dist laravel/laravel .
```

Este comando vai criar todo conjunto de containers da aplicação e rodar no serviço app (veja o docker-compose.yml acima) o comando `composer create-project --prefer-dist laravel/laravel .` como o workdir é `/var/www/html` (Veja php/Dockerfile) o laravel será instalado na pasta `src/`

## Configurando o `.env` do Laravel

Edite o arquivo `.env` dentro do diretório `src/` para definir a conexão com o MySQL:

```ini
DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=laravel
DB_USERNAME=user
DB_PASSWORD=password
```

## Rodando o projeto

```sh
docker compose up -d
```

Agora, acesse [http://localhost:8080](http://localhost:8080) para verificar a aplicação Laravel rodando com **PHP-FPM + Nginx + MySQL**. Mas pera ai, deu um erro!

O que aconteceu aqui é que durante o setup do laravel ele criou um banco de dados sqllite e rodou os migrations lá, como alteramos o .env para usar o mysql, precisamos rodar os migrations novamente.

para resolver este problema basta rodar os migrations novamente. E aqui e a chave também para você rodar qualquer outro comando com o artisan do laravel.

```sh
docker compose exec app php artisan migrate
```

Agora sim, acesse [http://localhost:8080](http://localhost:8080) para verificar a aplicação Laravel rodando com **PHP-FPM + Nginx + MySQL**.


### Mas se eu quiser criar um docker para um projeto laravel que já existe?

Coloque os arquivos do seu projeto na pasta src/, ajuste o .env do laravel para apontar para banco correto por exemplo, e instale as dependências do laravel com o composer.

```sh
docker compose exec app composer install
```
Note que você não precisa do composer nem do php instalado na sua máquina, você vai rodar o comando acima dentro do container do php-fpm, cujo workdir é /var/www/html e aponta para a pasta src/ do seu projeto.

pronto, seu projeto laravel legado vai estar rodando.

NOTA: Para não complicar o exemplo, eu sugeri você colocar os arquivos em /src, mas sei que você não vai querer mover seu projeto legado que está na raiz para pasta src/, para funcionar neste caso, basta você alterar o docker compose para mapear a pasta . em vez da pasta src/. :) 

Exemplo, altere 
```
volumes: 
  - ./src:/var/www/html
```
para
```
volumes:
  - .:/var/www/html
```


## 3.1 Configurando um ambiente **Laravel + Nginx + MySQL + SSL**

As vezes você precisa testar o ambiente com SSL, como estamos no ambiente de desenvolvimento vamos usar a solução mais simples possível, vamos gerar um certificado autoassinado e configurar o nginx para usar ele. Este passo a passo é uma continuação do exemplo 3.

### Gerando o Certificado SSL

Antes de configurar o Nginx, precisamos criar um certificado SSL autoassinado para nossa aplicação. 

Considerando a arvore de arquivos e pastas do exemplo 3, vá para a raiz deste projeto e execute os comandos abaixo para gerar o certificado e a chave privada:

```sh
mkdir -p nginx/certs
openssl req -x509 -newkey rsa:2048 -keyout nginx/certs/server.key -out nginx/certs/server.crt -days 365 -nodes -subj "/CN=localhost"
```

Mas... e se estou no windows, ou se não tenho o openssl instalado?

Não tem problema, podemos resolver isso facilmente usando o docker.


```sh
docker run --rm -v $(pwd)/nginx:/nginx debian:latest bash -c "apt-get update && apt-get install -y openssl && mkdir -p /nginx/certs && openssl req -x509 -newkey rsa:2048 -keyout /nginx/certs/server.key -out /nginx/certs/server.crt -days 365 -nodes -subj '/CN=localhost'"
```

Isso criará os arquivos server.crt (certificado) e server.key (chave privada) dentro do diretório nginx/certs.

### Atualizando a configuração do Nginx

Agora, vamos modificar a configuração do Nginx para utilizar HTTPS. Edite o arquivo nginx/default.conf e adicione as configurações abaixo:

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

Aqui, configuramos o Nginx para:
- Redirecionar automaticamente requisições HTTP para HTTPS.
- Habilitar o SSL com os arquivos server.crt e server.key.
- Continuar servindo a aplicação Laravel da pasta /var/www/html/public

### Atualizando o docker-compose.yml

Precisamos montar o volume do certificado SSL no container do Nginx. Edite o arquivo docker-compose.yml e atualize a seção do serviço nginx:

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

Aqui, adicionamos a porta 8443:443 para acessar o ambiente Laravel via HTTPS e montamos o diretório nginx/certs no container.

### Reiniciando os Containers

Agora, vamos reiniciar os containers para aplicar as alterações:

```sh
docker compose down
docker compose up -d
```

Agora, acesse [https://localhost:8443](https://localhost:8443) (atenção **https** e porta **8443**) para verificar a aplicação Laravel rodando com **PHP-FPM + Nginx + MySQL + SSL**.
Você provavelmente verá um aviso no navegador porque o certificado é autoassinado. Isso é esperado em um ambiente de desenvolvimento.

## Finalizando

Agora você tem as bases para criar ambientes de desenvolvimento Docker para seus projetos PHP. Explore, adapte e personalize as configurações para atender às suas necessidades!

Espero que você tenha gostado do artigo. Se tiver alguma dúvida ou quiser contribuir com melhorias (ou correções), sinta-se a vontade para [entrar em contato comigo](https://www.linkedin.com/in/fcsil/).



