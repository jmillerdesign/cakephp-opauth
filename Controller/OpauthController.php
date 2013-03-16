<?php
class OpauthController extends OpauthAppController {

	public function beforeFilter() {
		// Allow access to Opauth methods for users of AuthComponent
		if (is_object($this->Auth) && method_exists($this->Auth, 'allow')) {
			$this->Auth->allow();
		}
	}

/**
 * Callback for handling Opauth completion
 *
 * @return void Redirects to the Strategy redirect URL
 */
	public function opauth_complete() {
		if (!empty($this->data['validated'])) {
			// Save response in the Session
			$strategy = $this->data['auth']['provider'];
			$this->Session->write($strategy, $this->data);

			// Dispatch CakeEvent
			$event = new CakeEvent('Opauth.validated', $this, $this->data);
			$this->getEventManager()->dispatch($event);
			if ($event->isStopped()) {
				return;
			}

			// Redirect to the Strategy redirect URL
			$redirect = Configure::read(sprintf(
				'Opauth.Strategy.%s.redirect',
				$strategy
			));
		}

		// Dispatch CakeEvent
		$event = new CakeEvent('Opauth.complete', $this, $this->data);
		$this->getEventManager()->dispatch($event);
		if ($event->isStopped()) {
			return;
		}

		if (empty($redirect)) {
			if (is_object($this->Auth)) {
				$redirect = $this->Auth->loginAction;
			} else {
				$redirect = '/';
			}
		}
		$this->redirect($redirect);
	}

}
