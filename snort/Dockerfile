FROM alpine:3.18

# Instalar snort y dependencias para sniffing
RUN apk add --no-cache snort daq tcpdump

# Crear directorios obligatorios para el funcionamiento de Snort
RUN mkdir -p /var/log/snort && \
    mkdir -p /etc/snort/rules && \
    touch /etc/snort/rules/local.rules

# Copiar archivos de configuración
COPY snort.conf /etc/snort/snort.conf
COPY rules/local.rules /etc/snort/rules/local.rules

# Comando de inicio:
# -i any: escucha en todas las interfaces de red de la pila compartida
# -c: ruta de configuración
# -A console: muestra las alertas en tiempo real en los logs de Docker (didáctico)
# -l: ruta de almacenamiento de logs
CMD ["snort", "-i", "any", "-c", "/etc/snort/snort.conf", "-A", "console", "-l", "/var/log/snort"]
