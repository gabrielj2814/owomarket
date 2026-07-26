# 🐳 Guía Completa de Docker y Kubernetes (k8s) para OWOMarket Multi-Tenant

Esta guía explica cómo ejecutar tu proyecto **OWOMarket** localmente con **Docker Compose** (con soporte automático de subdominios) y cómo desplegar la imagen en **Kubernetes (k8s)** en el futuro.

---

## 🛠️ Parte 1: Desarrollo Local con Docker Compose

### 1. Iniciar los Contenedores
Abre tu terminal en la raíz del proyecto y ejecuta:

```bash
docker-compose up -d --build
```

Esto iniciará 4 contenedores aislados:
- **`owomarket_web`** (Nginx en puerto `80` con soporte wildcard).
- **`owomarket_app`** (PHP 8.3 FPM con tu código sincronizado al instante).
- **`owomarket_db`** (MySQL 8.0 en puerto `3306`).
- **`owomarket_redis`** (Redis en puerto `6379`).

---

### 2. Probar Subdominios Multi-Tenant en Local

#### Opción A: Usar `.localhost` (Sin editar el archivo `hosts` de Windows) ✨
Los navegadores modernos apuntan automáticamente `*.localhost` a tu máquina `127.0.0.1`.

Puedes ingresar directamente en tu navegador a:
- Dominio Central: `http://localhost` o `http://app.localhost`
- Tienda Tenant 1: `http://tienda1.localhost`
- Tienda Tenant 2: `http://tienda2.localhost`

#### Opción B: Usar `.owomarket.local`
Si prefieres usar `.local`, edita como Administrador `C:\Windows\System32\drivers\etc\hosts`:

```text
127.0.0.1   owomarket.local
127.0.0.1   tienda1.owomarket.local
127.0.0.1   tienda2.owomarket.local
```

---

### 3. Comandos Útiles de Desarrollo

```bash
# Ver estado de los contenedores
docker-compose ps

# Ver logs en vivo de Laravel / Nginx
docker-compose logs -f app

# Ejecutar comandos de Artisan dentro del contenedor
docker-compose exec app php artisan migrate
docker-compose exec app php artisan db:seed
docker-compose exec app php artisan test

# Detener los contenedores
docker-compose down
```

---

## ☸️ Parte 2: Construcción y Despliegue en Kubernetes (k8s)

Cuando llegue el momento de pasar tu aplicación a un clúster de Kubernetes en producción:

### 1. Construir y Subir la Imagen al Container Registry

```bash
# 1. Construir la imagen de producción
docker build -t tu-usuario/owomarket-backend:v1.0.0 .

# 2. Iniciar sesión en tu Registry (Docker Hub, GitHub Container Registry, AWS ECR)
docker login

# 3. Subir la imagen
docker push tu-usuario/owomarket-backend:v1.0.0
```

---

### 2. Desplegar en Kubernetes

```bash
# 1. Aplicar los manifiestos de Kubernetes
kubectl apply -f k8s/deployment.yaml
kubectl apply -f k8s/ingress.yaml

# 2. Verificar el estado de los Pods y Servicios
kubectl get pods -n owomarket
kubectl get services -n owomarket
kubectl get ingress -n owomarket

# 3. Escalado Horizontal (Ejemplo: Escalar a 5 replicas automáticamente)
kubectl scale deployment owomarket-backend --replicas=5 -n owomarket
```

---

## 📂 Archivos Creados en el Proyecto

- [Dockerfile](file:///c:/laragon/www/owomarket/Dockerfile) -> Construcción multietapa (Dev / Prod).
- [docker-compose.yml](file:///c:/laragon/www/owomarket/docker-compose.yml) -> Entorno local con Nginx, PHP, MySQL y Redis.
- [docker/nginx/default.conf](file:///c:/laragon/www/owomarket/docker/nginx/default.conf) -> Configuración de subdominios wildcard en Nginx.
- [k8s/deployment.yaml](file:///c:/laragon/www/owomarket/k8s/deployment.yaml) -> Deployment, Service, ConfigMap y Secret de Kubernetes.
- [k8s/ingress.yaml](file:///c:/laragon/www/owomarket/k8s/ingress.yaml) -> Ingress Controller con soporte de subdominios wildcard y SSL para Kubernetes.
