<?php namespace Gdoo\Order\Controllers;

use DB;
use Request;
use Auth;
use Validator;

use Gdoo\Model\Form;
use Gdoo\Model\Grid;

use Gdoo\User\Models\User;
use Gdoo\Order\Models\Logistics;

use Gdoo\Index\Controllers\DefaultController;

class LogisticsController extends DefaultController
{
    public $permission = ['dialog'];

    public function indexAction()
    {
        $header = Grid::header([
            'code' => 'logistics',
            'referer' => 1,
            'search' => ['by' => ''],
            'trash_btn' => 0,
        ]);

        $cols = $header['cols'];

        $cols['actions']['options'] = [[
            'name' => '编辑',
            'action' => 'edit',
            'display' => $this->access['edit'],
        ]];

        $search = $header['search_form'];
        $query = $search['query'];

        if (Request::method() == 'POST') {
            $model = DB::table($header['table'])->setBy($header);
            foreach ($header['join'] as $join) {
                $model->leftJoin($join[0], $join[1], $join[2], $join[3]);
            }
            $model->orderBy($header['sort'], $header['order']);

            foreach ($search['where'] as $where) {
                if ($where['active']) {
                    $model->search($where);
                }
            }

            $model->select($header['select']);
            $rows = $model->paginate($query['limit'])->appends($query);
            $items = Grid::dataFilters($rows, $header);
            return $items->toJson();
        }

        $header['buttons'] = [
            ['name' => '删除', 'icon' => 'fa-remove', 'action' => 'delete', 'display' => $this->access['delete']],
            ['name' => '导出', 'icon' => 'fa-share', 'action' => 'export', 'display' => 1],
        ];
        $header['cols'] = $cols;
        $header['tabs'] = Logistics::$tabs;
        $header['bys'] = Logistics::$bys;
        $header['js'] = Grid::js($header);

        return $this->display([
            'header' => $header,
        ]);
    }

    // 新建客户联系人
    public function createAction($action = 'edit')
    {
        $id = (int)Request::get('id');
        $form = Form::make(['code' => 'logistics', 'id' => $id, 'action' => $action]);
        return $this->display([
            'form' => $form,
        ], 'create');
    }

    // 创建客户联系人
    public function editAction()
    {
        return $this->createAction();
    }

    // 创建客户联系人
    public function showAction()
    {
        return $this->createAction('show');
    }

    // 删除
    public function deleteAction()
    {
        if (Request::method() == 'POST') {
            $ids = Request::get('id');
            return Form::remove(['code' => 'logistics', 'ids' => $ids]);
        }
    }

    /**
     * 弹出层信息
     */
    public function dialogAction()
    {
        $search = search_form([
            'advanced' => '',
            'prefix'   => '',
            'offset'   => '',
            'sort'     => '',
            'order'    => '',
            'limit'    => '',
        ], [
            ['text','logistics.name','名称'],
        ]);
        $query  = $search['query'];

        if (Request::method() == 'POST') {
            $model = DB::table('logistics');
            // 排序方式
            if ($query['sort'] && $query['order']) {
                $model->orderBy('logistics.'.$query['sort'], $query['order']);
            }

            foreach ($search['where'] as $where) {
                if ($where['active']) {
                    $model->search($where);
                }
            }
            
            if ($query['q']) {
                $model->where('logistics.name', 'like', '%'.$query['q'].'%');
            }

            $model->selectRaw("logistics.*, logistics.name as text");

            if ($query['limit']) {
                $rows = $model->paginate($query['limit']);
            } else {
                $rows['total'] = $model->count();
                $rows['data'] = $model->get();
            }
            return response()->json($rows);
        }

        return $this->render([
            'search' => $search,
            'query' => $query,
        ]);
    }
}
