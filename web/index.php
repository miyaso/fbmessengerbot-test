<?php

require('../vendor/autoload.php');

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use GuzzleHttp\Client;

$app = new Silex\Application();

$app->register(new Silex\Provider\MonologServiceProvider(), array(
    'monolog.logfile' => 'php://stderr',
));

$app->before(function (Request $request) use($bot) {
    // TODO validation
});

$app->get('/callback', function (Request $request) use ($app) {
    $response = "";
    if ($request->query->get('hub_verify_token') === getenv('FACEBOOK_PAGE_VERIFY_TOKEN')) {
        $response = $request->query->get('hub_challenge');
    }

    return $response;
});

$app->post('/callback', function (Request $request) use ($app) {
    // Let's hack from here!
    $body = json_decode($request->getContent(), true);
    $client = new Client(['base_uri' => 'https://graph.facebook.com/v2.6/']);

    foreach($body['entry'] as $obj){
        $app['monolog']->addInfo(sprintf('obj: %s', json_encode($obj)));

        foreach($obj['messaging'] as $m){
            $app['monolog']->addInfo(sprintf('messaging: %s', json_encode($m)));
            $from = $m['sender']['id'];
            $text = $m['message']['text'];

            if ($text) {
                $path = sprintf('me/messages?access_token=%s', getenv('FACEBOOK_PAGE_ACCESS_TOKEN'));
                $json = [
                    'recipient' => [
                        'id' => $from, 
                    ],
                    'message' => [
                        'text' => 'ちょっとまってね！', 
                    ],
                ];
                $client->request('POST', $path, ['json' => $json]);
                // 
                #$pom_key = getenv('POM_KEY');
                #$url = 'http://ws.ponpare.jp/ws/wsp0100/Wst0101Action.do?key='.$pom_key.'&large_area=1&format=json';
                #$json = file_get_contents($url);
                #$json = mb_convert_encoding($json, 'UTF8', 'ASCII,JIS,UTF-8,EUC-JP,SJIS-WIN');
                #$eq_data = json_decode($json,true);
                // 
                for ($i = 0; $i < 10; $i++) {
                    $c_json = [
                        'recipient' => [
                            'id' => $from, 
                        ],
                        'message' => [
                            'text' => sprintf('商品名:%s', $eq_data["ticket"][$i]["name"]), 
                        ],
                        'sticker_id'=> 369239263222822,
                        'attachments'=>[
                            'type'=>'image',
                            'payload'=>[
                                'url'=>'https://fbcdn-dragon-a.akamaihd.net/hphotos-ak-xfa1/t39.1997-6/851557_369239266556155_759568595_n.png',
                            ],
                        ],
                    ];
                    $client->request('POST', $path, ['json' => $c_json]);
                }
                $json = [
                    'recipient' => [
                        'id' => $from, 
                    ],
                    'message' => [
                        'text' => sprintf('%sじゃない', $text), 
                    ],
                ];
                $client->request('POST', $path, ['json' => $json]);
            }
        }
    }
    return 0;
});

$app->run();
