<?php

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/lib/Onsite/UrlShortener.php';

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

define ('RESPONSE_CODE_ERROR', 400);
define ('RESPONSE_CODE_OK', 200);

$app = new Silex\Application();
$app['debug'] = true;

$app->register(new Silex\Provider\DoctrineServiceProvider(), array(
  'db.options' => array(
    'driver' => 'pdo_mysql',
    'host' => 'wizeonsite.c1jgsj59qnnj.us-west-2.rds.amazonaws.com',
    'dbname' => 'wize',
    'user' => 'vjavier',
    'password' => 'rU2r2mPf',
  ),
));

$app->get('/{shorturl}', function ($shorturl) use ($app)  {
  $sql = "SELECT full_url FROM url WHERE short_hash = ?";
  $url = $app['db']->fetchAssoc($sql, array($shorturl)); // this can be cached using memcache
  $sql = "INSERT INTO url_access SET id_url = ?";
  $app['db']->executeUpdate($sql, array($shorturl)); // NEUTRALIZE UTC
  return $app->redirect($url['full_url'], 301);
});

$app->get('/', function () use ($app) {
  $sql = "SELECT full_url, short_hash FROM url";
  $urls = $app['db']->fetchAll($sql);
  $response = new Response(json_encode($urls), 200);
  $response->headers->addCacheControlDirective('must-revalidate', true);
  $response->headers->set('Content-Type', 'application/javascript');
  return $response;
});

$app->patch('/', function( Request $request) use ($app) {

  /*
   * Update  a BD
   * Validaer que no exista previamente
    * Actualizar el campo short_hash
   * Si existe
   * Retornar un error
   *
  */

});

$app->post('/', function( Request $request ) use ($app)  {
  $response = array();
  $url = $request->request->get('url', FALSE);

  if (!filter_var($url, FILTER_VALIDATE_URL) === false) {
    $reponseCode = RESPONSE_CODE_OK;

    $sql = "SELECT short_hash FROM url WHERE full_url_hash = ?";
    $url_exists = $app['db']->fetchAssoc($sql, array(sha1($url)));

    if ( $url_exists ) {
      $shortHash = $url_exists['short_hash'];
    } else {

      do {
        $shortHash = OnSite\UrlShortner::generateShortCode();
        $sql = "SELECT short_hash FROM url WHERE short_hash = ?";
        $shortExists = $app['db']->fetchAssoc($sql, array($shortHash));
      }while($shortExists);

      $sql = "INSERT INTO url SET full_url = ?, full_url_hash = ?, short_hash = ?";
      $app['db']->executeUpdate($sql, array($url, sha1($url), $shortHash));
    }

    $response ['sucess'] = TRUE;
    $response ['short'] = $shortHash;

  } else {
    $reponseCode = RESPONSE_CODE_ERROR;
    $response ['sucess'] = FALSE;
    $response ['message'] = 'URL is not valid';
  }

  $response = new Response(json_encode($response), $reponseCode);
  $response->headers->addCacheControlDirective('must-revalidate', true);
  $response->headers->set('Content-Type', 'application/javascript');

  return $response;
});

$app->run();