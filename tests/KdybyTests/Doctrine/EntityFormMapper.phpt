<?php

/**
 * Test: Kdyby\Doctrine\EntityFormMapper.
 *
 * @testCase KdybyTests\Doctrine\EntityFormMapperTest
 * @author Filip Procházka <filip@prochazka.su>
 * @package Kdyby\Doctrine
 */

namespace KdybyTests\Doctrine;

use Kdyby;
use Kdyby\Doctrine\Forms\EntityFormMapper;
use Kdyby\Doctrine\Forms\IComponentMapper;
use KdybyTests\Doctrine\PresenterMock;
use KdybyTests\ORMTestCase;
use Nette;
use Nette\Application\UI;
use Tester;
use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/mocks.php';



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class EntityFormMapperTest extends ORMTestCase
{

	/**
	 * @var EntityFormMapper
	 */
	private $mapper;



	protected function setUp()
	{
		$em = $this->createMemoryManager();
		$this->mapper = new EntityFormMapper($em);

		Kdyby\Doctrine\Forms\ToManyContainer::register();
	}



	public function testBasic_text()
	{
		$form = self::buildEntityForm();
		$form->injectEntityMapper($this->mapper);

		$name = $form->addText('name');

		$entity = new CmsGroup('Robot');
		$form->bindEntity($entity);

		Assert::same('Robot', $name->getValue());

		$this->attachToPresenter($form, array('name' => 'Human'));
		Assert::same('Human', $entity->name);
	}



	public function testRelation_toOne()
	{
		$form = self::buildEntityForm();
		$form->injectEntityMapper($this->mapper);

		$name = $form->addText('name');
		$addressContainer = $form->addContainer('address');
		$city = $addressContainer->addText('city');

		$entity = new CmsUser('Robot');
		$entity->setAddress(new CmsAddress('Brno'));
		$form->bindEntity($entity);

		Assert::same('Robot', $name->getValue());
		Assert::same('Brno', $city->getValue());

		$this->attachToPresenter($form, array('name' => 'Human', 'address' => array('city' => 'Praha')));
		Assert::same('Human', $entity->name);
		Assert::same('Praha', $entity->address->city);
	}



	public function testRelation_toOne_completeRelation()
	{
		$form = self::buildEntityForm();
		$form->injectEntityMapper($this->mapper);

		$name = $form->addText('name');
		$addressContainer = $form->addContainer('address');
		$city = $addressContainer->addText('city');

		$entity = new CmsUser('Robot');
		Assert::null($entity->address);

		$form->bindEntity($entity);

		Assert::same('Robot', $name->getValue());
		Assert::same('', $city->getValue());

		$this->attachToPresenter($form, array('name' => 'Human', 'address' => array('city' => 'Praha')));
		Assert::same('Human', $entity->name);
		Assert::true($entity->address instanceof CmsAddress);
		Assert::same('Praha', $entity->address->city);
	}



	public function testRelation_toOne_itemsLoad()
	{
		$form = self::buildEntityForm();
		$form->injectEntityMapper($this->mapper);
		$em = $this->mapper->getEntityManager();
		$usersDao = $em->getDao(__NAMESPACE__ . '\\CmsUser');

		$usersDao->save(array(
			new CmsUser('DG'),
			new CmsUser('Juzna'),
			new CmsUser('HosipLan'),
		));

		$form->addText('topic');
		$author = $form->addSelect('user')
			->setOption(IComponentMapper::ITEMS_TITLE, 'name');

		$article = new CmsArticle('Nette');
		$form->bindEntity($article);

		Assert::same(array(1 => 'DG', 2 => 'Juzna', 3 => 'HosipLan'), $author->items);

		$this->attachToPresenter($form, array('topic' => 'Nette Framework', 'user' => 2));
		Assert::same('Nette Framework', $article->topic);
		Assert::same('Juzna', $article->user->name);
	}



	public function testRelation_toMany()
	{
		$form = self::buildEntityForm();
		$form->injectEntityMapper($this->mapper);

		$name = $form->addText('name');
		$articlesContainer = $form->toMany('articles', function (Nette\Forms\Container $article) {
			$article->addText('topic');
		});

		$entity = new CmsUser('Robot');
		$entity->addArticle(new CmsArticle('Doctrine'));
		$entity->addArticle(new CmsArticle('Nette'));

		Assert::true(iterator_count($articlesContainer->getComponents()) === 0);

		$form->bindEntity($entity);

		Assert::same('Robot', $name->getValue());
		list($doctrineContainer, $netteContainer) = array_values(iterator_to_array($articlesContainer->getComponents()));

		Assert::same('Doctrine', $doctrineContainer['topic']->value);
		Assert::same('Nette', $netteContainer['topic']->value);

		$this->attachToPresenter($form, array('name' => 'Human', 'articles' => array('_new_0' => array('topic' => 'Dibi'), '_new_1' => array('topic' => 'Zend'))));

		list($first, $second) = $entity->articles->toArray();
		Assert::same('Human', $entity->name);
		Assert::same('Dibi', $first->topic);
		Assert::same('Zend', $second->topic);
	}



	public function testRename()
	{
		$form = self::buildEntityForm();
		$form->injectEntityMapper($this->mapper);

		$name = $form->addCheckbox('surname')
			->setOption(IComponentMapper::FIELD_NAME, 'name');

		$entity = new CmsGroup(TRUE);
		$form->bindEntity($entity);

		Assert::same(TRUE, $name->getValue());

		$this->attachToPresenter($form, array('name' => FALSE));
		Assert::same(FALSE, $entity->name);
	}



	/**
	 * @param UI\Form $form
	 * @param array $data
	 * @return PresenterMock
	 */
	private function attachToPresenter(UI\Form $form, $data = array())
	{
		$presenter = new PresenterMock();
		$this->serviceLocator->callMethod(array($presenter, 'injectPrimary'));

		if (!empty($data)) {
			$request = new Nette\Application\Request('fake', 'POST', array('do' => 'form-submit'), array('do' => 'form-submit') + $data);

		} else {
			$request = new Nette\Application\Request('fake', 'POST', array());
		}

		$presenter->run($request);
		$presenter['form'] = $form;

		return $presenter;
	}



	/**
	 * @return UI\Form|Kdyby\Doctrine\Forms\EntityForm
	 */
	private static function buildEntityForm()
	{
		$class = __NAMESPACE__ . '\\EntityForm';
		if (class_exists($class, FALSE)) {
			return new $class();
		}

		if (PHP_VERSION_ID >= 50400) {
			eval('namespace ' . __NAMESPACE__ . ' { class EntityForm extends \Nette\Application\UI\Form { use \Kdyby\Doctrine\Forms\EntityForm; } }');

		} else {
			$trait = file_get_contents(__DIR__ . '/../../../src/Kdyby/Doctrine/Forms/EntityForm.php');
			$trait = str_replace('namespace Kdyby\Doctrine\Forms;', 'namespace ' . __NAMESPACE__ . ';', $trait);
			$trait = str_replace("use Kdyby;", "use Kdyby;\n" . 'use Kdyby\Doctrine\Forms\EntityFormMapper;', $trait);
			$trait = str_replace("trait EntityForm", 'class EntityForm extends UI\Form', $trait);
			eval(substr($trait, 5));
		}

		return new $class();
	}

}

\run(new EntityFormMapperTest());
