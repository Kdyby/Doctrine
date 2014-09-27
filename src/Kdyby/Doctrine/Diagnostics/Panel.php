<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Kdyby\Doctrine\Diagnostics;

use Doctrine;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\Proxy;
use Doctrine\Common\Annotations\AnnotationException;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;
use Kdyby;
use Nette;
use Nette\Utils\Strings;
use Nette\Utils\Callback;
use Tracy\Bar;
use Tracy\BlueScreen;
use Tracy\Debugger;
use Tracy\Helpers;
use Tracy\IBarPanel;



/**
 * Debug panel for Doctrine
 *
 * @author David Grudl
 * @author Patrik Votoček
 * @author Filip Procházka <filip@prochazka.su>
 */
class Panel extends Nette\Object implements IBarPanel, Doctrine\DBAL\Logging\SQLLogger
{

	/**
	 * @var int logged time
	 */
	public $totalTime = 0;

	/**
	 * @var array
	 */
	public $queries = array();

	/**
	 * @var array
	 */
	public $failed = array();

	/**
	 * @var array
	 */
	public $skipPaths = array(
		'vendor/nette/', 'src/Nette/',
		'vendor/doctrine/dbal/', 'lib/Doctrine/DBAL/',
		'vendor/doctrine/orm/', 'lib/Doctrine/ORM/',
		'vendor/kdyby/doctrine/', 'src/Kdyby/Doctrine/',
		'vendor/phpunit',
	);

	/**
	 * @var \Doctrine\DBAL\Connection
	 */
	private $connection;



	/**
	 * @param \Doctrine\DBAL\Connection $connection
	 * @throws \Kdyby\Doctrine\InvalidStateException
	 */
	public function setConnection(Doctrine\DBAL\Connection $connection)
	{
		if ($this->connection !== NULL) {
			throw new Kdyby\Doctrine\InvalidStateException("Doctrine Panel is already bound to connection.");
		}

		if (($logger = $connection->getConfiguration()->getSQLLogger()) instanceof Doctrine\DBAL\Logging\LoggerChain) {
			$logger->addLogger($this);

		} else {
			$connection->getConfiguration()->setSQLLogger($this);
		}

		$this->connection = $connection;
	}



	/***************** Doctrine\DBAL\Logging\SQLLogger ********************/



	/**
	 * @param string
	 * @param array
	 * @param array
	 */
	public function startQuery($sql, array $params = NULL, array $types = NULL)
	{
		Debugger::timer('doctrine');

		$source = NULL;
		foreach (debug_backtrace(FALSE) as $row) {
			if (isset($row['file']) && $this->filterTracePaths(realpath($row['file']))) {
				if (isset($row['class']) && stripos($row['class'], '\\' . Proxy::MARKER) !== FALSE) {
					if (!in_array('Doctrine\Common\Persistence\Proxy', class_implements($row['class']))) {
						continue;

					} elseif (isset($row['function']) && $row['function'] === '__load') {
						continue;
					}

				} elseif (stripos($row['file'], DIRECTORY_SEPARATOR . Proxy::MARKER) !== FALSE) {
					continue;
				}

				$source = array($row['file'], (int) $row['line']);
				break;
			}
		}

		$this->queries[] = array($sql, $params, NULL, $types, $source);
	}



	/**
	 * @param string $file
	 * @return boolean
	 */
	protected function filterTracePaths($file)
	{
		$file = str_replace(DIRECTORY_SEPARATOR, '/', $file);
		$return = is_file($file);
		foreach ($this->skipPaths as $path) {
			if (!$return) {
				break;
			}
			$return = $return && strpos($file, '/' . trim($path, '/') . '/') === FALSE;
		}
		return $return;
	}



	/**
	 * @return array
	 */
	public function stopQuery()
	{
		$keys = array_keys($this->queries);
		$key = end($keys);
		$this->queries[$key][2] = $time = Debugger::timer('doctrine');
		$this->totalTime += $time;

		return $this->queries[$key] + array_fill_keys(range(0, 4), NULL);
	}



	/**
	 * @param \Exception $exception
	 */
	public function queryFailed(\Exception $exception)
	{
		$this->failed[spl_object_hash($exception)] = $this->stopQuery();
	}



	/***************** Tracy\IBarPanel ********************/



	/**
	 * @return string
	 */
	public function getTab()
	{
		return '<span title="Doctrine 2">'
			. '<img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAQAAAC1+jfqAAAABGdBTUEAAK/INwWK6QAAABl0RVh0U29mdHdhcmUAQWRvYmUgSW1hZ2VSZWFkeXHJZTwAAAEYSURBVBgZBcHPio5hGAfg6/2+R980k6wmJgsJ5U/ZOAqbSc2GnXOwUg7BESgLUeIQ1GSjLFnMwsKGGg1qxJRmPM97/1zXFAAAAEADdlfZzr26miup2svnelq7d2aYgt3rebl585wN6+K3I1/9fJe7O/uIePP2SypJkiRJ0vMhr55FLCA3zgIAOK9uQ4MS361ZOSX+OrTvkgINSjS/HIvhjxNNFGgQsbSmabohKDNoUGLohsls6BaiQIMSs2FYmnXdUsygQYmumy3Nhi6igwalDEOJEjPKP7CA2aFNK8Bkyy3fdNCg7r9/fW3jgpVJbDmy5+PB2IYp4MXFelQ7izPrhkPHB+P5/PjhD5gCgCenx+VR/dODEwD+A3T7nqbxwf1HAAAAAElFTkSuQmCC" />'
			. count($this->queries) . ' queries'
			. ($this->totalTime ? ' / ' . sprintf('%0.1f', $this->totalTime * 1000) . ' ms' : '')
			. '</span>';
	}



	/**
	 * @return string
	 */
	public function getPanel()
	{
		if (empty($this->queries)) {
			return "";
		}

		$s = "";
		foreach ($this->queries as $query) {
			$s .= $this->processQuery($query);
		}

		$host = sprintf('%s%s/%s',
			$this->connection->getHost(),
			(($p = $this->connection->getPort()) ? ':' . $p : ''),
			$this->connection->getDatabase()
		);

		return $this->renderStyles() .
			'<h1>Queries: ' . count($this->queries) . ($this->totalTime ? ', time: ' . sprintf('%0.3f', $this->totalTime * 1000) . ' ms' : '') . ', host: ' . $host . '</h1>' .
			'<div class="nette-inner tracy-inner nette-Doctrine2Panel">' .
			'<table><tr><th>ms</th><th>SQL Statement</th></tr>' . $s . '</table></div>';
	}



	/**
	 * @return string
	 */
	protected function renderStyles()
	{
		return '<style> #nette-debug td.nette-Doctrine2Panel-sql { background: white !important}
			#nette-debug .nette-Doctrine2Panel-source { color: #BBB !important }
			#nette-debug nette-Doctrine2Panel tr table { margin: 8px 0; max-height: 150px; overflow:auto } </style>';
	}



	/**
	 * @param array
	 * @return string
	 */
	protected function processQuery(array $query)
	{
		$h = 'htmlspecialchars';
		list($sql, $params, $time, $types, $source) = $query;

		$s = self::highlightQuery(static::formatQuery($sql, (array) $params, (array) $types, $this->connection ? $this->connection->getDatabasePlatform() : NULL));
		if ($source) {
			$s .= self::editorLink($source[0], $source[1], $h('.../' . basename(dirname($source[0]))) . '/<b>' . $h(basename($source[0])) . '</b>');
		}

		return '<tr><td>' . sprintf('%0.3f', $time * 1000) . '</td>' .
			'<td class = "nette-Doctrine2Panel-sql">' . $s . '</td></tr>';
	}



	/****************** Exceptions handling *********************/



	/**
	 * @param \Exception $e
	 * @return void|array
	 */
	public function renderQueryException($e)
	{
		if ($e instanceof \PDOException && count($this->queries)) {
			$types = $params = array();

			if ($this->connection !== NULL) {
				if (!$e instanceof Kdyby\Doctrine\DBALException || $e->connection !== $this->connection) {
					return NULL;

				} elseif (!isset($this->failed[spl_object_hash($e)])) {
					return NULL;
				}

				list($sql, $params, , , $source) = $this->failed[spl_object_hash($e)];

			} else {
				list($sql, $params, , $types, $source) = end($this->queries) + range(1, 5);
			}

			if (!$sql) {
				return NULL;
			}

			return array(
				'tab' => 'SQL',
				'panel' => $this->dumpQuery($sql, $params, $types, $source),
			);

		} elseif ($e instanceof Kdyby\Doctrine\QueryException && $e->query !== NULL) {
			if ($e->query instanceof Doctrine\ORM\Query) {
				return array(
					'tab' => 'DQL',
					'panel' => $this->dumpQuery($e->query->getDQL(), $e->query->getParameters()),
				);

			} elseif ($e->query instanceof Kdyby\Doctrine\NativeQueryWrapper) {
				return array(
					'tab' => 'Native SQL',
					'panel' => $this->dumpQuery($e->query->getSQL(), $e->query->getParameters()),
				);
			}
		}
	}



	/**
	 * @param \Exception $e
	 * @param \Nette\DI\Container $dic
	 * @return array
	 */
	public static function renderException($e, Nette\DI\Container $dic)
	{
		if ($e instanceof AnnotationException) {
			if ($dump = self::highlightAnnotationLine($e)) {
				return array(
					'tab' => 'Annotation',
					'panel' => $dump,
				);
			}

		} elseif ($e instanceof Doctrine\ORM\Mapping\MappingException) {
			if ($invalidEntity = Strings::match($e->getMessage(), '~^Class "([\\S]+)" .*? is not .*? valid~i')) {
				$refl = Nette\Reflection\ClassType::from($invalidEntity[1]);
				$file = $refl->getFileName();
				$errorLine = $refl->getStartLine();

				return array(
					'tab' => 'Invalid entity',
					'panel' => '<p><b>File:</b> ' . self::editorLink($file, $errorLine) . '</p>' .
						BlueScreen::highlightFile($file, $errorLine),
				);
			}

		} elseif ($e instanceof Doctrine\DBAL\Schema\SchemaException && $dic && ($em = $dic->getByType('Kdyby\Doctrine\EntityManager', FALSE))) {
			/** @var Kdyby\Doctrine\EntityManager $em */

			if ($invalidTable = Strings::match($e->getMessage(), '~table \'(.*?)\'~i')) {
				foreach ($em->getMetadataFactory()->getAllMetadata() as $class) {
					/** @var Kdyby\Doctrine\Mapping\ClassMetadata $class */
					if ($class->getTableName() === $invalidTable[1]) {
						$refl = $class->getReflectionClass();
						break;
					}
				}

				if (!isset($refl)) {
					return NULL;
				}

				$file = $refl->getFileName();
				$errorLine = $refl->getStartLine();

				return array(
					'tab' => 'Invalid schema',
					'panel' => '<p><b>File:</b> ' . self::editorLink($file, $errorLine) . '</p>' .
						BlueScreen::highlightFile($file, $errorLine),
				);
			}

		} elseif ($e instanceof Kdyby\Doctrine\DBALException && $e->query) {
			return array(
				'tab' => 'SQL',
				'panel' => self::highlightQuery(static::formatQuery($e->query, $e->params, array())),
			);

		} elseif ($e instanceof Doctrine\ORM\Query\QueryException) {
			if (($prev = $e->getPrevious()) && preg_match('~^(SELECT|INSERT|UPDATE|DELETE)\s+.*~i', $prev->getMessage())) {
				return array(
					'tab' => 'DQL',
					'panel' => self::highlightQuery(static::formatQuery($prev->getMessage(), array(), array())),
				);
			}

		} elseif ($e instanceof \PDOException) {
			$params = array();

			if (isset($e->queryString)) {
				$sql = $e->queryString;

			} elseif ($item = Helpers::findTrace($e->getTrace(), 'Doctrine\DBAL\Connection::executeQuery')) {
				$sql = $item['args'][0];
				$params = $item['args'][1];

			} elseif ($item = Helpers::findTrace($e->getTrace(), 'PDO::query')) {
				$sql = $item['args'][0];

			} elseif ($item = Helpers::findTrace($e->getTrace(), 'PDO::prepare')) {
				$sql = $item['args'][0];
			}

			return isset($sql) ? array(
				'tab' => 'SQL',
				'panel' => self::highlightQuery(static::formatQuery($sql, $params, array())),
			) : NULL;
		}

		return NULL;
	}



	/**
	 * @param string $query
	 * @param array|Doctrine\Common\Collections\ArrayCollection $params
	 * @param array $types
	 * @param string $source
	 *
	 * @return array
	 */
	protected function dumpQuery($query, $params, array $types = array(), $source = NULL)
	{
		if ($params instanceof ArrayCollection) {
			$tmp = array();
			$tmpTypes = array();
			foreach ($params as $key => $param) {
				if ($param instanceof Doctrine\ORM\Query\Parameter) {
					$tmpTypes[$param->getName()] = $param->getType();
					$tmp[$param->getName()] = $param->getValue();
					continue;
				}
				$tmp[$key] = $param;
			}
			$params = $tmp;
			$types = $tmpTypes;
		}

		// query
		$s = '<p><b>Query</b></p><table><tr><td class="nette-Doctrine2Panel-sql">';
		$s .= self::highlightQuery(static::formatQuery($query, $params, $types, $this->connection ? $this->connection->getDatabasePlatform() : NULL));
		$s .= '</td></tr></table>';

		$e = NULL;
		if ($source && is_array($source)) {
			list($file, $line) = $source;
			$e = '<p><b>File:</b> ' . self::editorLink($file, $line) . '</p>';
		}

		// styles and dump
		return $this->renderStyles() . '<div class="nette-inner tracy-inner nette-Doctrine2Panel">' . $e . $s . '</div>';
	}



	/**
	 * Returns syntax highlighted SQL command.
	 * This method is same as Nette\Database\Helpers::dumpSql except for parameters handling.
	 * @link https://github.com/nette/database/blob/667143b2d5b940f78c8dc9212f95b1bbc033c6a3/src/Database/Helpers.php#L75-L138
	 * @author David Grudl
	 * @param string $sql
	 * @return string
	 */
	public static function highlightQuery($sql)
	{
		static $keywords1 = 'SELECT|(?:ON\s+DUPLICATE\s+KEY)?UPDATE|INSERT(?:\s+INTO)?|REPLACE(?:\s+INTO)?|DELETE|CALL|UNION|FROM|WHERE|HAVING|GROUP\s+BY|ORDER\s+BY|LIMIT|OFFSET|SET|VALUES|LEFT\s+JOIN|INNER\s+JOIN|TRUNCATE';
		static $keywords2 = 'ALL|DISTINCT|DISTINCTROW|IGNORE|AS|USING|ON|AND|OR|IN|IS|NOT|NULL|[RI]?LIKE|REGEXP|TRUE|FALSE|WITH|INSTANCE\s+OF';

		// insert new lines
		$sql = " $sql ";
		$sql = preg_replace("#(?<=[\\s,(])($keywords1)(?=[\\s,)])#i", "\n\$1", $sql);

		// reduce spaces
		$sql = preg_replace('#[ \t]{2,}#', ' ', $sql);

		$sql = wordwrap($sql, 100);
		$sql = preg_replace('#([ \t]*\r?\n){2,}#', "\n", $sql);

		// syntax highlight
		$sql = htmlSpecialChars($sql);
		$sql = preg_replace_callback("#(/\\*.+?\\*/)|(\\*\\*.+?\\*\\*)|(?<=[\\s,(])($keywords1)(?=[\\s,)])|(?<=[\\s,(=])($keywords2)(?=[\\s,)=])#is", function($matches) {
			if (!empty($matches[1])) { // comment
				return '<em style="color:gray">' . $matches[1] . '</em>';

			} elseif (!empty($matches[2])) { // error
				return '<strong style="color:red">' . $matches[2] . '</strong>';

			} elseif (!empty($matches[3])) { // most important keywords
				return '<strong style="color:blue">' . $matches[3] . '</strong>';

			} elseif (!empty($matches[4])) { // other keywords
				return '<strong style="color:green">' . $matches[4] . '</strong>';
			}
		}, $sql);

		return '<pre class="dump">' . trim($sql) . "</pre>\n";
	}



	/**
	 * @param string $query
	 * @param array $params
	 * @param array $types
	 * @param \Doctrine\DBAL\Platforms\AbstractPlatform $platform
	 * @throws \Doctrine\DBAL\DBALException
	 * @throws \Nette\Utils\RegexpException
	 * @return string
	 */
	public static function formatQuery($query, $params, array $types = array(), AbstractPlatform $platform = NULL)
	{
		if (!$platform) {
			$platform = new Doctrine\DBAL\Platforms\MySqlPlatform();
		}

		if (!$types) {
			foreach ($params as $key => $param) {
				if (is_array($param)) {
					$types[$key] = Doctrine\DBAL\Connection::PARAM_STR_ARRAY;

				} else {
					$types[$key] = 'string';
				}
			}
		}

		try {
			list($query, $params, $types) = \Doctrine\DBAL\SQLParserUtils::expandListParameters($query, $params, $types);
		} catch (Doctrine\DBAL\SQLParserUtilsException $e) {
		}

		$formattedParams = array();
		foreach ($params as $key => $param) {
			if (isset($types[$key])) {
				if (is_scalar($types[$key]) && array_key_exists($types[$key], Type::getTypesMap())) {
					$types[$key] = Type::getType($types[$key]);
				}

				/** @var Type[] $types */
				if ($types[$key] instanceof Type) {
					$param = $types[$key]->convertToDatabaseValue($param, $platform);
				}
			}

			$formattedParams[] = SimpleParameterFormatter::format($param);
		}
		$params = $formattedParams;

		if (Nette\Utils\Validators::isList($params)) {
			$parts = explode('?', $query);
			if (count($params) > $parts) {
				throw new Kdyby\Doctrine\InvalidStateException("Too mny parameters passed to query.");
			}

			return implode('', Kdyby\Doctrine\Helpers::zipper($parts, $params));
		}

		return Strings::replace($query, '~(\\:[a-z][a-z0-9]*|\\?[0-9]*)~i', function ($m) use (&$params) {
			if (substr($m[0], 0, 1) === '?') {
				if (strlen($m[0]) > 1) {
					if (isset($params[$k = substr($m[0], 1)])) {
						return $params[$k];
					}

				} else {
					return array_shift($params);
				}

			} else {
				if (isset($params[$k = substr($m[0], 1)])) {
					return $params[$k];
				}
			}

			return $m[0];
		});
	}



	/**
	 * @param \Doctrine\Common\Annotations\AnnotationException $e
	 *
	 * @return string
	 */
	public static function highlightAnnotationLine(AnnotationException $e)
	{
		foreach ($e->getTrace() as $step) {
			if (@$step['class'] . @$step['type'] . @$step['function'] !== 'Doctrine\Common\Annotations\DocParser->parse') {
				continue;
			}

			$context = Strings::match($step['args'][1], '~^(?P<type>[^\s]+)\s*(?P<class>[^:]+)(?:::\$?(?P<property>[^\\(]+))?$~i');
			break;
		}

		if (!isset($context)) {
			return FALSE;
		}

		$refl = Nette\Reflection\ClassType::from($context['class']);
		$file = $refl->getFileName();
		$line = NULL;

		if ($context['type'] === 'property') {
			$refl = $refl->getProperty($context['property']);
			$line = Kdyby\Doctrine\Helpers::getPropertyLine($refl);

		} elseif ($context['type'] === 'method') {
			$refl = $refl->getProperty($context['method']);
		}

		if (($errorLine = self::calculateErrorLine($refl, $e, $line)) === NULL) {
			return FALSE;
		}

		$dump = BlueScreen::highlightFile($file, $errorLine);

		return '<p><b>File:</b> ' . self::editorLink($file, $errorLine) . '</p>' . $dump;
	}



	/**
	 * @param \Reflector|\Nette\Reflection\ClassType|\Nette\Reflection\Method $refl
	 * @param \Exception $e
	 * @param int $startLine
	 *
	 * @return int|string
	 */
	public static function calculateErrorLine(\Reflector $refl, \Exception $e, $startLine = NULL)
	{
		if ($startLine === NULL) {
			$startLine = $refl->getStartLine();
		}

		if ($pos = Strings::match($e->getMessage(), '~position\s*(\d+)~')) {
			$targetLine = self::calculateAffectedLine($refl, $pos[1]);

		} elseif ($notImported = Strings::match($e->getMessage(), '~^\[Semantical Error\]\s+The annotation "([^"]*?)"~i')) {
			$parts = explode(self::findRenamed($refl, $notImported[1]), self::cleanedPhpDoc($refl), 2);
			$targetLine = self::calculateAffectedLine($refl, strlen($parts[0]));

		} elseif ($notFound = Strings::match($e->getMessage(), '~^\[Semantical Error\]\s+Couldn\'t find\s+(.*?)\s+(.*?),\s+~')) {
			// this is just a guess
			$parts = explode(self::findRenamed($refl, $notFound[2]), self::cleanedPhpDoc($refl), 2);
			$targetLine = self::calculateAffectedLine($refl, strlen($parts[0]));

		} else {
			$targetLine = self::calculateAffectedLine($refl, 1);
		}

		$phpDocLines = count(Strings::split($refl->getDocComment(), '~[\n\r]+~'));

		return $startLine - ($phpDocLines - ($targetLine - 1));
	}



	/**
	 * @param \Reflector|\Nette\Reflection\ClassType|\Nette\Reflection\Method $refl
	 * @param int $symbolPos
	 *
	 * @return int
	 */
	protected static function calculateAffectedLine(\Reflector $refl, $symbolPos)
	{
		$doc = $refl->getDocComment();
		$cleanedDoc = self::cleanedPhpDoc($refl, $atPos);
		$beforeCleanLines = count(Strings::split(substr($doc, 0, $atPos), '~[\n\r]+~'));
		$parsedDoc = substr($cleanedDoc, 0, $symbolPos + 1);
		$parsedLines = count(Strings::split($parsedDoc, '~[\n\r]+~'));

		return $parsedLines + max($beforeCleanLines - 1, 0);
	}



	/**
	 * @param \Reflector|Nette\Reflection\ClassType|Nette\Reflection\Method $refl
	 * @param $annotation
	 */
	private static function findRenamed(\Reflector $refl, $annotation)
	{
		$parser = new Doctrine\Common\Annotations\PhpParser();
		$imports = $parser->parseClass($refl instanceof \ReflectionClass ? $refl : $refl->getDeclaringClass());

		$annotationClass = ltrim($annotation, '@');
		foreach ($imports as $alias => $import) {
			if (!Strings::startsWith($annotationClass, $import)) {
				continue;
			}

			$aliased = str_replace(Strings::lower($import), $alias, Strings::lower($annotationClass));
			$searchFor = preg_quote(Strings::lower($aliased));

			if (!$m = Strings::match($refl->getDocComment(), "~(?P<usage>@?$searchFor)~i")) {
				continue;
			}

			return $m['usage'];
		}

		return $annotation;
	}



	/**
	 * @param \Nette\Reflection\ClassType|\Nette\Reflection\Method|\Reflector $refl
	 * @param null $atPos
	 *
	 * @return string
	 */
	private static function cleanedPhpDoc(\Reflector $refl, &$atPos = NULL)
	{
		return trim(substr($doc = $refl->getDocComment(), $atPos = strpos($doc, '@') - 1), '* /');
	}



	/**
	 * Returns link to editor.
	 * @author David Grudl
	 * @param string $file
	 * @param string $line
	 * @param string $text
	 * @return Nette\Utils\Html
	 */
	private static function editorLink($file, $line, $text = NULL)
	{
		if (Debugger::$editor && is_file($file) && $text !== NULL) {
			return Nette\Utils\Html::el('a')
				->href(strtr(Debugger::$editor, array('%file' => rawurlencode($file), '%line' => $line)))
				->title("$file:$line")
				->setHtml($text);

		} else {
			return Helpers::editorLink($file, $line);
		}
	}



	/****************** Registration *********************/



	/**
	 * Registers panel to debugger
	 *
	 * @param \Tracy\Bar $bar
	 */
	public function registerBarPanel(Bar $bar)
	{
		$bar->addPanel($this);
	}



	/**
	 * Registers generic exception renderer
	 */
	public static function registerBluescreen(Nette\DI\Container $dic)
	{
		static::getDebuggerBlueScreen()->addPanel(function ($e) use ($dic) {
			return Panel::renderException($e, $dic);
		});
	}



	/**
	 * @param \Doctrine\DBAL\Connection $connection
	 * @return Panel
	 */
	public static function register(Doctrine\DBAL\Connection $connection)
	{
		$panel = new static();
		/** @var Panel $panel */

		$panel->setConnection($connection);
		$panel->registerBarPanel(static::getDebuggerBar());
		static::getDebuggerBlueScreen()->addPanel(Callback::closure($panel, 'renderQueryException'));

		return $panel;
	}



	/**
	 * @return Bar
	 */
	private static function getDebuggerBar()
	{
		return Debugger::getBar();
	}



	/**
	 * @return BlueScreen
	 */
	private static function getDebuggerBlueScreen()
	{
		return Debugger::getBlueScreen();
	}

}
