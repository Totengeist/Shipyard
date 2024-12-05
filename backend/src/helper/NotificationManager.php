<?php

namespace Shipyard;

class NotificationManager {
    /**
     * A globalized notifier.
     *
     * @var NotificationManager
     */
    private static $global_notifier;
    /**
     * The notifier channels.
     *
     * @var Notifier[]
     */
    private $notifiers = [];
    /**
     * Enables or disables notifications.
     *
     * @var bool
     */
    private $enabled = true;

    /**
     * @return void
     */
    public function setAsGlobal() {
        self::$global_notifier = $this;
    }

    /**
     * @return NotificationManager
     */
    public static function get() {
        return self::$global_notifier;
    }

    /**
     * Add a notifier channel.
     *
     * Using a channel name as an identifier allows picking and choosing different notifiers of the same type, but with different configurations.
     *
     * @param string   $name     the name of the channel for identification
     * @param Notifier $notifier the Notifier instance to use for notifications to this channel
     *
     * @return void
     */
    public function add_channel($name, $notifier) {
        $this->notifiers[$name] = $notifier;
    }

    /**
     * Add a notifier channel.
     *
     * Using a channel name as an identifier allows picking and choosing different notifiers of the same type, but with different configurations.
     *
     * @param string $name the name of the channel for identification
     *
     * @return Notifier|null
     */
    public function channel($name) {
        if (isset($this->notifiers[$name])) {
            return $this->notifiers[$name];
        }

        return null;
    }

    /**
     * Send a notification.
     *
     * While not all notifiers may have use for a subject, it is used for logging.
     *
     * @param string $message the message of the notification
     * @param string $subject an optional subject for the notification
     *
     * @return bool
     */
    public static function send($message, $subject) {
        if (!self::$global_notifier->enabled) {
            return false;
        }
        foreach (self::$global_notifier->notifiers as $channel => $notifier) {
            $result = $notifier->send($message, $subject);
        }

        return true;
    }
}
