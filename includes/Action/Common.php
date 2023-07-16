<?php

namespace ParcelPanel\Action;

use ParcelPanel\Api\Api;
use ParcelPanel\Libs\Singleton;

class Common
{
    use Singleton;

    // 获取公共配置
    public static function getCommonSetting()
    {
        $commonSetMessage = Api::userCommonLangList([]);
        if (is_wp_error($commonSetMessage)) {
            return [];
        }
        return $commonSetMessage['data'] ?? [];
    }


    // 获取公共配置
    public function getNowLang()
    {
        // $data = self::getCommonSetting();
        // $langPP = $data['lang'] ?? '';// pp lang
        $langPP = get_option( \ParcelPanel\OptionName\PP_LANG_NOW );

        $langWP = get_locale();
        $langArr = explode('_', $langWP);
        $langP = $langArr[0] ?? '';

        $resLang = $langWP;// use wp lang
        if ($langWP != $langPP && !empty($langPP)) {
            if ($langP != $langPP) {
                $resLang = $langPP;// use pp lang
            }
        }
        return $resLang;
    }

}
