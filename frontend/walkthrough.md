# Manual y Guía Didáctica de Seguridad en Docker (Desarrollo Local)

¡Felicitaciones! Hemos implementado exitosamente un entorno de seguridad completo y didáctico para tu backend dentro de Docker en Windows (WSL2), optimizado para trabajar con tu frontend en XAMPP.

---

## Qué hemos construido y cómo funciona

Hemos implementado una arquitectura de seguridad por capas compartiendo el espacio de nombres de red del contenedor **Nginx (`loadbalancer`)**. Esto permite un aislamiento perfecto de las herramientas sin sobrecargar tu sistema Windows:

```
                  ┌──────────────────────────────────────────────┐
                  │                 Host Windows                 │
                  │  ┌──────────────┐      ┌──────────────────┐  │
                  │  │ Navegador    │ ───> │ Frontend (XAMPP) │  │
                  │  └──────────────┘      └──────────────────┘  │
                  └───────┬──────────────────────────────────────┘
                          │ (Peticiones a localhost:80/443)
                          ▼
┌────────────────────────────────────────────────────────────────────────┐
│                        Docker (Entorno Linux WSL2)                     │
│                                                                        │
│   ┌────────────────────────────────────────────────────────────────┐   │
│   │           Espacio de Red Compartido (loadbalancer)              │   │
│   │                                                                │   │
│   │    ┌──────────────────┐       ┌─────────────┐   ┌──────────┐   │   │
│   │    │  Nginx (LB)      │ ◄───> │ Fail2ban    │   │ Snort    │   │   │
│   │    │  puertos 80, 443 │       │ (iptables)  │   │ (IDS)    │   │   │
│   │    └────────┬─────────┘       └──────┬──────┘   └────┬─────┘   │   │
│   └─────────────┼────────────────────────┼───────────────┼─────────┘   │
│                 │ (Proxy interno)        │ Lee logs      │ Sniffer     │
│                 ▼                        ▼               ▼             │
│        ┌──────────────────┐      ┌───────────────────────────────┐     │
│        │ API Express:3000 │      │  ./nginx_logs (Carpeta Host)   │     │
│        └────────┬─────────┘      └───────────────────────────────┘     │
│                 ▼                                                      │
│        ┌──────────────────┐                                            │
│        │   MySQL:3306     │                                            │
│        └──────────────────┘                                            │
└────────────────────────────────────────────────────────────────────────┘
```

1. **Nginx (`loadbalancer`):** Actúa como puerta de entrada segura HTTPS y escribe logs de acceso físico en la carpeta `./nginx_logs` montada en tu disco local.
2. **Fail2ban (`fail2ban`):** Contenedor con permisos de red (`NET_ADMIN`) que vigila los archivos en `./nginx_logs`. Si detecta un patrón de ataque, bloquea dinámicamente al agresor usando `iptables`.
3. **Snort (`snort`):** Un Sistema de Detección de Intrusos (IDS) pasivo que escucha en la interfaz de red compartida para analizar firmas sospechosas en tiempo real (XSS, SQLi, escaneos).

---

## Guía Paso a Paso para Iniciar el Servidor

Abre una terminal PowerShell o CMD en la raíz del proyecto (`c:\Users\Angel\Desktop\Proyecto\agencia-backend`) y ejecuta:

1. **Construir e iniciar los contenedores:**
   ```powershell
   docker-compose -f docker-compose.dev.yml up --build -d
   ```
2. **Verificar que todos los servicios estén corriendo:**
   ```powershell
   docker-compose -f docker-compose.dev.yml ps
   ```
   *Deberías ver `loadbalancer`, `api`, `db`, `fail2ban` y `snort` en estado running.*

---

## Laboratorio de Pruebas: Simulación de Ataques (Educativo)

Dado que todo se ejecuta de manera local, la IP de origen que Nginx detecta suele ser la de la pasarela de Docker (`172.x.x.1` o `192.168.65.1`). 
> [!NOTE]
> Para fines didácticos, hemos configurado **tiempos de bloqueo de 60 segundos** (`bantime = 60s`) y un **límite de 3 intentos** (`maxretry = 3`), permitiéndote experimentar y probar sin quedarte bloqueado indefinidamente.

### Experimento 1: Detección de Escaneo de Vulnerabilidades (Fail2ban)
Los atacantes reales utilizan bots para buscar páginas de administración de tecnologías ajenas (ej. buscando archivos de WordPress o PHP).

1. Abre tu terminal de Windows y simula 2 peticiones a un archivo inexistente de WordPress usando `curl.exe` con `-k` (ignorar certificado auto-firmado) apuntando directamente a **HTTPS**:
   ```powershell
   curl.exe -k https://localhost/wp-login.php
   curl.exe -k https://localhost/wp-admin.php
   ```
2. **Comprueba el estado en Fail2ban**:
   ```powershell
   docker exec -it fail2ban fail2ban-client status nginx-noscript
   ```
   *Verás que la IP de origen ha sido agregada a la lista de bloqueados (`Banned IP list`).*
3. **Comprueba el Firewall en caliente (iptables)**:
   Ejecuta el comando dentro del contenedor de `fail2ban` (ya que comparte la red con Nginx y sí tiene instalada la utilidad `iptables`):
   ```powershell
   docker exec -it fail2ban iptables -L -n -v
   ```
   *Verás una regla REJECT o DROP activa apuntando a la IP detectada en la cadena de Fail2ban.*
4. Intenta acceder de nuevo a `http://localhost/` desde el navegador o consola. **¡La petición se quedará colgada o será rechazada de inmediato!**
5. Espera 60 segundos. El ban expirará automáticamente y podrás volver a ingresar.

---

### Experimento 2: Fuerza Bruta a tu API de Autenticación (Fail2ban)
Protege tu endpoint crítico `/api/auth/login` contra ataques de diccionario.

1. Realiza 3 intentos fallidos de login simulando un script de fuerza bruta enviando credenciales erróneas a tu API directamente mediante **HTTPS** (usando el formato de JSON escapado para Windows):
   ```powershell
   curl.exe -k -X POST https://localhost/api/auth/login -H "Content-Type: application/json" -d "{\""email\"":\""hacker@mal.com\"",\""password\"":\""bad\""}"
   curl.exe -k -X POST https://localhost/api/auth/login -H "Content-Type: application/json" -d "{\""email\"":\""hacker@mal.com\"",\""password\"":\""bad\""}"
   curl.exe -k -X POST https://localhost/api/auth/login -H "Content-Type: application/json" -d "{\""email\"":\""hacker@mal.com\"",\""password\"":\""bad\""}"
   ```
2. **Verifica la cárcel de Express**:
   ```powershell
   docker exec -it fail2ban fail2ban-client status express-login-bruteforce
   ```
   *Fail2ban habrá detectado los tres errores de autenticación 401 consecutivas y habrá bloqueado la IP.*

---

### Experimento 3: Detección de Intrusos (Snort IDS)
Snort no bloquea (está configurado como IDS de alerta), pero genera alarmas instantáneas cuando detecta tráfico malicioso inyectado en las peticiones.

1. **Abre un lector de alertas en tiempo real de Snort**:
   ```powershell
   docker logs -f snort
   ```
2. Deja esa consola abierta. En otra terminal de Windows, realiza una petición simulando un **ataque de inyección SQL (SQLi)**:
   ```powershell
   curl.exe -k "http://localhost/api/flights?search=union%20select%201,2,3"
   ```
3. Regresa a la consola de Snort. **Verás aparecer una alerta similar a esta:**
   ```text
   [**] [1:1000003:1] [EDUCATIVO] Posible Inyeccion SQL Detectada (UNION SELECT) [**]
   [Priority: 0] 
   05/31-17:10:15.123456 172.18.0.1:54320 -> 172.18.0.3:3000
   ```
4. Intenta ahora simular una inyección de **Cross-Site Scripting (XSS)**:
   ```powershell
   curl.exe -k "http://localhost/api/flights?name=<script>alert('xss')</script>"
   ```
   *Snort disparará la alerta ID `1000004` correspondiente a la inyección XSS de inmediato.*

---

## Comandos Útiles para Administrar tu Seguridad

* **Ver estado global de Fail2ban:**
  ```powershell
  docker exec -it fail2ban fail2ban-client status
  ```
* **Desbanear una IP manualmente (si te auto-bloqueas por error):**
  ```powershell
  docker exec -it fail2ban fail2ban-client set express-login-bruteforce unbanip <TU_IP>
  ```
* **Ver reglas de firewall activas (ejecutado en fail2ban):**
  ```powershell
  docker exec -it fail2ban iptables -S
  ```
* **Reiniciar las firmas de Snort sin apagar el contenedor:**
  ```powershell
  docker kill -s HUP snort
  ```
