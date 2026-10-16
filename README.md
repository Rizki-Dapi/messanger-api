# Messanger API

![Version](https://img.shields.io/badge/version-1.0.0-blue)
![Laravel](https://img.shields.io/badge/Laravel-13-red)
![PHP](https://img.shields.io/badge/PHP-8.3-777bb4)

A backend API for a messaging platform built with Laravel 13. This project focuses on backend architecture, authentication, messaging, real-time communication, notifications, and reliable business logic.

## Tech Stack

- **Framework:** Laravel 13
- **Primary Database:** PostgreSQL
- **Logging Database:** MongoDB for system errors and business activity logs
- **Cache:** Redis
- **Queue:** Laravel Queue
- **Real-Time Communication:** Laravel Reverb
- **Authentication:** JWT (`tymon/jwt-auth`)
- **Password Hashing:** Argon2id
- **Testing:** Pest
- **CI/CD:** GitHub Actions

## Architecture

This project follows a layered architecture to keep controllers thin and separate business logic from database operations.

```text
Route
  → Middleware
  → Controller
  → Request Validation
  → Service
  → Repository Interface
  → Repository Implementation
  → Model / Database
  → Resource / Response
```

- **Repository + Interface** — separates database queries from business logic and allows services to depend on abstractions.
- **Service** — handles business rules and coordinates repositories, events, and other services.
- **Custom Exceptions** — handles application-specific errors with appropriate HTTP status codes.
- **Events and Listeners** — separates event dispatching from follow-up actions such as notifications.
- **API Resources** — standardizes the structure of API responses.
- **MongoDB Logging** — stores system errors and relevant business activity logs separately from the primary database.

## Key Features

### Authentication

- JWT-based authentication.
- Token refresh.
- Email verification.
- Password reset.
- Trusted-device handling.

### Messaging

- Send and retrieve chats.
- Conversation management.
- Unread message counts.
- Mark messages as read.
- Chat event dispatching.

### Stories

- Story management.
- Story expiration handling.
- Redis caching for unexpired stories.

### Real-Time Communication and Notifications

- Laravel Reverb for WebSocket broadcasting.
- Events for chat-related activity.
- Notification handling through Laravel's event and notification mechanisms.

### Logging and Security

- Structured system error logging with MongoDB.
- Business activity logging.
- Password hashing using Argon2id.
- Generic error responses to avoid exposing internal exception details.

## Prerequisites

- PHP 8.3+
- Composer
- PostgreSQL
- MongoDB
- Redis
- Node.js and npm only if needed by your Reverb or frontend development workflow
- WSL Ubuntu on Windows, if applicable

## Installation

Clone the repository:

```bash
git clone <your-repository-url>
cd Messanger-api
```

Install dependencies:

```bash
composer install
```

Configure the environment:

```bash
cp .env.example .env
php artisan key:generate
php artisan jwt:secret
```

Configure your `.env` file with the appropriate database and service credentials:

```env
APP_ENV=local

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=messanger_api
DB_USERNAME=your_pg_user
DB_PASSWORD=your_pg_password

MONGODB_URI=mongodb://127.0.0.1:27017
MONGODB_DATABASE=messanger_api_logs

CACHE_STORE=redis
QUEUE_CONNECTION=database

MAIL_MAILER=smtp
MAIL_HOST=sandbox.smtp.mailtrap.io
MAIL_USERNAME=your_mailtrap_username
MAIL_PASSWORD=your_mailtrap_password
```

Configure your Reverb environment variables according to the application's broadcasting configuration. Do not commit real credentials to version control.

Run database migrations:

```bash
php artisan migrate
```

## Running the Application

Start the required services: PostgreSQL, MongoDB, and Redis.

Run the Laravel HTTP server:

```bash
php artisan serve
```

Run the queue worker if your application uses queued jobs, notifications, or broadcast events:

```bash
php artisan queue:work
```

Run the Reverb server for real-time WebSocket communication:

```bash
php artisan reverb:start
```

Keep the required processes running while developing and testing their corresponding features.

## API Documentation

See the Postman collection:

[Open API Documentation](https://documenter.getpostman.com/view/33217412/2sBYHSwfEp)

## Testing

This project uses **Pest** for automated testing.

Run the test suite locally:

```bash
php artisan test
```

Run tests with additional output:

```bash
php artisan test --compact
```

The test suite covers application behavior such as authentication, messaging, conversation access, unread message counts, read status, and relevant event dispatching, depending on the implemented test cases.

### Continuous Integration

GitHub Actions runs automated checks on pushes and pull requests targeting `main`.

The workflow includes:

- Setting up PHP 8.3.
- Installing Composer dependencies.
- Preparing the Laravel test environment.
- Running database migrations against PostgreSQL.
- Running Pest tests with PostgreSQL and MongoDB services.
- Checking code style using Laravel Pint.

Reverb does not need to run in CI when tests verify event dispatching through Laravel's testing utilities. Actual WebSocket connectivity requires a separate integration test with a running Reverb server.

## Version History

- **1.0.0** — Initial project release with JWT authentication, messaging and conversation features, story handling, event-driven functionality, notifications, MongoDB logging, Redis caching, and automated testing infrastructure.
