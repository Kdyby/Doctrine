if [ $TRAVIS_PHP_VERSION = '5.6' ]; then
  composer create-project --prefer-source --dev --no-interaction jakub-onderka/php-parallel-lint vendor/php-parallel-lint ~0.8
  php vendor/php-parallel-lint/parallel-lint.php -e php,phpt --exclude vendor .
  composer create-project --prefer-source --dev --no-interaction nette/code-checker vendor/code-checker ~2.2
  php vendor/code-checker/src/code-checker.php -d src
  php vendor/code-checker/src/code-checker.php -d tests
fi
