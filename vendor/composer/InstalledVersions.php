<?php

namespace Composer;

use Composer\Semver\VersionParser;






class InstalledVersions
{
private static $installed = array (
  'root' => 
  array (
    'pretty_version' => 'dev-master',
    'version' => 'dev-master',
    'aliases' => 
    array (
    ),
    'reference' => 'e798ee9dad656f5e802af54e72aae2cf1961adc9',
    'name' => 'caiocof/fsphp',
  ),
  'versions' => 
  array (
    'caiocof/fsphp' => 
    array (
      'pretty_version' => 'dev-master',
      'version' => 'dev-master',
      'aliases' => 
      array (
      ),
      'reference' => 'e798ee9dad656f5e802af54e72aae2cf1961adc9',
    ),
    'coffeecode/cropper' => 
    array (
      'pretty_version' => '1.3.1',
      'version' => '1.3.1.0',
      'aliases' => 
      array (
      ),
      'reference' => 'ecc9a275d1cbb4117ab52233f298f4c4b85de71a',
    ),
    'coffeecode/optimizer' => 
    array (
      'pretty_version' => '2.0.1',
      'version' => '2.0.1.0',
      'aliases' => 
      array (
      ),
      'reference' => '6379bc47072e3e5d6354d51c5731ecab860804f3',
    ),
    'coffeecode/paginator' => 
    array (
      'pretty_version' => '1.0.5',
      'version' => '1.0.5.0',
      'aliases' => 
      array (
      ),
      'reference' => '7ee353b183fa2915ba2e2d7296108c075b7d0917',
    ),
    'coffeecode/router' => 
    array (
      'pretty_version' => '1.0.7',
      'version' => '1.0.7.0',
      'aliases' => 
      array (
      ),
      'reference' => 'e3b94f72bc389e8b97d82a0fd0e5bff743e51667',
    ),
    'coffeecode/uploader' => 
    array (
      'pretty_version' => '1.0.10',
      'version' => '1.0.10.0',
      'aliases' => 
      array (
      ),
      'reference' => '1fd58f13d41de26be36bef9fcebe54b865f3380e',
    ),
    'league/plates' => 
    array (
      'pretty_version' => 'v4.0.0-alpha',
      'version' => '4.0.0.0-alpha',
      'aliases' => 
      array (
      ),
      'reference' => 'f53f4c1fa4bf307d0f3858a348172ff4faf7669a',
    ),
    'matthiasmullie/minify' => 
    array (
      'pretty_version' => '1.3.63',
      'version' => '1.3.63.0',
      'aliases' => 
      array (
      ),
      'reference' => '9ba1b459828adc13430f4dd6c49dae4950dc4117',
    ),
    'matthiasmullie/path-converter' => 
    array (
      'pretty_version' => '1.1.3',
      'version' => '1.1.3.0',
      'aliases' => 
      array (
      ),
      'reference' => 'e7d13b2c7e2f2268e1424aaed02085518afa02d9',
    ),
    'phpmailer/phpmailer' => 
    array (
      'pretty_version' => 'v6.1.7',
      'version' => '6.1.7.0',
      'aliases' => 
      array (
      ),
      'reference' => '2c2370ba3df7034f9eb7b8f387c97b52b2ba5ad0',
    ),
    'rosell-dk/image-mime-type-guesser' => 
    array (
      'pretty_version' => '0.3',
      'version' => '0.3.0.0',
      'aliases' => 
      array (
      ),
      'reference' => '204fd61ca81e3b0ba46c6165dab8f74816b1fe99',
    ),
    'rosell-dk/webp-convert' => 
    array (
      'pretty_version' => '2.3.2',
      'version' => '2.3.2.0',
      'aliases' => 
      array (
      ),
      'reference' => '5da7989e87cc0b6c61a5fd73262ed28999be27ab',
    ),
  ),
);







public static function getInstalledPackages()
{
return array_keys(self::$installed['versions']);
}









public static function isInstalled($packageName)
{
return isset(self::$installed['versions'][$packageName]);
}














public static function satisfies(VersionParser $parser, $packageName, $constraint)
{
$constraint = $parser->parseConstraints($constraint);
$provided = $parser->parseConstraints(self::getVersionRanges($packageName));

return $provided->matches($constraint);
}










public static function getVersionRanges($packageName)
{
if (!isset(self::$installed['versions'][$packageName])) {
throw new \OutOfBoundsException('Package "' . $packageName . '" is not installed');
}

$ranges = array();
if (isset(self::$installed['versions'][$packageName]['pretty_version'])) {
$ranges[] = self::$installed['versions'][$packageName]['pretty_version'];
}
if (array_key_exists('aliases', self::$installed['versions'][$packageName])) {
$ranges = array_merge($ranges, self::$installed['versions'][$packageName]['aliases']);
}
if (array_key_exists('replaced', self::$installed['versions'][$packageName])) {
$ranges = array_merge($ranges, self::$installed['versions'][$packageName]['replaced']);
}
if (array_key_exists('provided', self::$installed['versions'][$packageName])) {
$ranges = array_merge($ranges, self::$installed['versions'][$packageName]['provided']);
}

return implode(' || ', $ranges);
}





public static function getVersion($packageName)
{
if (!isset(self::$installed['versions'][$packageName])) {
throw new \OutOfBoundsException('Package "' . $packageName . '" is not installed');
}

if (!isset(self::$installed['versions'][$packageName]['version'])) {
return null;
}

return self::$installed['versions'][$packageName]['version'];
}





public static function getPrettyVersion($packageName)
{
if (!isset(self::$installed['versions'][$packageName])) {
throw new \OutOfBoundsException('Package "' . $packageName . '" is not installed');
}

if (!isset(self::$installed['versions'][$packageName]['pretty_version'])) {
return null;
}

return self::$installed['versions'][$packageName]['pretty_version'];
}





public static function getReference($packageName)
{
if (!isset(self::$installed['versions'][$packageName])) {
throw new \OutOfBoundsException('Package "' . $packageName . '" is not installed');
}

if (!isset(self::$installed['versions'][$packageName]['reference'])) {
return null;
}

return self::$installed['versions'][$packageName]['reference'];
}





public static function getRootPackage()
{
return self::$installed['root'];
}







public static function getRawData()
{
return self::$installed;
}



















public static function reload($data)
{
self::$installed = $data;
}
}
