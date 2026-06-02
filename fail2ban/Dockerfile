FROM alpine:3.18

# Instalar fail2ban, iptables y utilidades de red
RUN apk add --no-cache fail2ban iptables ip6tables tzdata

# Eliminar cárceles preconfiguradas del sistema (como SSH) que causan errores por falta de logs locales
RUN rm -rf /etc/fail2ban/jail.d/*

# Crear directorios necesarios
RUN mkdir -p /var/run/fail2ban && \
    mkdir -p /var/log/nginx

# Copiar configuraciones customizadas
COPY jail.local /etc/fail2ban/jail.local
COPY filter.d/ /etc/fail2ban/filter.d/

# Asegurar que existan los archivos de log antes de iniciar fail2ban y arrancar en primer plano
CMD touch /var/log/nginx/custom_access.log /var/log/nginx/custom_error.log && \
    fail2ban-server -f -x -v start
