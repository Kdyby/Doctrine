<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Kdyby\Doctrine;

use Kdyby;
use Nette;



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
final class Events
{

	/**
	 * The namespace for listeners on Doctrine events.
	 */
	const NS = 'Doctrine\\ORM\\Event';

	/**
	 * Is invoked right after entity is hydrated with at least it's toOne relations.
	 */
	const postLoadRelations = 'Doctrine\\ORM\\Event::postLoadRelations';

	/**
	 * The preRemove event occurs for a given entity before the respective
	 * EntityManager remove operation for that entity is executed.
	 *
	 * This is an entity lifecycle event.
	 *
	 * @var string
	 */
	const preRemove = 'Doctrine\\ORM\\Event::preRemove';

	/**
	 * The postRemove event occurs for an entity after the entity has
	 * been deleted. It will be invoked after the database delete operations.
	 *
	 * This is an entity lifecycle event.
	 *
	 * @var string
	 */
	const postRemove = 'Doctrine\\ORM\\Event::postRemove';

	/**
	 * The prePersist event occurs for a given entity before the respective
	 * EntityManager persist operation for that entity is executed.
	 *
	 * This is an entity lifecycle event.
	 *
	 * @var string
	 */
	const prePersist = 'Doctrine\\ORM\\Event::prePersist';

	/**
	 * The postPersist event occurs for an entity after the entity has
	 * been made persistent. It will be invoked after the database insert operations.
	 * Generated primary key values are available in the postPersist event.
	 *
	 * This is an entity lifecycle event.
	 *
	 * @var string
	 */
	const postPersist = 'Doctrine\\ORM\\Event::postPersist';

	/**
	 * The preUpdate event occurs before the database update operations to
	 * entity data.
	 *
	 * This is an entity lifecycle event.
	 *
	 * @var string
	 */
	const preUpdate = 'Doctrine\\ORM\\Event::preUpdate';

	/**
	 * The postUpdate event occurs after the database update operations to
	 * entity data.
	 *
	 * This is an entity lifecycle event.
	 *
	 * @var string
	 */
	const postUpdate = 'Doctrine\\ORM\\Event::postUpdate';

	/**
	 * The postLoad event occurs for an entity after the entity has been loaded
	 * into the current EntityManager from the database or after the refresh operation
	 * has been applied to it.
	 *
	 * Note that the postLoad event occurs for an entity before any associations have been
	 * initialized. Therefore it is not safe to access associations in a postLoad callback
	 * or event handler.
	 *
	 * This is an entity lifecycle event.
	 *
	 * @var string
	 */
	const postLoad = 'Doctrine\\ORM\\Event::postLoad';

	/**
	 * The loadClassMetadata event occurs after the mapping metadata for a class
	 * has been loaded from a mapping source (annotations/xml/yaml).
	 *
	 * @var string
	 */
	const loadClassMetadata = 'Doctrine\\ORM\\Event::loadClassMetadata';

	/**
	 * The preFlush event occurs when the EntityManager#flush() operation is invoked,
	 * but before any changes to managed entities have been calculated. This event is
	 * always raised right after EntityManager#flush() call.
	 */
	const preFlush = 'Doctrine\\ORM\\Event::preFlush';

	/**
	 * The onFlush event occurs when the EntityManager#flush() operation is invoked,
	 * after any changes to managed entities have been determined but before any
	 * actual database operations are executed. The event is only raised if there is
	 * actually something to do for the underlying UnitOfWork. If nothing needs to be done,
	 * the onFlush event is not raised.
	 *
	 * @var string
	 */
	const onFlush = 'Doctrine\\ORM\\Event::onFlush';

	/**
	 * The postFlush event occurs when the EntityManager#flush() operation is invoked and
	 * after all actual database operations are executed successfully. The event is only raised if there is
	 * actually something to do for the underlying UnitOfWork. If nothing needs to be done,
	 * the postFlush event is not raised. The event won't be raised if an error occurs during the
	 * flush operation.
	 *
	 * @var string
	 */
	const postFlush = 'Doctrine\\ORM\\Event::postFlush';

	/**
	 * The onClear event occurs when the EntityManager#clear() operation is invoked,
	 * after all references to entities have been removed from the unit of work.
	 *
	 * @var string
	 */
	const onClear = 'Doctrine\\ORM\\Event::onClear';



	/**
	 * Private constructor. This class is not meant to be instantiated.
	 */
	private function __construct()
	{
	}



	/**
	 * @param string $eventName
	 * @return string
	 */
	public static function prefix($eventName)
	{
		return self::NS . '::' . $eventName;
	}

}
