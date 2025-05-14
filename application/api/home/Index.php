<?php
namespace app\api\home;

use think\Controller;
use think\facade\Request;


class Index extends Controller
{
    public function get_url()
    {
        // $uid = id_decode('TURBd01EQXdNREF3TUg2MWhIQQ');
        // $jmuid = id_encode(14);

        // https://woaishangav.kuaizhan.com/?type=0&s=TURBd01EQXdNREF3TUg2MWhIQQ&id=0
        // https://woaishangav.kuaizhan.com/?type=1&s=TURBd01EQXdNREF3TUg2MWhIQQ&id=5930

        // /s/TURBd01EQXdNREF3TUg2MWhIQQ
        // /s/TURBd01EQXdNREF3TUg2MWhIQQ/video_detail?id=5930

        $data = Request::param();

        // $domain = getKYDomain();

        $map = [];
        if ($data['wq'] == 'wx') {
            $map['wx_status'] = 1;
        }
        if ($data['wq'] == 'qq') {
            $map['qq_status'] = 1;
        }
        $domain = db('domain')
                    ->where($map)
                    ->where('status',1)
                    ->field('domain,is_all')
                    ->find();

        // qr_url
        $url = empty($domain) ? config('web.qr_url') : $domain['domain'];


        if($domain['is_all'] == 1){
            $prefix = getDomainPrefix().'.'.$url;
        }else{
            $prefix = $url;
        }

        if ($data['type'] == "0") {
            echo 'http://'.$prefix.'/s/'.$data['k'];
        } elseif ($data['type'] == "1"){
            if ($data['id']) {
                echo  'http://'.$prefix.'/s/'.$data['k'].'/video_detail?id='.$data['id'];
            }
        } elseif ($data['type'] == "2"){
            if ($data['id']) {
                echo  'http://'.$prefix.'/s/'.$data['k'].'/book_detail?id='.$data['id'];
            }
        }

    }
}