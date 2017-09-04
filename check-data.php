<?php
/**
 * Created by PhpStorm.
 * User: afinogen
 * Date: 21.08.17
 * Time: 11:55
 */

define('HOST', 'http://localhost:8085');

if (file_exists(__DIR__.'/error.answ')) {
    unlink(__DIR__.'/error.answ');
}

$data = file(__DIR__.'/data/FULL/answers/phase_1_get.answ');
$countAnswers = count($data);
$countErrors = 0;
foreach ($data as $answer) {
    preg_match('/^(GET|POST)\t(.*)\t([\d]+)(\t(.*)|\n)$/i', $answer, $mathes);
    $oldMatches = $mathes;
    unset($mathes[0]);
    $mathesN = array_values($mathes);
    if (!isset($mathesN[0])) {
        var_dump($oldMatches, $answer);exit;
    }
    list($method, $uri, $code, $serverAnser) = $mathesN;
    $result = send($uri);
    if ($result['http_code'] !== (int)$code || !checkAnswer(json_decode($result['content'], true), json_decode($serverAnser, true))) {
        var_dump($result['content']);
        file_put_contents('error.answ', trim($answer)."\t".PHP_EOL.$result['http_code']."\t".$result['content'].PHP_EOL.PHP_EOL, FILE_APPEND);
        $countErrors++;
    }
}

echo 'Errors - '.$countErrors.' ('.($countErrors/$countAnswers*100).'%)'.PHP_EOL;

function checkAnswer($answer, $originAnswer)
{
    if ($answer === $originAnswer) {
        return true;
    }

    foreach ($originAnswer as $key => $item) {
        if (is_array($item)) {
            if (!isset($answer[$key])) {
                return false;
            }
            if (!checkAnswer($answer[$key], $originAnswer[$key])) {
                return false;
            }
        }
        if (!isset($answer[$key])) {
            return false;
        } elseif (is_numeric($item) && (float)$item != (float)$answer[$key]) {
            return false;
        } elseif($answer[$key] != $item) {
            return false;
        }
    }

    return true;
}

function send($url)
{
    $uagent = "Opera/9.80 (Windows NT 6.1; WOW64) Presto/2.12.388 Version/12.14";

    $ch = curl_init(HOST.$url);

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);   // возвращает веб-страницу
    curl_setopt($ch, CURLOPT_HEADER, 0);           // не возвращает заголовки
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);   // переходит по редиректам
    curl_setopt($ch, CURLOPT_ENCODING, "");        // обрабатывает все кодировки
    curl_setopt($ch, CURLOPT_USERAGENT, $uagent);  // useragent
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 120); // таймаут соединения
    curl_setopt($ch, CURLOPT_TIMEOUT, 120);        // таймаут ответа
    curl_setopt($ch, CURLOPT_MAXREDIRS, 10);       // останавливаться после 10-ого редиректа

    $content = curl_exec($ch);
    $err = curl_errno($ch);
    $errmsg = curl_error($ch);
    $header = curl_getinfo($ch);
    curl_close($ch);

    $header['errno'] = $err;
    $header['errmsg'] = $errmsg;
    $header['content'] = $content;

    return $header;
}
