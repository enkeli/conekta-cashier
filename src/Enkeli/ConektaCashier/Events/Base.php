<?php

namespace Enkeli\ConektaCashier\Events;

use Illuminate\Queue\SerializesModels;

/**
 * The base from wich all events extends
 */
abstract class Base
{
    use SerializesModels;

    /** @var array $payload The payload sent to the webhook */
    public $payload = [];

    /** @var array $object The data object */
    public $object = null;

    /** @var string $object_id The data object id */
    public $object_id = null;

    /**
     * Create a new event instance.
     *
     * @param  array  $payload Conekta's payload
     * @return void
     */
    public function __construct($payload)
    {
        $this->payload = $payload;

        if (isset($payload['data']) && isset($payload['data']['object'])) {
            $this->object = $payload['data']['object'];
        }

        if ($this->object && isset($this->object['id'])) {
            $this->object_id = $this->object['id'];
        }
    }
}