<?php namespace Gdoo\Customer\Controllers;

use DB;
use Request;

use Gdoo\Index\Controllers\DefaultController;

class WidgetController extends DefaultController
{
    public $permission = ['birthday'];

    // 生日提醒
    public function birthdayAction()
    {
        if (Request::method() == 'POST') {

            $model = DB::table('customer');
            $region = regionCustomer();
            if ($region['authorise']) {
                foreach ($region['whereIn'] as $key => $where) {
                    $model->whereIn($key, $where);
                }
            }

            if ($this->dbType == 'sqlsrv') {
                $model->whereRaw('
                    (datediff(dd, getdate(), dateadd(year, datediff(year, head_birthday, getdate()), head_birthday)) between 0 and 7)
                    OR
                    (datediff(dd, getdate(), dateadd(year, datediff(year, head_birthday, getdate())+1, head_birthday)) between 0 and 7)')
                ->selectRaw('id, code, name, head_name, head_phone, head_birthday')
                ->get();
            } else if($this->dbType == 'pgsql') {
                $model->whereRaw("
                    (concat(date_part('year', current_date), '-', date_part('month', head_birthday), '-', date_part('day', head_birthday))::date - current_date between 0 and 7) 
                    OR
                    (concat(date_part('year', current_date) + 1, '-', date_part('month', head_birthday), '-', date_part('day', head_birthday))::date - current_date between 0 and 7)")
                ->selectRaw("id, code, name, head_name, head_phone, concat(date_part('year', current_date), '-', date_part('month', head_birthday), '-', date_part('day', head_birthday))::date as head_birthday");
            }

            else if($this->dbType == 'mysql') {
                $model->whereRaw("
                    (concat(year(now()), DATE_FORMAT(birthday,'-%m-%d')) BETWEEN DATE_FORMAT(now(),'%Y-%m-%d') AND DATE_FORMAT(DATE_ADD(now(), interval 10 day),'%Y-%m-%d'))
                    OR 
                    (concat(year(now()) + 1, DATE_FORMAT(birthday,'-%m-%d')) BETWEEN DATE_FORMAT(now(),'%Y-%m-%d') AND DATE_FORMAT(DATE_ADD(now(), interval 10 day),'%Y-%m-%d'))")
                ->selectRaw("id, code, name, head_name, head_phone, concat(year(now()), DATE_FORMAT(birthday,'-%m-%d')) as head_birthday");
            }

            $rows = $model->get();

            $json['total'] = sizeof($rows);
            $json['data'] = $rows;
            return response()->json($json);
        }
        return $this->render();
    }
}
