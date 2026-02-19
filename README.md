# 🔗 Linkly — URL Shortener

Полнофункциональный сервис сокращения ссылок с аналитикой, написанный на PHP 8.2 + React 19.

![PHP Version](https://img.shields.io/badge/PHP-8.2%2B-777BB4?style=flat-square&logo=php)
![React](https://img.shields.io/badge/React-19-61DAFB?style=flat-square&logo=react)
![Docker](https://img.shields.io/badge/Docker-Ready-2496ED?style=flat-square&logo=docker)
![License](https://img.shields.io/badge/License-MIT-green?style=flat-square)

---

## ✨ Возможности

- 🚀 **Создание коротких URL** — автоматическая генерация или кастомный alias
- 📊 **Аналитика кликов** — геолокация, устройство, браузер, ОС
- 📱 **QR-коды** — генерация QR для любой ссылки
- 👤 **Авторизация** — регистрация и личный кабинет
- ⏱️ **TTL** — опциональный срок жизни ссылок
- 🛡️ **Безопасность** — CSRF, Rate Limiting, валидация

---

## 🛠 Технологии

| Backend | Frontend | Infrastructure |
|---------|----------|----------------|
| PHP 8.2 | React 19 | Docker Compose |
| MySQL 8.0 | TypeScript | Nginx |
| Redis | Vite | PHP-FPM |
| PDO | Zustand | |
| REST API | React Router | |

---

## 📦 Установка

### Требования

- Docker & Docker Compose
- Git

### Быстрый старт

```bash
# Клонирование
git clone https://github.com/USERNAME/linkly.git
cd linkly

# Настройка окружения
cp .env.example .env
# Отредактируйте .env под свои нужды

# Запуск
docker-compose up -d --build

# Установка зависимостей PHP
docker-compose exec php composer install

# Установка зависимостей Frontend
cd Frontend && npm install && npm run build && cd ..

# Готово! Откройте http://localhost
```

---

## 🚀 Команды

| Команда | Описание |
|---------|----------|
| `dev.bat` | Запуск в режиме разработки |
| `build.bat` | Сборка контейнеров |
| `start.bat` | Запуск продакшн |
| `stop.bat` | Остановка контейнеров |
| `test.bat` | Запуск тестов |

---

## 📁 Структура проекта

```
├── src/
│   ├── Controller/     # HTTP обработчики
│   ├── Service/        # Бизнес-логика
│   ├── Repository/     # Доступ к данным
│   ├── Model/          # Доменные модели
│   ├── DTO/            # Data Transfer Objects
│   ├── Middleware/     # Auth, CORS, CSRF, Rate Limit
│   └── Utils/          # Утилиты
├── Frontend/
│   ├── pages/          # Страницы React
│   ├── components/     # UI компоненты
│   ├── services/       # API клиент
│   └── store/          # Zustand store
├── migrations/         # SQL миграции
├── docker/             # Docker конфигурация
└── public/             # Document root
```

---

## 🔌 API Endpoints

### Публичные

| Method | Endpoint | Описание |
|--------|----------|----------|
| `POST` | `/api/auth/register` | Регистрация |
| `POST` | `/api/auth/login` | Авторизация |
| `POST` | `/api/links` | Создать короткую ссылку |
| `GET` | `/{shortCode}` | Редирект на оригинальный URL |
| `GET` | `/api/links/{shortCode}/qr` | Получить QR-код |

### Требующие авторизации

| Method | Endpoint | Описание |
|--------|----------|----------|
| `GET` | `/api/user/links` | Список ссылок пользователя |
| `DELETE` | `/api/links/{id}` | Удалить ссылку |
| `GET` | `/api/links/{shortCode}/stats` | Статистика кликов |

---

## ⚙️ Конфигурация

Основные переменные `.env`:

```env
# Database
DB_HOST=mysql
DB_NAME=linkly
DB_USER=linkly
DB_PASSWORD=your_password

# Redis
REDIS_HOST=redis

# App
APP_URL=http://localhost
APP_ENV=production

# Rate Limiting
RATE_LIMIT_REQUESTS=100
RATE_LIMIT_WINDOW=3600
```

---

## 🧪 Тесты

```bash
# Unit тесты
composer test

# PHPStan
composer stan

# Code style
composer cs-fix
```

---

## 📄 Лицензия

MIT License — используйте свободно.

---

## 👤 Автор

Создано как пет-проект для демонстрации full-stack разработки на PHP + React.
