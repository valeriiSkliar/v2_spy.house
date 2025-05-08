

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

### Working with Queues Locally

To process jobs from queues locally, use the following command:

```bash
php artisan queue:work rabbitmq --queue=default,collect-ads,push-house-ads,delayed,mail,website-downloads
```

This command will start a worker that processes jobs from all configured queues. You can also specify individual queues:

```bash
# Process only mail queue
php artisan queue:work rabbitmq --queue=mail

# Process only collect-ads queue
php artisan queue:work rabbitmq --queue=collect-ads
```

Additional queue worker options:
- `--tries=3`: Number of times to attempt a job before marking it as failed
- `--timeout=60`: Number of seconds a job can run before timing out
- `--sleep=3`: Number of seconds to wait when no job is available
- `--max-jobs=1000`: Number of jobs to process before stopping

Example with options:
```bash
php artisan queue:work rabbitmq --queue=mail --tries=3 --timeout=60 --sleep=3 --max-jobs=1000
```

To monitor queue status and messages, access the RabbitMQ management interface at http://localhost:15672 (default credentials: guest/guest)

