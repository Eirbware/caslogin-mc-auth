#!/bin/bash

php bin/doctrine orm:generate-proxies
php -S localhost:8000
