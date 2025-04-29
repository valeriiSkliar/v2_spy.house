#!/bin/bash

# Start workers for default queue
php artisan queue:work rabbitmq --queue=default --sleep=3 --tries=3 --max-time=3600 >> storage/logs/worker-default.log 2>&1 &
php artisan queue:work rabbitmq --queue=default --sleep=3 --tries=3 --max-time=3600 >> storage/logs/worker-default.log 2>&1 &

# Start workers for collect-ads queue
php artisan queue:work rabbitmq --queue=collect-ads --sleep=3 --tries=3 --max-time=3600 >> storage/logs/worker-collect-ads.log 2>&1 &
php artisan queue:work rabbitmq --queue=collect-ads --sleep=3 --tries=3 --max-time=3600 >> storage/logs/worker-collect-ads.log 2>&1 &

# Start workers for delayed queue
php artisan queue:work rabbitmq --queue=delayed --sleep=3 --tries=3 --max-time=3600 >> storage/logs/worker-delayed.log 2>&1 &
php artisan queue:work rabbitmq --queue=delayed --sleep=3 --tries=3 --max-time=3600 >> storage/logs/worker-delayed.log 2>&1 &

# Start workers for mail queue
php artisan queue:work rabbitmq --queue=mail --sleep=3 --tries=3 --max-time=3600 >> storage/logs/worker-mail.log 2>&1 &
php artisan queue:work rabbitmq --queue=mail --sleep=3 --tries=3 --max-time=3600 >> storage/logs/worker-mail.log 2>&1 &

echo "All queue workers have been started. Check the logs in storage/logs/ for details."
