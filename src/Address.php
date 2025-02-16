<?php

namespace laocc\address;

class Address
{
    private string $dbPath;
    private string $version = '202012';

    public function __construct(string $version = null)
    {
        if ($version) $this->version = $version;
        $this->dbPath = dirname(__DIR__, 2) . '/data/' . $this->version;
    }

    public function object()
    {
        $area = file_get_contents($this->dbPath . '/china.object.json');
        $area = json_decode($area, true);
        return $area;
    }

    public function array()
    {
        $area = file_get_contents($this->dbPath . '/china.array.json');
        $area = json_decode($area, true);
        return $area;
    }

    public function name_code()
    {
        $area = file_get_contents($this->dbPath . '/china.code.json');
        $area = json_decode($area, true);
        return $area;
    }

    public function code_name()
    {
        $area = file_get_contents($this->dbPath . '/china.code.json');
        $area = json_decode($area, true);
        $area['000000'] = ['nick' => '未知'];
        return $area;
    }

}