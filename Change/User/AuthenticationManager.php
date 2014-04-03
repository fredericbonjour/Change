<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Change\User;

/**
* @name \Change\User\AuthenticationManager
*/
class AuthenticationManager implements \Zend\EventManager\EventsCapableInterface
{
	use \Change\Events\EventsCapableTrait;

	const EVENT_MANAGER_IDENTIFIER = 'AuthenticationManager';

	const EVENT_LOGIN = 'login';

	const EVENT_LOGOUT = 'logout';

	const EVENT_BY_USER_ID = 'byUserId';

	/**
	 * @var UserInterface|null
	 */
	protected $currentUser;

	/**
	 * @param UserInterface $currentUser
	 */
	public function setCurrentUser($currentUser = null)
	{
		$this->currentUser = $currentUser;
	}

	/**
	 * @return UserInterface
	 */
	public function getCurrentUser()
	{
		return $this->currentUser === null ? new AnonymousUser() : $this->currentUser;
	}

	/**
	 * @return null|string|string[]
	 */
	protected function getEventManagerIdentifier()
	{
		return static::EVENT_MANAGER_IDENTIFIER;
	}

	/**
	 * @return string[]
	 */
	protected function getListenerAggregateClassNames()
	{
		return $this->getApplication()->getConfiguredListenerClassNames('Change/Events/AuthenticationManager');
	}

	/**
	 * @api
	 * @param integer $userId
	 * @return UserInterface|null
	 */
	public function getById($userId)
	{
		$em = $this->getEventManager();
		$args = $em->prepareArgs(array('userId' => $userId));
		$event = new \Change\Events\Event(static::EVENT_BY_USER_ID, $this, $args);
		$this->getEventManager()->trigger($event);
		$user = $event->getParam('user');
		if ($user instanceof UserInterface)
		{
			return $user;
		}
		return null;
	}

	/**
	 * @api
	 * @param string $login
	 * @param string $password
	 * @param string $realm
	 * @return UserInterface|null
	 */
	public function login($login, $password, $realm)
	{
		$em = $this->getEventManager();
		$args = $em->prepareArgs(array('login' => $login,
			'password' => $password, 'realm' => $realm));

		$event = new \Change\Events\Event(static::EVENT_LOGIN, $this, $args);
		$this->getEventManager()->trigger($event);
		$user = $event->getParam('user');
		if ($user instanceof UserInterface)
		{
			return $user;
		}
		return null;
	}

	/**
	 * @api
	 */
	public function logout()
	{
		$em = $this->getEventManager();
		$args = $em->prepareArgs(['user' => $this->getCurrentUser()]);
		$this->getEventManager()->trigger(static::EVENT_LOGOUT, $this, $args);
		$this->setCurrentUser(null);
	}
}