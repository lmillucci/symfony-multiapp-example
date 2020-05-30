# Symfony 5 application with multiple applications
Starting from Symfony 4 creating applications with multiple kernels [is no longer recommended](https://symfony.com/doc/current/configuration/multiple_kernels.html).
They suggest to creare multiple different applications but this is could lead to have a big quantity of duplicated code.

To avoid this situation I've found this [great solution](https://github.com/yceruto/symfony-skeleton-vkernel) and I've decided to give it a try. 
In this repository you find a very basic application that works using the `VirtualKernel` method.

# How to use the files  
NOTE: To run this project you need Docker.

- Add these entries to your `/etc/hosts` file:
```
127.0.0.1 admin.local.it
127.0.0.1 api.local.it
127.0.0.1 site.local.it
```
- run `docker-compose up`
- Open a shell inside the container with `docker exec -it web-multiapp bash`
- install dependencies with `composer install`
- visit `http://admin.local.it/` `http://api.local.it/` or `http://site.local.it/`
