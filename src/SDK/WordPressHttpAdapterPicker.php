<?php

declare(strict_types=1);

namespace Liquichain\WooCommerce\SDK;

use Liquichain\Api\HttpAdapter\LiquichainHttpAdapterPickerInterface;

class WordPressHttpAdapterPicker implements LiquichainHttpAdapterPickerInterface
{
    public function pickHttpAdapter($httpClient)
    {
       if($httpClient === null ){
           return new WordPressHttpAdapter();
       }
       return $httpClient;
    }
}
