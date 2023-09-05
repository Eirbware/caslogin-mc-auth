#!/bin/bash

php bin/doctrine orm:generate-proxies
php -S 0.0.0.0:8000
