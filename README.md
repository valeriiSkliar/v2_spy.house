

## Local Development Setup

### Prerequisites

This project relies on RabbitMQ for background job processing. Ensure it is installed and running.

**Install RabbitMQ (Ubuntu/Debian):**

```bash
sudo apt-get update
sudo apt-get install -y rabbitmq-server
```

**Enable Management Plugin (Optional):**

Provides a web interface to monitor RabbitMQ (usually at http://localhost:15672/).

```bash
sudo rabbitmq-plugins enable rabbitmq_management
```

**Start and Check Status:**

```bash
sudo systemctl start rabbitmq-server
sudo systemctl status rabbitmq-server
```

### Queue Management Commands

The application provides commands to manage RabbitMQ queues defined in `config/queue.php`:

*   `php artisan queues:list`: List declared queues and their message counts.
*   `php artisan queues:create`: Declare all configured queues and exchanges.
*   `php artisan queues:delete`: Delete all configured queues.

