<?php

namespace address;

const _AddressVersion = '';

class Address
{

    public function objects()
    {
        $area = file_get_contents(_ROOT . _AddressVersion . '/china.object.json');
        $area = json_decode($area, true);
    }

    public function names()
    {
        $area = file_get_contents(_ROOT . _AddressVersion . '/china.code.json');
        $area = json_decode($area, true);
        $area['000000'] = ['nick' => '未知'];
    }

    public function codes()
    {
        $area = file_get_contents(_ROOT . _AddressVersion . '/china.code.json');
        $area = json_decode($area, true);
        $area['000000'] = ['nick' => '未知'];
    }

    public function arrays()
    {
        $area = file_get_contents(_ROOT . _AddressVersion . '/china.array.json');
        $area = json_decode($area, true);
    }

}