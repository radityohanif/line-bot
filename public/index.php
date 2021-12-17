<?php

/**
 * Kita akan membuat inisialisasi LINE bot dan SLIM App. Selanjutnya buat route untuk URL Webhook ke public/webhook dengan method POST. Setiap request yang masuk akan dicek terlebih dahulu oleh header content X-Line-Signature apakah valid atau tidak. Kemudian jika lolos validasi signature ini, kode implementasi fitur-fitur Messaging API nantinya akan berjalan.

 * Catatan: Bila hendak mencoba simulasi request, misalnya melalui aplikasi Postman, Anda dapat atur variabel $pass_signature = true; untuk melewati proses pengecekan signature.
 */

require __DIR__ . '/../vendor/autoload.php';

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\RequestInterface as Request;
use Slim\Factory\AppFactory;

use LINE\LINEBot;
use LINE\LINEBot\HTTPClient\CurlHTTPClient;
use LINE\LINEBot\MessageBuilder\MultiMessageBuilder;
use LINE\LINEBot\MessageBuilder\TextMessageBuilder;
use LINE\LINEBot\MessageBuilder\StickerMessageBuilder;
use LINE\LINEBot\SignatureValidator as SignatureValidator;

$pass_signature = true;
$channel_access_token = "fRIKZ6iNY3CgQnmbEXlh/MN4J4ZOlABuuH9HrYlb+XHj13iubIwiV7GdUceE+/hPQycNn9o4vNG0BVu75Io8BW3ECOzrWeJX8tICgcAkuHSF0SU4YYPj+dRUVPdvyTaSoRxhizvrZuJVCp6vsDGpoAdB04t89/1O/w1cDnyilFU=";
$channel_secret = "c910dfb912797f796ce793d91c42da7b";

$httpClient = new CurlHTTPClient($channel_access_token);
$bot = new LINEBot($httpClient, ['channelSecret' => $channel_secret]);

$app = AppFactory::create();
$app->setBasePath("/public");

$app->get('/', function (Request $request, Response $response, $args) {
  $response->getBody()->write("Hello World");
  return $response;
});

// buat root untuk webhook
$app->post('/webhook', function (Request $request, Response $response) use ($channel_secret, $bot, $httpClient, $pass_signature) {

  // get request body and line signature header
  $body = $request->getBody();
  $signature = $request->getHeaderLine('HTTP_X_LINE_SIGNATURE');

  // log body and signature
  file_put_contents('php://stderr', 'Body: ' . $body);

  if ($pass_signature === false) {

    // if LINE_SIGNATURE exist in request header
    if (empty($signature)) {
      return $response->withStatus(400, 'Signature not set');
    }

    // is this request comes from LINE?
    if (!SignatureValidator::validateSignature($body, $channel_secret, $signature)) {
      return $response->withStatus(400, 'Invalid Signature');
    }
  }

  // Kode aplikasi nanti disini..
  $data = json_decode($body, true);
  if (is_array($data['events'])) {
    foreach ($data['events'] as $event) {
      if ($event['type'] == 'message') {
        if ($event['message']['type'] == 'text') {
          // inisiasi 
          $replyToken = $event['replyToken'];
          $pesanMasuk = strtolower($event['message']['text']);
          $mintaStiker = [
            'bagi stiker dong',
            'punya stiker keren gak',
            'stiker',
          ];

          if (in_array($pesanMasuk, $mintaStiker)) {
            $packageId = 1070;
            $stickerId = [17861, 17860, 17854, 17847, 17844];

            // generate stiker random
            $stickerId = $stickerId[rand(0, count($stickerId))];
            $sticker = new StickerMessageBuilder($packageId, $stickerId);

            // kirim
            $result = $bot->replyMessage($replyToken, $sticker);
          } else {
            $result = $bot->replyText($replyToken, "maaf kami gak ngerti kamu ngomong apa 😭");
          }
          $response
            ->getBody()
            ->write(json_encode($result->getJSONDecodedBody()));

          return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($result->getHTTPStatus());
        }
      }
    }
  }
});
$app->run();