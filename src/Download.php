<?php

namespace laocc\download;


class Download
{


    /**
     * 中国省市县地区编码，
     * 从民政部官网：http://www.mca.gov.cn/article/sj/xzqh/2020/
     * 查询最新的地区编码表，比如：http://www.mca.gov.cn/article/sj/xzqh/2020/2020/202003301019.html
     * 国家统计局数据：http://www.stats.gov.cn/tjsj/tjbz/tjyqhdmhcxhfdm/
     *
     * https://lbs.qq.com/service/webService/webServiceGuide/webServiceDistrict
     * 调用示例，含全国地区
     * https://apis.map.qq.com/ws/district/v1/list?key=OB4BZ-D4W3U-B7VVO-4PJWW-6TKDJ-WPB77
     *
     */
    function download($url, $save, $comparison)
    {
        if (!is_readable($save)) {
            echo "{$save} 目录不可读写";
            return;
        }
        $js = 256 | 64 | 128;

//        $url = 'http://www.mca.gov.cn/article/sj/xzqh/2020/2020/202003301019.html';
        if (is_readable(rtrim($save . '/') . '/china.temp.html')) {
            $html = file_get_contents(rtrim($save . '/') . '/china.temp.html');

        } else {
            $html = file_get_contents($url);
            echo file_put_contents(rtrim($save . '/') . '/china.temp.html', $html) . "\n";
        }
        $compCode = file_get_contents($comparison . '/china.code.json');
        $comparison = file_get_contents($comparison . '/china.object.json');
        $compCode = json_decode($compCode, true);
        $comparison = json_decode($comparison, true);

        /**
         * 没有县区的地级市：{"441900":"东莞市","442000":"中山市","460300":"三沙市","460400":"儋州市","620200":"嘉峪关市"}
         */
        $empProv = ['710000', '810000', '820000'];

        //简化名称
        $reZu = function ($n) {
            $n = str_replace(['自治区', '自治旗', '自治州', '自治县', '鄂温克族', '东乡族自治县'], ['', '旗', '州', '县', '鄂温克', '东乡县'], $n);
            if ($n === '内蒙古') return $n;
            $zu = ["满族", "蒙古族", "回族", "达斡尔族", "朝鲜族", "畲族", "土家族", "苗族", "瑶族", "侗族", "各族", "仫佬族", "毛南族", "黎族",
                "羌族", "仡佬族", "布依族", "水族", "哈尼族", "纳西族", "拉祜族", "佤族", "布朗族", "傣族", "壮族", "白族", "彝族", "景颇族",
                "傈僳族", "独龙族", "怒族", "普米族", "裕固族", "哈萨克族", "东乡族", "保安族", "土族", "撒拉族", "藏族"];
            $reName = '/(' . implode('|', $zu) . '|哈萨克|蒙古|维吾尔)/';
            return preg_replace($reName, '', $n);
        };

        $html = preg_replace('/<span\s+style=.+<\/span>/', '', $html);
        preg_match_all('/>(\d{6})<.+?>([\x{4e00}-\x{9fa5}]+?)<\/td>/isu', $html, $area, PREG_SET_ORDER);

        file_put_contents(rtrim($save . '/') . '/china.temp.json', json_encode($area, $js)) . "\n";

        $prov = $city = '';
        $cityName = $cityNick = null;
        $provName = $proNick = null;
        $list = 'list';
        $address = [];
        $ePro = $eCity = $eCount = [];

        foreach ($area as $i => $a) {
            $name = $a[2];
            $code = $a[1];
            $nick = $reZu($name);
            if (!isset($compCode[$code])) {
                echo "新地区码：{$code}:{$name}\n";
            }

            if (preg_match('/^\d{2}0{4}$/', $code)) {
                //4个0为省级地区

                if ($city and empty($address[$prov][$list][$city][$list])) {
//                    echo "市.无下级市县:{$city}\t{$cityName}\n";
                    $tCode = substr($city, 0, 4) . '01';
                    $eCity[$city] = $cityName;
                    $address[$prov][$list][$city][$list][$tCode] = ['code' => $tCode, 'name' => $cityName, 'nick' => $cityNick];
                }

                $prov = $code;
                $city = '';//重置地区市
                $provName = $name;
                $proNick = $nick;
                $address[$prov] = ['code' => $code, 'name' => $name, 'nick' => $nick, $list => []];

            } else if (preg_match('/^\d{4}0{2}$/', $code)) {
                //后2个0为市级

                if ($city and empty($address[$prov][$list][$city][$list])) {
//                    echo "市.无下级市县:{$city}\t{$cityName}\n";
                    $tCode = substr($city, 0, 4) . '01';
                    $eCity[$city] = $cityName;
                    $address[$prov][$list][$city][$list][$tCode] = ['code' => $tCode, 'name' => $cityName, 'nick' => $cityNick];
                }

                $city = $code;
                $cityName = $name;
                $cityNick = $nick;
                $address[$prov][$list][$city] = ['code' => $code, 'name' => $name, 'nick' => $nick, $list => []];
            } else if (empty($city)) {
                //没有地级市的情况，如重庆
                $zxs = $address[$prov];
                $zCode = substr($code, 0, 4) . '00';
                if (!isset($address[$prov][$list][$zCode])) {
//                    echo "省.没有地级市:{$prov}\t{$provName}\n";
                    $ePro[$prov] = $provName;
                    if ($zCode === '500100') {
                        $zxs['name'] = '重庆辖区';
                        $zxs['nick'] = '重庆辖区';
                    }
                    if ($zCode === '500200') {
                        $zxs['name'] = '重庆辖县';
                        $zxs['nick'] = '重庆辖县';
                    }
                    $address[$prov][$list][$zCode] = ['code' => $zCode, 'name' => $zxs['name'], 'nick' => $zxs['nick'], $list => []];
                }
                $address[$prov][$list][$zCode][$list][$code] = ['code' => $code, 'name' => $name, 'nick' => $nick];
            } else {
                if (substr($city, 0, 4) !== substr($code, 0, 4)) {

                    if (empty($address[$prov][$list][$city][$list])) {
//                        echo "市.无下级市县:{$city}\t{$cityName}\n";
                        $tCode = substr($city, 0, 4) . '01';
                        $eCity[$city] = $cityName;
                        $address[$prov][$list][$city][$list][$tCode] = ['code' => $tCode, 'name' => $cityName, 'nick' => $cityNick];
                    }

//                    echo "县.省直辖市县:{$code}\t{$proNick}.{$name}\n";
                    $zCode = substr($code, 0, 4) . '00';
                    $eCount[$code] = $proNick . '.' . $name;

                    if (!isset($address[$prov][$list][$zCode])) {
                        $address[$prov][$list][$zCode] = ['code' => $zCode, 'name' => '省级直辖市县', 'nick' => '直辖市县', $list => []];
                    }
                    $address[$prov][$list][$zCode][$list][$code] = ['code' => $code, 'name' => $name, 'nick' => $nick];

                } else {
                    $address[$prov][$list][$city][$list][$code] = ['code' => $code, 'name' => $name, 'nick' => $nick];
                }
            }
        }
        echo '没有地级市县的省级地区：' . json_encode($ePro, 320) . "\n\n";
        echo '没有县区的地级市：' . json_encode($eCity, 320) . "\n\n";
        echo '直属省级的县级市：' . json_encode($eCount, 320 + 128) . "\n\n";

        $array = [];
        $codeName = [];
        $nameCode = [];
        $codeCheck = [];

        foreach ($address as $a => &$prov) {
            if (in_array($prov['code'], $empProv)) {
                $prov['list'] = $comparison[$prov['code']]['list'];
            }
            $p = ['code' => $prov['code'], 'name' => ($prov['name']), 'nick' => ($prov['nick']), 'list' => []];
            $codeName[$prov['code']] = ['name' => $p['name'], 'nick' => $prov['nick']];
            $nameCode[$p['name']] = ['code' => $prov['code'], 'name' => $p['name'], 'nick' => $prov['nick']];
            if (!isset($codeCheck[$prov['code']])) $codeCheck[$prov['code']] = 0;
            $codeCheck[$prov['code']]++;

            if (!empty($prov['list'])) {
                foreach ($prov['list'] as $b => &$city) {
                    $c = ['code' => $city['code'], 'name' => ($city['name']), 'nick' => ($city['nick']), 'list' => []];
                    $codeName[$city['code']] = ['name' => $c['name'], 'nick' => $city['nick']];
                    $nameCode[$c['name']] = ['code' => $city['code'], 'name' => $c['name'], 'nick' => $city['nick']];
                    if (!isset($codeCheck[$city['code']])) $codeCheck[$city['code']] = 0;
                    $codeCheck[$city['code']]++;

                    if (!empty($city['list'])) {
                        foreach ($city['list'] as $z => &$cont) {
                            $c['list'][] = ['code' => $cont['code'], 'name' => $cont['name'], 'nick' => $cont['nick']];
                            $codeName[$cont['code']] = ['name' => $cont['name'], 'nick' => $cont['nick']];
                            $nameCode[$cont['name']] = ['code' => $cont['code'], 'name' => $cont['name'], 'nick' => $cont['nick']];
                            if (!isset($codeCheck[$cont['code']])) $codeCheck[$cont['code']] = 0;
                            $codeCheck[$cont['code']]++;
                        }
                    } else {
                        //没有区，主要是海南三沙市
                        $newCode = substr($city['code'], 0, 4) . '01';
                        $city['list'][$newCode] = ['code' => $newCode, 'name' => $city['name'], 'nick' => $city['nick']];
                    }
                    $p['list'][] = $c;
                }
            }
            $array[] = $p;
        }

        foreach ($compCode as $c => $n) {
            if (!isset($codeName[$c])) {
                echo "原地区【{$c}:{$n['name']}】不存在了\n";
            }
        }


//        print_r($address);
        echo file_put_contents(rtrim($save . '/') . '/china.array.json', json_encode($array, $js)) . "\n";
        echo file_put_contents(rtrim($save . '/') . '/china.object.json', json_encode($address, $js)) . "\n";
        echo file_put_contents(rtrim($save . '/') . '/china.code.json', json_encode($codeName, $js)) . "\n";
        echo file_put_contents(rtrim($save . '/') . '/china.name.json', json_encode($nameCode, $js)) . "\n";
        echo "请注意港澳台地区是否有更新，若有，请手动编辑\n";
        echo "手工复制china.code.json到setting/address.json\n";

    }


    /**
     * 整理gps
     *      * https://lbs.qq.com/service/webService/webServiceGuide/webServiceDistrict
     * 调用示例，含全国地区
     * https://apis.map.qq.com/ws/district/v1/list?key=OB4BZ-D4W3U-B7VVO-4PJWW-6TKDJ-WPB77
     *
     * 将这里的内容保存到本地，再分析读取
     */
    public function areaGps()
    {
        $file = _ROOT . '/runtime/area/list.json';
        $data = file_get_contents($file);
        $data = json_decode($data, true);
        $address = [];
        foreach ($data['result'] as $c) {
            foreach ($c as $a) {
                $address[$a['id']] = $a['location'];
                $address[$a['id']]['name'] = $a['fullname'];
            }
        }
        print_r($address);
        ksort($address);
        file_put_contents(_ROOT . '/runtime/area/location.json', json_encode($address, 256 | 128));
    }


    /**
     * 数据源同上
     */
    public function areaCodeDB()
    {

        $reZu = function ($n) {
            $n = str_replace(['自治区', '自治旗', '自治州', '自治县', '鄂温克族', '东乡族自治县', '(由澳门特别行政区实施管辖)'], ['', '旗', '州', '县', '鄂温克', '东乡县', ''], $n);
            $zu = ["满族", "蒙古族", "回族", "达斡尔族", "朝鲜族", "畲族", "土家族", "苗族", "瑶族", "侗族", "各族", "仫佬族", "毛南族", "黎族",
                "羌族", "仡佬族", "布依族", "水族", "哈尼族", "纳西族", "拉祜族", "佤族", "布朗族", "傣族", "壮族", "白族", "彝族", "景颇族",
                "傈僳族", "独龙族", "怒族", "普米族", "裕固族", "哈萨克族", "东乡族", "保安族", "土族", "撒拉族", "藏族"];
            $reName = '/(' . implode('|', $zu) . '|哈萨克|蒙古|维吾尔|特别行政区)/';
            if ($n === '内蒙古') return $n;
            return preg_replace($reName, '', $n);
        };

        $file = _ROOT . '/runtime/area/list.json';
        $data = file_get_contents($file);
        $data = json_decode($data, true);
        $value = [];
        foreach ($data['result'] as $lev => $address) {
            foreach ($address as $add) {
                $c = $add['id'];
                $val = [];
                $val['code'] = $add['id'];
                $val['name'] = $reZu($add['fullname']);
                $val['nick'] = $add['name'] ?? $val['name'];

                if (substr($c, -4) === '0000') {//省级
                    $val['list'] = [];
                    $value[$add['id']] = $val;
                } else if (substr($c, -2) === '00') {//市级
                    $pCode = substr($c, 0, 2) . '0000';
                    $cCode = substr($c, 0, 4) . '00';
                    $value[$pCode]['list'][$cCode] = $val;
                } else {
                    $pCode = substr($c, 0, 2) . '0000';
                    $cCode = substr($c, 0, 4) . '00';
                    if (!isset($value[$pCode]['list'][$cCode])) {
                        $value[$pCode]['list'][$cCode] = $value[$pCode];
                        $value[$pCode]['list'][$cCode]['code'] = $cCode;
                        $value[$pCode]['list'][$cCode]['list'] = [];
                        if ($cCode === '500100') {
                            $value[$pCode]['list'][$cCode]['name'] = '重庆市辖区';
                            $value[$pCode]['list'][$cCode]['nick'] = '重庆市辖区';
                        } else if ($cCode === '500200') {
                            $value[$pCode]['list'][$cCode]['name'] = '重庆市辖县';
                            $value[$pCode]['list'][$cCode]['nick'] = '重庆市辖县';
                        }
                    }
                    $value[$pCode]['list'][$cCode]['list'][$add['id']] = $val;
                }

            }
        }
//        print_r($address);
//        ksort($value);
        file_put_contents(_ROOT . '/runtime/area/china_full.json', json_encode($value, 256 | 128));
    }


    /**
     * 转一维数组
     */
    public function areaAddressCode()
    {
        $file = _ROOT . '/runtime/area/list.json';
        $data = file_get_contents($file);
        $data = json_decode($data, true);
        $value = [];
        foreach ($data['result'] as $lev => $address) {
            foreach ($address as $add) {
                $val = [];
                $val['code'] = $add['id'];
                $val['name'] = $add['fullname'];
                $val['nick'] = $add['name'] ?? $val['name'];
                if (isset($value[$add['fullname']])) {
                    echo "{$add['fullname']}\t重名地区\n";
                    $v = $value[$add['fullname']];
                    if (isset($v['code'])) {
                        $value[$add['fullname']] = [$v];
                    }
                    $value[$add['fullname']][] = $val;
                } else {
                    $value[$add['fullname']] = $val;
                }
            }
        }
        file_put_contents(_ROOT . '/runtime/area/china_list2.json', json_encode($value, 256 | 128));
    }

    /**
     * addCode,addProvince,addCity,addCounty
     */

}