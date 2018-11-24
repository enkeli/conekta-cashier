<?php

namespace Enkeli\ConektaCashier;

use Conekta\Conekta;
use Conekta\Event;
use Exception;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class WebhookController extends Controller
{
    /**
     * Handle a Conekta webhook call.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handleWebhook()
    {
        $payload = $this->getJsonPayload();

        if (!$this->eventExistsOnConekta($payload['id'])) {
            return $this->missingConektaEvent($payload);
        }

        $eventClass = studly_case(str_replace('.', '_', $payload['type']));
        $eventClass = "\\Enkeli\\ConektaCashier\\Events\\$eventClass";

        // Fire BeforeAll event.
        event(new Events\BeforeAll($payload));

        // Fire event if exists, otherwise fire Missing
        if (class_exists($eventClass)) {
            event(new $eventClass($payload));
        } else {
            event(new Events\Missing($payload));
        }

        // Fire AfterAll event.
        event(new Events\AfterAll($payload));

        return $this->eventDispatched();
    }

    /**
     * Verify with Stripe that the event is genuine.
     *
     * @param string $id
     *
     * @return bool
     */
    protected function eventExistsOnConekta($id)
    {
        try {
            Conekta::setApiKey(Config::get('services.conekta.secret'));

            return !is_null(Event::where(['id' => $id]));
        } catch (Throwable $e) {
            return false;
        }
    }

    /**
     * Get the billable entity instance by Conekta ID.
     *
     * @param string $conektaId
     *
     * @return \Dinkbit\ConektaCashier\BillableInterface
     */
    protected function getBillable($conektaId)
    {
        return App::make('Enkeli\ConektaCashier\BillableRepositoryInterface')->find($conektaId);
    }

    /**
     * Get the billable entity instance by Payload object.
     *
     * @param array $payload
     *
     * @return \Dinkbit\ConektaCashier\BillableInterface
     */
    protected function getBillableFromPayload($payload)
    {
        return $this->getBillable($payload['data']['object']['customer_info']['customer_id']);
    }

    /**
     * Get the JSON payload for the request.
     *
     * @return array
     */
    protected function getJsonPayload()
    {
        return (array) json_decode(Request::getContent(), true);
    }

    /**
     * Returns a succesful response.
     *
     * @return mixed
     */
    public function eventDispatched()
    {
        return new Response("", 200);
    }

    /**
     * Handle calls to missing Conekta's events.
     *
     * @param array $parameters
     *
     * @return mixed
     */
    public function missingConektaEvent($parameters = [])
    {
        return new Response("", 404);
    }
}
