<?php

ini_set('max_execution_time', 0);

require_once 'phpQuery.php';

function receiving_information($url)
{
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    $content = curl_exec($curl);
    curl_close($curl);

    $pq = phpQuery::newDocument($content);

    $name_company = $pq->find('.breadcrumb')->find('.last')->html();

    $actual_address = $pq->find('.field-name-field-physical-address')->find('.even')->html();

    $site = $pq->find('.field-name-field-site')->find('a')->attr('href');

    $phones = $pq->find('.field-name-field-phone')->find('.field-items')->find('.even');
    $a_phones = array();
    foreach ($phones as $item)
    {
        $a_phones[] = pq($item)->html();
	}
    $phone = join('|', $a_phones);

    $working_hours = $pq->find('.field-name-field-work-hours')->find('.field-items')->find('.even');
    $a_working_hours = array();
    foreach ($working_hours as $item)
    {
        $a_working_hours[] = pq($item)->html();
	}
    $working_hour = join('|', $a_working_hours);

    $emails = $pq->find('.field-name-field-email')->find('.field-items')->find('.even')->find('a');
    $a_emails = array();
    foreach ($emails as $item)
    {
        preg_match_all("#[^mailto:][\w@.-]+#",  pq($item)->attr('href'), $cut_email);
        $a_emails[] = $cut_email[0][0];
	}
    $email = join('|', $a_emails);

    $internet_shop = $pq->find('.field-name-field-internet-shop')->find('.field-items')->find('.even')->html();

    $kinds_of_trade = $pq->find('.field-name-field-kinds-of-trade')->find('.field-items')->find('.field-item');
    $a_kinds_of_trade = array();
    foreach ($kinds_of_trade as $item)
    {
        $a_kinds_of_trade[] = pq($item)->html();
	}
    $kind_of_trade = join('|', $a_kinds_of_trade);

    $logo = $pq->find('.field-name-field-company-logo')->find('.image-style-medium7')->attr('src');

    $category = $pq->find('.breadcrumb')->find('.even')->find('a')->html();

    record(array(
        array(
            $name_company,
            $actual_address,
            $site,
            $phone,
            $working_hour,
            $email,
            $internet_shop,
            $kind_of_trade,
            $logo,
            $category,
        )
    ));
}

function get_links_page($url)
{
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    $content = curl_exec($curl);
    curl_close($curl);

    $pq = phpQuery::newDocument($content);

    $page_links = $pq->find('.view-display-id-panel_companies_catalog')->find('.view-content')->find('.views-title-company')->find('a');
    foreach ($page_links as $item)
    {
        receiving_information(pq($item)->attr('href'));
	}
}

function page_enumeration($url)
{
    $not_to_break = 1000;

    for($i = 0; $i <= $not_to_break; $i++)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url.'?page='.$i);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $content = curl_exec($curl);
        curl_close($curl);

        if(preg_match('#<div class="view-empty">([А-Яа-я,.\s]+)|(<p>[А-Яа-я,.\s]+</p>)</div>#u', $content))
        {
            return false;
        }

        get_links_page($url.'?page='.$i);
    }
}

function record($content)
{
    $fp = fopen('parsing_result.csv', 'a');
    fprintf($fp, chr(0xEF).chr(0xBB).chr(0xBF));
    foreach ($content as $item) 
    {
        fputcsv($fp, $item, ';');
    }
    fclose($fp);
}

$urls = [
    'https://ivtextil.ru/trikotazh',
    'https://ivtextil.ru/specodezhda',
    'https://ivtextil.ru/postelnoe-bele',
    'https://ivtextil.ru/domashniy-tekstil',
    'https://ivtextil.ru/tkani',
    'https://ivtextil.ru/catalog',
];

record(array(
    array(
        'Название компании', 
        'Фактический адрес', 
        'Офицальный сайт', 
        'Телефон', 
        'График работы', 
        'Электронная почта', 
        'Интернет-магазин', 
        'Виды торговли', 
        'Логотип', 
        'Категория',
    )
));

foreach($urls as $item)
{
    page_enumeration($item);
}

echo('Операция парсинга выполнена.');
