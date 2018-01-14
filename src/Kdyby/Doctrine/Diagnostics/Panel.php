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
use Tracy\Bar;
use Tracy\BlueScreen;
use Tracy\Debugger;
use Tracy\Dumper;
use Tracy\Helpers;
use Tracy\IBarPanel;



/**
 * Debug panel for Doctrine
 *
 * @author David Grudl
 * @author Patrik Votoček
 * @author Filip Procházka <filip@prochazka.su>
 */
class Panel implements IBarPanel, Doctrine\DBAL\Logging\SQLLogger
{

	use \Kdyby\StrictObjects\Scream;

	/**
	 * @var float logged time
	 */
	public $totalTime = 0;

	/**
	 * @var array
	 */
	public $queries = [];

	/**
	 * @var array
	 */
	public $failed = [];

	/**
	 * @var array
	 */
	public $skipPaths = [
		'vendor/nette/', 'src/Nette/',
		'vendor/doctrine/collections/', 'lib/Doctrine/Collections/',
		'vendor/doctrine/common/', 'lib/Doctrine/Common/',
		'vendor/doctrine/dbal/', 'lib/Doctrine/DBAL/',
		'vendor/doctrine/orm/', 'lib/Doctrine/ORM/',
		'vendor/kdyby/doctrine/', 'src/Kdyby/Doctrine/',
		'vendor/phpunit',
	];

	/**
	 * @var \Doctrine\DBAL\Connection
	 */
	private $connection;

	/**
	 * @var \Doctrine\ORM\EntityManager
	 */
	private $em;



	/***************** Doctrine\DBAL\Logging\SQLLogger ********************/



	/**
	 * @param string $sql
	 * @param array|null $params
	 * @param array|null $types
	 */
	public function startQuery($sql, array $params = NULL, array $types = NULL)
	{
		Debugger::timer('doctrine');

		$source = NULL;
		foreach (debug_backtrace(FALSE) as $row) {
			if (isset($row['file']) && $this->filterTracePaths(realpath($row['file']))) {
				if (isset($row['class']) && stripos($row['class'], '\\' . Proxy::MARKER) !== FALSE) {
					if (!in_array(Doctrine\Common\Persistence\Proxy::class, class_implements($row['class']))) {
						continue;

					} elseif (isset($row['function']) && $row['function'] === '__load') {
						continue;
					}

				} elseif (stripos($row['file'], DIRECTORY_SEPARATOR . Proxy::MARKER) !== FALSE) {
					continue;
				}

				$source = [$row['file'], (int) $row['line']];
				break;
			}
		}

		$this->queries[] = [$sql, $params, NULL, $types, $source];
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
	 * @param \Exception|\Throwable $exception
	 */
	public function queryFailed($exception)
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
			. '<svg viewBox="0 0 2048 2048"><path fill="#aaa" d="M1024 896q237 0 443-43t325-127v170q0 69-103 128t-280 93.5-385 34.5-385-34.5-280-93.5-103-128v-170q119 84 325 127t443 43zm0 768q237 0 443-43t325-127v170q0 69-103 128t-280 93.5-385 34.5-385-34.5-280-93.5-103-128v-170q119 84 325 127t443 43zm0-384q237 0 443-43t325-127v170q0 69-103 128t-280 93.5-385 34.5-385-34.5-280-93.5-103-128v-170q119 84 325 127t443 43zm0-1152q208 0 385 34.5t280 93.5 103 128v128q0 69-103 128t-280 93.5-385 34.5-385-34.5-280-93.5-103-128v-128q0-69 103-128t280-93.5 385-34.5z"></path></svg>'
			. '<span class="tracy-label">'
			. count($this->queries) . ' queries'
			. ($this->totalTime ? ' / ' . sprintf('%0.1f', $this->totalTime * 1000) . ' ms' : '')
			. '</span>'
			. '</span>';
	}



	/**
	 * @return string
	 */
	public function getPanel()
	{
		if (empty($this->queries)) {
			return '';
		}

		$connParams = $this->connection->getParams();
		if ($connParams['driver'] === 'pdo_sqlite' && isset($connParams['path'])) {
			$host = 'path: ' . basename($connParams['path']);

		} else {
			$host = sprintf('host: %s%s/%s',
				$this->connection->getHost(),
				(($p = $this->connection->getPort()) ? ':' . $p : ''),
				$this->connection->getDatabase()
			);
		}

		return
			$this->renderStyles() .
			sprintf('<h1>Queries: %s %s, %s</h1>',
				count($this->queries),
				($this->totalTime ? ', time: ' . sprintf('%0.3f', $this->totalTime * 1000) . ' ms' : ''),
				$host
			) .
			'<div class="nette-inner tracy-inner nette-Doctrine2Panel">' .
				implode('<br>', array_filter([
					$this->renderPanelCacheStatistics(),
					$this->renderPanelQueries()
				])) .
			'</div>';
	}



	private function renderPanelCacheStatistics()
	{
		if (empty($this->em)) {
			return '';
		}

		$config = $this->em->getConfiguration();
		if (!$config->isSecondLevelCacheEnabled()) {
			return '';
		}

		$loggerChain = $config->getSecondLevelCacheConfiguration()
			->getCacheLogger();

		if (!$loggerChain instanceof Doctrine\ORM\Cache\Logging\CacheLoggerChain) {
			return '';
		}

		if (!$statistics = $loggerChain->getLogger('statistics')) {
			return '';
		}

		return Dumper::toHtml($statistics, [Dumper::DEPTH => 5]);
	}



	private function renderPanelQueries()
	{
		if (empty($this->queries)) {
			return "";
		}

		$s = "";
		foreach ($this->queries as $query) {
			$s .= $this->processQuery($query);
		}

		return '<table><tr><th>ms</th><th>SQL Statement</th></tr>' . $s . '</table>';
	}



	/**
	 * @return string
	 */
	protected function renderStyles()
	{
		return '<style>
			#nette-debug td.nette-Doctrine2Panel-sql { background: white !important}
			#nette-debug .nette-Doctrine2Panel-source { color: #BBB !important }
			#nette-debug nette-Doctrine2Panel tr table { margin: 8px 0; max-height: 150px; overflow:auto }
			#tracy-debug td.nette-Doctrine2Panel-sql { background: white !important}
			#tracy-debug .nette-Doctrine2Panel-source { color: #BBB !important }
			#tracy-debug nette-Doctrine2Panel tr table { margin: 8px 0; max-height: 150px; overflow:auto }
		</style>';
	}



	/**
	 * @param array $query
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
	 * @param \Exception|\Throwable $e
	 * @return array|NULL
	 */
	public function renderQueryException($e)
	{
		if ($e instanceof \PDOException && count($this->queries)) {
			$types = $params = [];

			if ($this->connection !== NULL) {
				if (!isset($this->failed[spl_object_hash($e)])) {
					return NULL;
				}

				list($sql, $params, , , $source) = $this->failed[spl_object_hash($e)];

			} else {
				list($sql, $params, , $types, $source) = end($this->queries) + range(1, 5);
			}

			if (!$sql) {
				return NULL;
			}

			return [
				'tab' => 'SQL',
				'panel' => $this->dumpQuery($sql, $params, $types, $source),
			];

		} elseif ($e instanceof Kdyby\Doctrine\QueryException && $e->query !== NULL) {
			if ($e->query instanceof Doctrine\ORM\Query) {
				return [
					'tab' => 'DQL',
					'panel' => $this->dumpQuery($e->query->getDQL(), $e->query->getParameters()),
				];

			} elseif ($e->query instanceof Kdyby\Doctrine\NativeQueryWrapper) {
				return [
					'tab' => 'Native SQL',
					'panel' => $this->dumpQuery($e->query->getSQL(), $e->query->getParameters()),
				];
			}
		}

		return NULL;
	}



	/**
	 * @param \Exception|\Throwable $e
	 * @param \Nette\DI\Container $dic
	 * @return array|NULL
	 */
	public static function renderException($e, Nette\DI\Container $dic)
	{
		if ($e instanceof AnnotationException) {
			if ($dump = self::highlightAnnotationLine($e)) {
				return [
					'tab' => 'Annotation',
					'panel' => $dump,
				];
			}

		} elseif ($e instanceof Doctrine\ORM\Mapping\MappingException) {
			if ($invalidEntity = Strings::match($e->getMessage(), '~^Class "([\\S]+)" .*? is not .*? valid~i')) {
				$refl = Nette\Reflection\ClassType::from($invalidEntity[1]);
				$file = $refl->getFileName();
				$errorLine = $refl->getStartLine();

				return [
					'tab' => 'Invalid entity',
					'panel' => '<p><b>File:</b> ' . self::editorLink($file, $errorLine) . '</p>' .
						BlueScreen::highlightFile($file, $errorLine),
				];
			}

		} elseif ($e instanceof Doctrine\DBAL\Schema\SchemaException && $dic && ($em = $dic->getByType(Kdyby\Doctrine\EntityManager::class, FALSE))) {
			if (!$em instanceof Kdyby\Doctrine\EntityManager) {
				return null;
			}

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

				return [
					'tab' => 'Invalid schema',
					'panel' => '<p><b>File:</b> ' . self::editorLink($file, $errorLine) . '</p>' .
						BlueScreen::highlightFile($file, $errorLine),
				];
			}

		} elseif ($e instanceof Kdyby\Doctrine\DBALException && $e->query !== NULL) {
			return [
				'tab' => 'SQL',
				'panel' => self::highlightQuery(static::formatQuery($e->query, $e->params, [])),
			];

		} elseif ($e instanceof Doctrine\DBAL\Exception\DriverException) {
			if (($prev = $e->getPrevious()) && ($item = Helpers::findTrace($e->getTrace(), Doctrine\DBAL\DBALException::class . '::driverExceptionDuringQuery'))) {
				/** @var \Doctrine\DBAL\Driver $driver */
				$driver = $item['args'][0];
				$params = isset($item['args'][3]) ? $item['args'][3] : [];

				return [
					'tab' => 'SQL',
					'panel' => self::highlightQuery(static::formatQuery($item['args'][2], $params, [], $driver->getDatabasePlatform())),
				];
			}

		} elseif ($e instanceof Doctrine\ORM\Query\QueryException) {
			if (($prev = $e->getPrevious()) && preg_match('~^(SELECT|INSERT|UPDATE|DELETE)\s+.*~i', $prev->getMessage())) {
				return [
					'tab' => 'DQL',
					'panel' => self::highlightQuery(static::formatQuery($prev->getMessage(), [], [])),
				];
			}

		} elseif ($e instanceof \PDOException) {
			$params = [];

			if (isset($e->queryString)) {
				$sql = $e->queryString;

			} elseif ($item = Helpers::findTrace($e->getTrace(), Doctrine\DBAL\Connection::class . '::executeQuery')) {
				$sql = $item['args'][0];
				$params = $item['args'][1];

			} elseif ($item = Helpers::findTrace($e->getTrace(), \PDO::class . '::query')) {
				$sql = $item['args'][0];

			} elseif ($item = Helpers::findTrace($e->getTrace(), \PDO::class . '::prepare')) {
				$sql = $item['args'][0];
			}

			return isset($sql) ? [
				'tab' => 'SQL',
				'panel' => self::highlightQuery(static::formatQuery($sql, $params, [])),
			] : NULL;
		}

		return NULL;
	}



	/**
	 * @param string $query
	 * @param array|Doctrine\Common\Collections\ArrayCollection $params
	 * @param array $types
	 * @param array|string $source
	 * @return string
	 */
	protected function dumpQuery($query, $params, array $types = [], $source = NULL)
	{
		if ($params instanceof ArrayCollection) {
			$tmp = [];
			$tmpTypes = [];
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
		$sql = htmlspecialchars($sql, ENT_IGNORE, 'UTF-8');
		$sql = preg_replace_callback("#(/\\*.+?\\*/)|(\\*\\*.+?\\*\\*)|(?<=[\\s,(])($keywords1)(?=[\\s,)])|(?<=[\\s,(=])($keywords2)(?=[\\s,)=])#is", function ($matches) {
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
	public static function formatQuery($query, $params, array $types = [], AbstractPlatform $platform = NULL)
	{
		if ($platform === NULL) {
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

		$formattedParams = [];
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
	 * @return string|bool
	 */
	public static function highlightAnnotationLine(AnnotationException $e)
	{
		foreach ($e->getTrace() as $step) {
			if (@$step['class'] . @$step['type'] . @$step['function'] !== Doctrine\Common\Annotations\DocParser::class . '->parse') {
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

		$errorLine = self::calculateErrorLine($refl, $e, $line);
		if ($errorLine === NULL) {
			return FALSE;
		}

		$dump = BlueScreen::highlightFile($file, $errorLine);

		return '<p><b>File:</b> ' . self::editorLink($file, $errorLine) . '</p>' . $dump;
	}



	/**
	 * @param \Reflector|\Nette\Reflection\ClassType|\Nette\Reflection\Method|\Nette\Reflection\Property $refl
	 * @param \Exception|\Throwable $e
	 * @param int|NULL $startLine
	 * @return int|NULL
	 */
	public static function calculateErrorLine(\Reflector $refl, $e, $startLine = NULL)
	{
		if ($startLine === NULL && method_exists($refl, 'getStartLine')) {
			$startLine = $refl->getStartLine();
		}
		if ($startLine === NULL) {
			return NULL;
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
	 * @return int
	 */
	protected static function calculateAffectedLine(\Reflector $refl, $symbolPos)
	{
		$doc = $refl->getDocComment();
		/** @var int|NULL $atPos */
		$atPos = NULL;
		$cleanedDoc = self::cleanedPhpDoc($refl, $atPos);
		$beforeCleanLines = count(Strings::split(substr($doc, 0, $atPos), '~[\n\r]+~'));
		$parsedDoc = substr($cleanedDoc, 0, $symbolPos + 1);
		$parsedLines = count(Strings::split($parsedDoc, '~[\n\r]+~'));

		return $parsedLines + max($beforeCleanLines - 1, 0);
	}



	/**
	 * @param \Reflector|Nette\Reflection\ClassType|Nette\Reflection\Method $refl
	 * @param string $annotation
	 * @return string
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
	 * @param int|null $atPos
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
	 * @param string|int $line
	 * @param string $text
	 * @return Nette\Utils\Html
	 */
	private static function editorLink($file, $line, $text = NULL)
	{
		if (Debugger::$editor && is_file($file) && $text !== NULL) {
			return Nette\Utils\Html::el('a')
				->href(strtr(Debugger::$editor, ['%file' => rawurlencode($file), '%line' => $line]))
				->setAttribute('title', "$file:$line")
				->setHtml($text);

		} else {
			return Nette\Utils\Html::el()->setHtml(Helpers::editorLink($file, $line));
		}
	}



	/****************** Registration *********************/



	public function enableLogging()
	{
		if ($this->connection === NULL) {
			throw new Kdyby\Doctrine\InvalidStateException("Doctrine Panel is not bound to connection.");
		}

		$config = $this->connection->getConfiguration();
		$logger = $config->getSQLLogger();

		if ($logger instanceof Doctrine\DBAL\Logging\LoggerChain) {
			$logger->addLogger($this);

		} else {
			$config->setSQLLogger($this);
		}
	}



	/**
	 * @param \Doctrine\DBAL\Connection $connection
	 * @return Panel
	 */
	public function bindConnection(Doctrine\DBAL\Connection $connection)
	{
		if ($this->connection !== NULL) {
			throw new Kdyby\Doctrine\InvalidStateException("Doctrine Panel is already bound to connection.");
		}

		$this->connection = $connection;

		// Tracy
		$this->registerBarPanel(Debugger::getBar());
		Debugger::getBlueScreen()->addPanel([$this, 'renderQueryException']);

		return $this;
	}



	/**
	 * @param Doctrine\ORM\EntityManager $em
	 * @return Panel
	 */
	public function bindEntityManager(Doctrine\ORM\EntityManager $em)
	{
		$this->em = $em;

		if ($this->em instanceof Kdyby\Doctrine\EntityManager) {
			$uowPanel = new EntityManagerUnitOfWorkSnapshotPanel();
			$uowPanel->bindEntityManager($em);
		}

		if ($this->connection === NULL) {
			$this->bindConnection($em->getConnection());
		}

		return $this;
	}



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
		Debugger::getBlueScreen()->addPanel(function ($e) use ($dic) {
			return Panel::renderException($e, $dic);
		});
	}

}
