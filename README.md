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

- `php artisan queues:list`: List declared queues and their message counts.
- `php artisan queues:create`: Declare all configured queues and exchanges.
- `php artisan queues:delete`: Delete all configured queues.

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

### Quick Start with Persistent Workers Script

For development or simple production setups, you can use the provided `start-workers.sh` script that runs workers persistently without time limits:

```bash
# Make the script executable
chmod +x start-workers.sh

# Start all persistent workers
./start-workers.sh

# Check worker status
./start-workers.sh check

# Stop all workers
./start-workers.sh stop

# Restart all workers
./start-workers.sh restart

# View worker logs (real-time monitoring)
tail -f storage/logs/worker-*.log

# View specific queue logs
tail -f storage/logs/worker-mail.log        # Mail queue
tail -f storage/logs/worker-collect-ads.log # Ad collection
tail -f storage/logs/worker-default.log     # Default queue
```

**Key features of the persistent script:**

- ❌ **No `--max-time` limit** - workers run indefinitely
- ✅ **Memory management** - 512MB limit per worker with `--memory=512`
- ✅ **Daemon mode** - workers run in background with `--daemon`
- ✅ **PID tracking** - saves process IDs for management
- ✅ **Optimized settings** - different timeouts for different queue types
- ✅ **Management functions** - start/stop/check/restart capabilities
- 🔒 **Duplicate protection** - prevents starting workers when already running
- 🔒 **Process validation** - checks worker health before operations
- 🔧 **Graceful restart** - proper stop/start sequence with safety delays

**Worker configuration:**

- **Default queue**: 2 workers, 300s timeout
- **Collect-ads queue**: 2 workers, 600s timeout (heavy processing)
- **Push-house-ads queue**: 1 worker, 600s timeout
- **Mail queue**: 2 workers, 120s timeout (high priority)
- **Delayed queue**: 1 worker, 300s timeout
- **Website-downloads queue**: 1 worker, 300s timeout

**Safety Features and Error Prevention:**

The script includes several safety mechanisms to prevent common issues:

```bash
# If workers are already running, the script will warn and exit
./start-workers.sh start
# Output: WARNING: 9 workers are already running!
#         Use './start-workers.sh stop' first or './start-workers.sh restart'

# Safe restart with automatic stop/start sequence
./start-workers.sh restart
# Stops all workers → waits 2 seconds → starts fresh workers

# Health check shows detailed worker status
./start-workers.sh check
# Output: Worker PID 12345 is running
#         Worker PID 12346 is running
#         ... (shows all 9 workers)
```

**Performance Monitoring:**

Monitor worker performance and resource usage:

```bash
# Check worker resource usage
ps aux | grep "queue:work" | grep -v grep

# Monitor memory usage by workers
ps -eo pid,ppid,cmd,%mem,%cpu --sort=-%mem | grep "queue:work"

# Check worker count and status
./start-workers.sh check | wc -l  # Should show 9 workers

# Monitor logs for errors or performance issues
grep -i "error\|exception\|timeout" storage/logs/worker-*.log
```

**Troubleshooting:**

If you encounter duplicate workers or processes, use these commands:

```bash
# Check system-wide queue workers
ps aux | grep "queue:work" | grep -v grep

# Count active workers (should be 9-10 including cursor process)
ps aux | grep "queue:work" | grep -v grep | wc -l

# Force kill all queue workers (if needed)
pkill -f "queue:work"

# Clean start after force kill
./start-workers.sh start
```

**When to Use Persistent Script vs Supervisor:**

| Feature                | Persistent Script           | Supervisor                      |
| ---------------------- | --------------------------- | ------------------------------- |
| **Setup Complexity**   | ✅ Simple (just run script) | ⚠️ Requires configuration files |
| **Automatic Restart**  | ❌ Manual restart needed    | ✅ Automatic on crash/reboot    |
| **Process Monitoring** | ✅ Basic (check command)    | ✅ Advanced (web interface)     |
| **Log Management**     | ✅ Basic (file rotation)    | ✅ Advanced (structured logs)   |
| **Development Use**    | ✅ Perfect for dev/testing  | ⚠️ Overkill for development     |
| **Production Use**     | ⚠️ Good for simple setups   | ✅ Recommended for production   |
| **System Integration** | ❌ No systemd integration   | ✅ Full system service          |
| **Memory Monitoring**  | ✅ Basic (--memory limit)   | ✅ Advanced monitoring          |

**Recommendation:**

- Use **Persistent Script** for development, testing, and simple production deployments
- Use **Supervisor** for serious production environments with high availability requirements

### Production Queue Management with Supervisor

For production environments, use **Supervisor** to manage queue workers as background processes that run continuously without stopping.

#### Installing Supervisor

**Ubuntu/Debian:**

```bash
sudo apt-get update
sudo apt-get install supervisor
```

#### Supervisor Configuration

Create configuration files for queue workers:

**1. Main Queue Worker (`/etc/supervisor/conf.d/spyhouse-queue-worker.conf`):**

```ini
[program:spyhouse-queue-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/your/project/artisan queue:work rabbitmq --queue=default,collect-ads,push-house-ads,delayed,mail,website-downloads --tries=3 --timeout=300 --sleep=3
directory=/path/to/your/project
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/log/supervisor/spyhouse-queue-worker.log
stdout_logfile_maxbytes=10MB
stdout_logfile_backups=5
stopwaitsecs=30
```

**2. High Priority Mail Queue (`/etc/supervisor/conf.d/spyhouse-mail-queue.conf`):**

```ini
[program:spyhouse-mail-queue]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/your/project/artisan queue:work rabbitmq --queue=mail --tries=3 --timeout=120 --sleep=1
directory=/path/to/your/project
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/var/log/supervisor/spyhouse-mail-queue.log
stdout_logfile_maxbytes=10MB
stdout_logfile_backups=5
stopwaitsecs=30
```

**3. Ad Collection Queue (`/etc/supervisor/conf.d/spyhouse-ads-queue.conf`):**

```ini
[program:spyhouse-ads-queue]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/your/project/artisan queue:work rabbitmq --queue=collect-ads,push-house-ads --tries=5 --timeout=600 --sleep=5
directory=/path/to/your/project
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/var/log/supervisor/spyhouse-ads-queue.log
stdout_logfile_maxbytes=10MB
stdout_logfile_backups=5
stopwaitsecs=30
```

#### Important Configuration Notes:

- **NO `--max-jobs` parameter**: Workers will run indefinitely without stopping
- **`autostart=true`**: Workers start automatically when supervisor starts
- **`autorestart=true`**: Workers restart automatically if they crash
- **`numprocs`**: Number of worker processes to run (adjust based on your server capacity)
- **`user=www-data`**: Run workers under web server user (adjust if different)
- **`timeout`**: Longer timeouts for heavy processing queues (ads collection)
- **Path replacement**: Replace `/path/to/your/project` with your actual project path

#### Supervisor Management Commands:

```bash
# Reload supervisor configuration
sudo supervisorctl reread
sudo supervisorctl update

# Start all queue workers
sudo supervisorctl start spyhouse-queue-worker:*
sudo supervisorctl start spyhouse-mail-queue:*
sudo supervisorctl start spyhouse-ads-queue:*

# Stop all queue workers
sudo supervisorctl stop spyhouse-queue-worker:*
sudo supervisorctl stop spyhouse-mail-queue:*
sudo supervisorctl stop spyhouse-ads-queue:*

# Restart all queue workers
sudo supervisorctl restart spyhouse-queue-worker:*
sudo supervisorctl restart spyhouse-mail-queue:*
sudo supervisorctl restart spyhouse-ads-queue:*

# Check status of all workers
sudo supervisorctl status

# View real-time logs
sudo supervisorctl tail -f spyhouse-queue-worker stdout
sudo supervisorctl tail -f spyhouse-mail-queue stdout
sudo supervisorctl tail -f spyhouse-ads-queue stdout
```

#### Monitoring and Logs:

- **Log files**: Located in `/var/log/supervisor/`
- **Log rotation**: Automatic (10MB max, 5 backups)
- **Real-time monitoring**: Use `supervisorctl status` and `tail` commands

#### Supervisor Service Management:

```bash
# Enable supervisor to start on boot
sudo systemctl enable supervisor

# Start supervisor service
sudo systemctl start supervisor

# Check supervisor service status
sudo systemctl status supervisor

# Restart supervisor service
sudo systemctl restart supervisor
```

#### Best Practices for Production:

1. **Monitor Memory Usage**: Queue workers can accumulate memory over time
2. **Regular Restarts**: Consider periodic restarts via cron (optional)
3. **Log Monitoring**: Set up log rotation and monitoring alerts
4. **Resource Allocation**: Adjust `numprocs` based on server capacity
5. **Queue Separation**: Use dedicated workers for different queue types

#### Optional: Memory Management

If workers accumulate too much memory, add periodic restarts via cron:

```bash
# Add to crontab (sudo crontab -e)
# Restart queue workers every 6 hours to prevent memory leaks
0 */6 * * * /usr/bin/supervisorctl restart spyhouse-queue-worker:* spyhouse-mail-queue:* spyhouse-ads-queue:*
```

## Advertisement Networks Synchronization

The application includes a system for synchronizing advertisement networks from external FeedHouse API. This system detects new networks and logs them for administrator review.

### Commands

The following commands are available for managing advertisement networks synchronization:

```bash
# Check available advertisement networks commands
php artisan list | grep advertisment

# Run synchronization manually (synchronous execution)
php artisan advertisment-networks:sync

# Run synchronization through background queue (recommended for production)
php artisan advertisment-networks:sync --queue

# Check scheduled tasks including weekly synchronization
php artisan schedule:list
```

### Automated Synchronization

The system is configured to automatically synchronize advertisement networks:

- **Frequency**: Every Sunday at 00:00 UTC
- **Method**: Background queue processing
- **Behavior**: Only logs new networks (does not auto-create database records)

### How It Works

1. **Fetches Data**: Connects to FeedHouse API to retrieve current network list
2. **Compares**: Checks against existing networks in the database
3. **Logs New Networks**: Records information about newly detected networks in application logs
4. **Admin Notification**: Logs require administrator review and manual approval

### Log Messages

When new networks are detected, the system generates:

- **INFO**: "NEW ADVERTISEMENT NETWORKS DETECTED! Administrator notification required."
- **WARNING**: "ADMIN ACTION REQUIRED: New advertisement networks need manual review and approval."

These logs include detailed information about detected networks (names, codes, metadata) for administrator review.

## Notification System

The application includes a comprehensive notification system that provides a unified interface for creating and sending notifications through different channels (email, database, etc.). This system integrates with Laravel's native notification system while adding additional features.

### Core Components

1. **NotificationType Enum** (`app/Enums/Frontend/NotificationType.php`):

   - Defines all possible notification types in the system
   - Each notification type has a unique string value
   - Used for type-safety and standardization

2. **NotificationType Model** (`app/Models/NotificationType.php`):

   - Database representation of notification types
   - Stores metadata for each notification type (name, description, default channels, etc.)
   - Configurable per notification type (user-configurable flag, default channels)

3. **BaseNotification Class** (`app/Notifications/BaseNotification.php`):

   - Abstract base class for all notifications
   - Handles channel resolution, data formatting, and type information
   - Provides default implementations that can be overridden

4. **HasNotificationType Trait** (`app/Traits/HasNotificationType.php`):

   - Associates notifications with their types
   - Provides utility methods for working with notification types
   - Manages channel resolution based on user preferences

5. **NotificationDispatcher Service** (`app/Services/Notification/NotificationDispatcher.php`):
   - Utility service for sending notifications
   - Provides methods for different notification scenarios
   - Simplifies the notification creation and sending process

### Using the Notification System

#### Creating a New Notification

1. Create a new notification class that extends `BaseNotification`:

```php
<?php

namespace App\Notifications;

use App\Enums\Frontend\NotificationType;
use App\Notifications\BaseNotification;
use Illuminate\Notifications\Messages\MailMessage;

class YourNewNotification extends BaseNotification
{
    private string $someData;

    public function __construct(string $someData)
    {
        parent::__construct(NotificationType::YOUR_NOTIFICATION_TYPE);
        $this->someData = $someData;
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->getTitle($notifiable))
            ->line($this->getMessage($notifiable))
            ->line('Additional information: ' . $this->someData);
    }

    protected function getTitle(object $notifiable): string
    {
        return 'Your Custom Title';
    }

    protected function getMessage(object $notifiable): string
    {
        return 'Your custom notification message.';
    }

    protected function getIcon(): string
    {
        return 'custom-icon'; // Icon identifier for frontend
    }

    protected function getAdditionalData(object $notifiable): array
    {
        return [
            'some_data' => $this->someData,
            'additional_info' => 'Any other data for the notification',
        ];
    }
}
```

2. Add your notification type to the enum if it doesn't exist:

```php
// In App\Enums\Frontend\NotificationType
enum NotificationType: string
{
    // ...existing types...
    case YOUR_NOTIFICATION_TYPE = 'your_notification_type';
}
```

3. Add your notification type to the database seeder (`database/seeders/NotificationTypesSeeder.php`) to ensure it's properly configured.

#### Sending Notifications

##### 1. Standard Method (creating the notification instance first):

```php
use App\Notifications\YourNewNotification;

$notification = new YourNewNotification('Some data');
$user->notify($notification);
```

##### 2. Using the Dispatcher (more concise):

```php
use App\Services\Notification\NotificationDispatcher;
use App\Notifications\YourNewNotification;

NotificationDispatcher::sendNotification(
    $user,
    YourNewNotification::class,
    ['Some data']
);
```

##### 3. Quick Notifications (without creating a separate class):

```php
use App\Services\Notification\NotificationDispatcher;
use App\Enums\Frontend\NotificationType;

NotificationDispatcher::quickSend(
    $user,
    NotificationType::YOUR_NOTIFICATION_TYPE,
    ['some_data' => 'value'],
    'Custom Title',
    'Custom Message'
);
```

##### 4. Sending to Non-User Recipients:

```php
use App\Services\Notification\NotificationDispatcher;
use App\Notifications\YourNewNotification;

// Send to a specific email
NotificationDispatcher::sendTo(
    'mail',
    'email@example.com',
    new YourNewNotification('Some data')
);

// Send to multiple emails
NotificationDispatcher::sendToEmails(
    ['email1@example.com', 'email2@example.com'],
    new YourNewNotification('Some data')
);
```

### How Notifications are Displayed

1. **Database Notifications**:

   - Stored in the `notifications` table
   - Accessible via `$user->notifications` relation
   - Displayed in the user's notification center (`/notifications` route)

2. **Email Notifications**:
   - Sent via the mail channel
   - Use Laravel's mail templates for consistent appearance
   - Configurable per notification type

### User Preferences for Notifications

Users can configure their notification preferences:

1. **Enable/Disable Notifications**:

   ```php
   $user->setNotificationEnabled(NotificationType::YOUR_NOTIFICATION_TYPE, true|false);
   ```

2. **Configure Notification Channels**:

   ```php
   $user->setNotificationChannels(NotificationType::YOUR_NOTIFICATION_TYPE, ['mail', 'database']);
   ```

3. **Check Notification Status**:
   ```php
   $user->hasNotificationEnabled(NotificationType::YOUR_NOTIFICATION_TYPE);
   $user->hasNotificationChannelEnabled(NotificationType::YOUR_NOTIFICATION_TYPE, 'mail');
   ```

### Extending the Notification System

To add new notification channels:

1. Add the channel name to the default channels array in the `NotificationType` model
2. Implement the appropriate method in your notification class (e.g., `toSms()`)
3. Update the channel resolution logic in the `HasNotificationType` trait if needed

### Best Practices

1. **Use Enum Types**: Always use the enum for type safety, not string literals
2. **Extend Base Class**: All notifications should extend `BaseNotification`
3. **Provide Descriptive Data**: Include relevant information in `getAdditionalData()`
4. **Respect User Preferences**: Always use `resolveChannels()` to determine delivery channels
5. **Use Translation Keys**: For titles and messages, use translation keys instead of hardcoded strings
6. **Use Dispatcher Service**: Prefer `NotificationDispatcher` over direct calls when possible
