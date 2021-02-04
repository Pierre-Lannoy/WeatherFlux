FROM php:8-cli-alpine

ARG IMAGE_BUILD_DATE
ENV IMAGE_BUILD_DATE $IMAGE_BUILD_DATE

ARG IMAGE_SOURCE_COMMIT
ENV IMAGE_SOURCE_COMMIT $IMAGE_SOURCE_COMMIT

LABEL org.label-schema.name = "WeatherFlux"
LABEL org.label-schema.description = "A gateway to listen WeatherFlow stations on a local network and write weather data in InfluxDB 2."
LABEL org.label-schema.url="https://github.com/Pierre-Lannoy/WeatherFlux"
LABEL org.label-schema.vendor = "Pierre Lannoy <https://pierre.lannoy.fr/>"
LABEL org.label-schema.build-date=$IMAGE_BUILD_DATE
LABEL org.label-schema.vcs-ref=$IMAGE_SOURCE_COMMIT
LABEL org.label-schema.vcs-url="https://github.com/Pierre-Lannoy/WeatherFlux"
LABEL org.label-schema.schema-version = "1.2.4"

RUN  apk update \
  && apk add wget \
  && apk add git \
  && apk add unzip \
  && rm -rf /var/lib/apk/lists/* \
  && docker-php-ext-install pcntl

WORKDIR /tmp/
COPY composer-install.sh composer-install.sh
RUN chmod +x /tmp/composer-install.sh \
  && /tmp/composer-install.sh \
  && rm -f /tmp/composer-install.sh

WORKDIR /usr/share/weatherflux
RUN composer require --no-interaction --no-cache weatherflux/weatherflux
RUN mkdir config
RUN cp ./vendor/weatherflux/weatherflux/config-blank.json ./config/config.json
RUN mkdir logs

VOLUME /usr/share/weatherflux/config
VOLUME /usr/share/weatherflux/logs
EXPOSE 50222/udp

HEALTHCHECK --interval=5m --timeout=10s --start-period=10s --retries=2 \
  CMD php /usr/share/weatherflux/vendor/weatherflux/weatherflux/weatherflux.php status -h

ENTRYPOINT php /usr/share/weatherflux/vendor/weatherflux/weatherflux/weatherflux.php start -c