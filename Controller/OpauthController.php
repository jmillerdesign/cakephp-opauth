<?php
App::uses('Router', 'Routing');
class OpauthController extends OpauthAppController {

	public function __construct($request = null, $response = null) {
		parent::__construct($request, $response);
		$this->modelClass = null;
	}

	public function beforeFilter() {
		// Allow access to Opauth methods for users of AuthComponent
		if (is_object($this->Auth) && method_exists($this->Auth, 'allow')) {
			$this->Auth->allow();
		}

		//Disable Security for the plugin actions in case that Security Component is active
		if (is_object($this->Security)) {
			$this->Security->validatePost = false;
			$this->Security->csrfCheck = false;
		}
	}

/**
 * Callback for handling Opauth completion
 *
 * @return void Redirects to the Strategy redirect URL
 */
	public function opauth_complete() {
		if (array_key_exists('auth', $this->data)) {
			$strategy = $this->data['auth']['provider'];
		} else if (array_key_exists('error', $this->data)) {
			$strategy = $this->data['error']['provider'];
		}

		if (!empty($this->data['validated'])) {
			// Save response in the Session
			$this->Session->write($strategy, $this->data);

			// Dispatch CakeEvent
			$event = new CakeEvent('Opauth.validated', $this, $this->data);
			$this->getEventManager()->dispatch($event);
			if ($event->isStopped()) {
				return;
			}
		}

		// Redirect to the Strategy redirect URL
		$redirect = Configure::read(sprintf(
			'Opauth.Strategy.%s.redirect',
			$strategy
		));

		// Dispatch CakeEvent
		$event = new CakeEvent('Opauth.complete', $this, $this->data);
		$this->getEventManager()->dispatch($event);
		if ($event->isStopped()) {
			return;
		}

		if (!$redirect) {
			if (is_object($this->Auth)) {
				$redirect = $this->Auth->loginAction;
			} else {
				$redirect = '/';
			}
		}

		$cakeRequest = new CakeRequest(Router::url($redirect));
		$cakeRequest->data = $this->data;

		$dispatcher = new Dispatcher();
		$dispatcher->dispatch($cakeRequest, new CakeResponse());
	}

}
