[![Actions Status](https://github.com/ibra1994/studocu/workflows/CI/badge.svg)](https://github.com/ibra1994/studocu/actions)

# **Flashcard console app**

### Installation

* `docker-compose up -d`
* `docker-compose exec laravel.test bash`
* `composer install`
* `php artisan migrate`

### Run Flashcard app

`php artisan flashcard:interactive`

### Run tests

`php vendor/phpunit/phpunit/phpunit --configuration phpunit.xml tests`
