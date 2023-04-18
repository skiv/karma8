FROM php:8.1-fpm

RUN apt-get update; \
        apt-get upgrade -y; \
        apt-get install -y --no-install-recommends \
                default-mysql-client \
                cron

RUN docker-php-ext-install pcntl
RUN docker-php-ext-install mysqli

CMD ["php", "-S", "0.0.0.0:8000"]

COPY crontab /etc/cron.d/crontab
RUN chmod 0644 /etc/cron.d/crontab
RUN crontab /etc/cron.d/crontab
RUN touch /var/log/cron.log
EXPOSE 80

