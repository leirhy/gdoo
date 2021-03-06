<?php namespace Gdoo\Index\Controllers;

use DB;
use URL;
use Request;

use App\Support\Pinyin;

use Gdoo\User\Models\User;
use Gdoo\Index\Models\Notification;

class ApiController extends Controller
{
    /**
     * jq导出xls
     */
    public function jqexportAction()
    {
        $gets = Request::all();
        $data = urldecode($gets['data']);
        $rows = json_decode($data, true);
        return writeExcel($rows['thead'], $rows['tbody'], 'jqexport');
    }
    
    /**
     * 初始化JS输出
     */
    public function commonAction()
    {
        $settings['public_url'] = URL::to('/');
        $settings['upload_file_type'] = $this->setting['upload_type'];
        $settings['upload_max_size'] = $this->setting['upload_max'];
        $settings['openSource'] = $this->openSource;

        header('Content-type: text/javascript');
        echo 'var settings = '. json_encode($settings, JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * 任务调用
     */
    public function taskAction()
    {
        $rows = DB::table('cron')->where('status', 1)->get();
        if ($rows) {
            foreach ($rows as $row) {
                $cron = \Cron\CronExpression::factory($row['expression']);

                // 由于定时任务无法定义秒这里特殊处理一下
                if (strtotime($row['next_run']) <= time()) {
                    // 这里执行代码
                    // 记录下次执行和本次执行结果
                    $next = $cron->getNextRunDate()->format('Y-m-d H:i:00');
                    $data = [
                        'next_run' => $next,
                        'last_run' => '执行成功。'
                    ];
                    DB::table('cron')->where('id', $row['id'])->update($data);
                }
            }
        }
    }

    /**
     * 获取单据编号
     */
    public function billSeqNoAction()
    {
        $bill_id = Request::get('bill_id');
        $date = Request::get('date');
        $bill = DB::table('model_bill')->where('id', $bill_id)->first();
        $model = DB::table('model')->where('id', $bill['model_id'])->first();
        $make_sn = make_sn([
            'table' => $model['table'],
            'date' => $date,
            'bill_id' => $bill['id'],
            'prefix' => $bill['sn_prefix'],
            'rule' => $bill['sn_rule'],
            'length' => $bill['sn_length'],
        ]);
        return $this->json($make_sn['new_value'], true);
    }

    /**
     * 汉字转拼音
     */
    public function pinyinAction()
    {
        $word = Request::get('name');
        $type = Request::get('type');

        if (empty($word)) {
            return '';
        }

        if ($type == 'first') {
            return str_replace('/', '', Pinyin::output(str_replace(' ', '', $word)));
        } else {
            return str_replace('/', '', Pinyin::getstr(str_replace(' ', '', $word)));
        }
    }

    /**
     * 显示位置信息
     */
    public function locationAction()
    {
        $gets = Request::all();
        return $this->render(array(
            'gets' => $gets
        ));
    }

    /**
     * 系统字典
     */
    public function dictAction()
    {
        $key  = Request::get('key');
        $rows = option($key);
        return response()->json($rows)->setEncodingOptions(JSON_UNESCAPED_UNICODE);
    }

    /**
     * 系统选项
     */
    public function optionAction()
    {
        $key  = Request::get('key');
        $rows = option($key);
        return response()->json($rows)->setEncodingOptions(JSON_UNESCAPED_UNICODE);
    }

    /**
     * 不支持浏览器提示
     */
    public function unsupportedBrowserAction()
    {
        return $this->render();
    }

    /*
     * 显示用户列表
     */
    public function dialogAction()
    {
        $gets = Request::all();
        return $this->render([
            'gets' => $gets
        ]);
    }

    /*
     * 调用省市县显示
     */
    public function regionAction()
    {
        $parent_id = Request::get('parent_id', 0);
        $layer = Request::get('layer', 1);
        $names = [1=>'省' ,2=>'市', 3=>'县'];
        $title[] = ['id' => '' ,'name' => $names[$layer]];

        $rows = DB::table('region')
        ->where('parent_id', (int)$parent_id)
        ->where('layer', $layer)
        ->get()->toArray();

        $rows = array_merge($title, $rows);
        return response()->json($rows);
    }
}
