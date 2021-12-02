<?php
declare(strict_types=1);
require_once './vendor/autoload.php';

use Nextcloud\CodingStandard\Config;

$config = new Config();
$config->getFinder()
  ->notPath('build')
  ->notPath('dep')
  ->notPath('l10n')
  ->notPath('node_modules')
  ->notPath('src')
  ->notPath('vendor')
  ->in(__DIR__);

return $config;
