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
