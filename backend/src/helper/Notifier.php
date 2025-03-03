<?php

namespace Shipyard;

abstract class Notifier {
    /**
     * Enables or disables notifications.
     *
     * @var bool
     */
    protected $enabled = true;

    /**
     * Enable notifications from this notifier.
     *
     * @return void
     */
    public function enable() {
        $this->enabled = true;
    }

    /**
     * Disable notifications from this notifier.
     *
     * @return void
     */
    public function disable() {
        $this->enabled = false;
    }

    /**
     * Send a notification.
     *
     * While not all notifiers may have use for a subject, it is expected by the NotificationManager for logging purposes.
     *
     * @param string $message the message of the notification
     * @param string $subject an optional subject for the notification
     *
     * @return bool
     */
    abstract public function send($message, $subject);
}
