# Docker tuto LAMP STACK for Ubuntu - Aug 2021

1. Install docker.io from Ubuntu packages and launch it:
- `sudo systemctl enable --now docker`
- `sudo usermod -aG docker [username]`
- `docker --version`
- `systemctl status docker`
- `newgrp docker` (to be added yourself to the docker group)

Test projet: `docker run hello-world`
(bug permission denied ? solve it with `newgrp docker`)


2. Root folder of the project:
    - create "docker-compose.yaml"
    - "public" folder
    - in that public folder: index.php with hello world or phpinfo().

3. Content of the yaml:
    - version: '3.7' (not sure why this version, to be documented)
    - services:
        - db: image "mysql" (with all config lines)
        - www: image "php" apache (+ config ports, volumes...)

4. When yaml is ready: `docker-compose up`

TODO: more details to add from that tutorial https://linuxhint.com/lamp_server_docker/


# Docker tuto LAMP STACK 2 for Ubuntu, version 2

1. Install docker.io from Ubuntu packages and launch it:
- `sudo systemctl enable --now docker`
- `sudo usermod -aG docker [username]`
- `docker --version`
- `systemctl status docker`
- `newgrp docker` (to be added yourself to the docker group)

Test projet: `docker run hello-world`
(bug permission denied ? solve it with `newgrp docker`)


2. Create a "public" folder, and a file "docker-compose.y(a)ml" with this content

version: "3.7"
services:
  web-server:
    build:
      dockerfile: php.Dockerfile
      context: .
    restart: always
    volumes:
      - "./public/:/var/www/html/"
    ports:
      - "8080:80"
  mysql-server:
    image: mysql:latest
    restart: always
    environment:
      MYSQL_ROOT_PASSWORD: root
    volumes:
      - mysql-data:/var/lib/mysql

  phpmyadmin:
    image: phpmyadmin/phpmyadmin:5.0.1
    restart: always
    environment:
      PMA_HOST: mysql-server
      PMA_USER: root
      PMA_PASSWORD: root
    ports:
      - "5000:80"
volumes:
  mysql-data:

3. create a file "php.Dockerfile" with this content below. It defines the "php" module and its dependencies

FROM php:7.4.3-apache
RUN docker-php-ext-install mysqli pdo pdo_mysql

4. in the html folder, create a file index.php containing just a PDO connection (for testing):

<?php
$host = "mysql-server";
$user = "root";
$pass = "root";
$db = "app1";
try {
    $conn = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
 
    echo "Docker LAMP Stack - Connected successfully";
} catch(PDOException $e) {
    echo "Docker LAMP Stack - Connection failed: " . $e->getMessage();
}
?>

5. `newgrp docker` (in order to avoid the permission denied problem)
`docker-compose build` (for the first initialization)

6. 
`docker-compose up -d` (starts Docker -  the "-d" means run in background, without terminal freezing)
`docker ps` (lists all the processes)
`docker kill $(docker ps -q)` (stops all the docker processes)

7. Error forbidden ? Systemctl restart docker

8. Last thing: database app1 needs to be created in phpmyadmin on: localhost:5000

9. Access: localhost:8080


# Docker Nginx

1. Launching this command allows you to run an nginx instance.
`docker run -it --rm -d -p 8080:80 --name web nginx`
(Error forbidden ? `newgrp docker`)